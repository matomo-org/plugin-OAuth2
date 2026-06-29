<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Matomo\Dependencies\OAuth2\Nyholm\Psr7\Factory\Psr17Factory;
use Matomo\Dependencies\OAuth2\Nyholm\Psr7\Response;
use Matomo\Dependencies\OAuth2\Nyholm\Psr7Server\ServerRequestCreator;
use Piwik\Common;
use Piwik\Log;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugin\ControllerAdmin;
use Piwik\Plugin\Manager;
use Piwik\Plugins\OAuth2\Entities\ClientEntity;
use Piwik\Plugins\OAuth2\Entities\UserEntity;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\AuthorizationServerMetadata;
use Piwik\Plugins\OAuth2\Service\ServerFactory;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Request;
use Piwik\Url;
use Matomo\Dependencies\OAuth2\Psr\Http\Message\ResponseInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Exception\OAuthServerException;

class Controller extends ControllerAdmin
{
    public function __construct(
        private ClientModel $clientModel,
        private ScopeRepository $scopeRepository,
        private ServerFactory $serverFactory,
        private SystemSettings $settings,
        private UserModel $userModel,
        private AuthorizationServerMetadata $authorizationServerMetadata
    ) {
        parent::__construct();
    }

    public function index()
    {
        Piwik::checkUserHasSuperUserAccess();

        $baseUrl = Url::getCurrentUrlWithoutFileName();

        $viewData = [
            'clients' => array_map(static function (array $client) {
                unset($client['secret_hash']);
                return $client;
            }, $this->clientModel->all()),
            'scopes' => $this->scopeRepository->describeScopes(),
            'authorizeUrl' => $baseUrl . 'index.php?module=OAuth2&action=authorize',
            'tokenUrl' => $baseUrl . 'index.php?module=OAuth2&action=token',
        ];

        return $this->renderTemplate('index', $viewData);
    }

    public function authorize()
    {
        if (!$this->settings->enableAuthorizationCode->getValue()) {
            return $this->renderUnauthorized(Piwik::translate('OAuth2_AuthorizationCodeGrantDisabled'));
        }

        if (Piwik::isUserIsAnonymous()) {
            Piwik::checkUserIsNotAnonymous();
        }

        $psrRequest = $this->createServerRequest();
        $authServer = $this->serverFactory->makeAuthorizationServer();

        try {
            $authRequest = $authServer->validateAuthorizationRequest($psrRequest);
        } catch (OAuthServerException $e) {
            $this->logOAuthRequestFailure('authorize', $e);
            return $this->emitResponse($e->generateHttpResponse(new Response()));
        } catch (\Throwable $e) {
            $this->logOAuthRequestFailure('authorize', $e);
            return $this->renderUnauthorized(Piwik::translate('OAuth2_InvalidAuthorizationRequest'));
        }

        $login = Piwik::getCurrentUserLogin();
        $userEntity = new UserEntity();
        $userEntity->setIdentifier($login);
        $authRequest->setUser($userEntity);

        $scopes = array_map(function ($scope) {
            return $scope->getIdentifier();
        }, $authRequest->getScopes());

        $client = $authRequest->getClient();
        $clientScopes = [];
        if ($client instanceof ClientEntity) {
            $clientScopes = array_values($client->getAllowedScopes());
        }
        $scopes = array_values($scopes);

        if (count($scopes) !== 1 || count($clientScopes) !== 1 || $clientScopes[0] !== $scopes[0]) {
            return $this->renderUnauthorized(Piwik::translate('OAuth2_InvalidClientScope'));
        }

        $this->checkDoesUserHasAccessAsPerScope($scopes[0]);

        if ($this->isPostRequest()) {
            $decision = Request::fromRequest()->getStringParameter('decision', '');
            $nonce = Request::fromRequest()->getStringParameter('nonce', '');

            if (!Nonce::verifyNonce('Oauth2.authorize', $nonce)) {
                return $this->renderUnauthorized(Piwik::translate('OAuth2_InvalidAuthorizationRequest'));
            }

            $isApproved = $decision === 'allow';
            $authRequest->setAuthorizationApproved($isApproved);
            Piwik::postEvent('OAuth2.authorize.decision.end', [
                $this->buildAuthorizationActivityData($authRequest->getClient(), $login, $scopes, $isApproved),
            ]);

            try {
                $response = $authServer->completeAuthorizationRequest($authRequest, new Response());
            } catch (OAuthServerException $e) {
                $this->logOAuthRequestFailure('authorize', $e);
                $response = $e->generateHttpResponse(new Response());
            } catch (\Throwable $e) {
                $this->logOAuthRequestFailure('authorize', $e);
                $response = (new Response())->withStatus(500)->withBody((new Psr17Factory())->createStream(Piwik::translate('OAuth2_ServerError')));
            }

            return $this->emitResponse($response);
        }

        $client = $authRequest->getClient();
        $user = $this->userModel->getUser($login);

        $termsAndConditionUrl = '';
        $privacyPolicyUrl = '';
        if (Manager::getInstance()->isPluginActivated('PrivacyManager')) {
            $coreSettings = new \Piwik\Plugins\PrivacyManager\SystemSettings();
            $termsAndConditionUrl = $coreSettings->termsAndConditionUrl->getValue();
            $privacyPolicyUrl = $coreSettings->privacyPolicyUrl->getValue();
        }

        return $this->renderTemplate('authorize', [
            'clientName' => $client->getName(),
            'clientId' => $client->getIdentifier(),
            'userLogin' => $login,
            'userEmail' => $user['email'] ?? '',
            'scopes' => $scopes,
            'scopeDescriptions' => $this->scopeRepository->describeScopes(),
            'nonce' => Nonce::getNonce('Oauth2.authorize'),
            'termsAndCondition' => $termsAndConditionUrl,
            'privacyPolicyUrl' => $privacyPolicyUrl,
        ]);
    }

    public function token()
    {
        if (!$this->isPostRequest()) {
            $response = (new Response())
                ->withStatus(405, 'Method Not Allowed')
                ->withHeader('Allow', 'POST')
                ->withBody((new Psr17Factory())->createStream(Piwik::translate('OAuth2_TokenEndpointException')));

            return $this->emitResponse($response);
        }

        $psrRequest = $this->createServerRequest();
        $authServer = $this->serverFactory->makeAuthorizationServer();
        $response = new Response();

        try {
            $response = $authServer->respondToAccessTokenRequest($psrRequest, $response);
        } catch (OAuthServerException $e) {
            $this->logOAuthRequestFailure('token', $e);
            $response = $e->generateHttpResponse($response);
        } catch (\Throwable $e) {
            $this->logOAuthRequestFailure('token', $e);
            $response = $response->withStatus(500)->withBody((new Psr17Factory())->createStream(Piwik::translate('OAuth2_ServerError')));
        }

        return $this->emitResponse($response);
    }

    /**
     * Serves the OAuth 2.0 Authorization Server Metadata document (RFC 8414).
     *
     * This is a public endpoint. The web server is expected to forward
     * `/.well-known/oauth-authorization-server` to this action; see the plugin README.
     */
    public function metadata()
    {
        // This is a public, cacheable document. Matomo's normal dispatch starts a session,
        // which queues a session cookie and PHP's session cache-limiter headers
        // (Expires/Pragma: no-cache). Strip those so the response is cookie-free and cleanly
        // cacheable; the Cache-Control header sent below replaces the session's one.
        if (!headers_sent()) {
            header_remove('Set-Cookie');
            header_remove('Expires');
            header_remove('Pragma');
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $psr17Factory = new Psr17Factory();

        if ($method !== 'GET' && $method !== 'HEAD') {
            $response = (new Response())
                ->withStatus(405, 'Method Not Allowed')
                ->withHeader('Allow', 'GET, HEAD')
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withBody($psr17Factory->createStream((string) json_encode(['error' => 'Method not allowed.'])));

            return $this->emitResponse($response);
        }

        $metadata = $this->authorizationServerMetadata->build();
        $body = $method === 'HEAD' ? '' : (string) json_encode($metadata);

        $response = (new Response())
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'public, max-age=3600')
            ->withBody($psr17Factory->createStream($body));

        return $this->emitResponse($response);
    }

    private function createServerRequest()
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        return $creator->fromGlobals();
    }

    private function emitResponse(ResponseInterface $response)
    {
        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                Common::sendHeader($name . ': ' . $value);
            }
        }
        echo (string) $response->getBody();
        return null;
    }

    private function isPostRequest(): bool
    {
        return !empty($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }

    private function renderUnauthorized(string $message)
    {
        http_response_code(400);
        return $message;
    }

    private function buildAuthorizationActivityData($client, string $login, array $scopes, bool $isApproved): array
    {
        $clientData = [
            'id' => method_exists($client, 'getIdentifier') ? $client->getIdentifier() : null,
            'name' => method_exists($client, 'getName') ? $client->getName() : null,
        ];

        if ($client instanceof ClientEntity) {
            $clientData['type'] = $client->type;
            $clientData['active'] = $client->active;
        }

        return [
            'version' => 'v1',
            'client' => $clientData,
            'userLogin' => $login,
            'scopes' => array_values($scopes),
            'decision' => $isApproved ? 'allowed' : 'denied',
        ];
    }

    private function logOAuthRequestFailure(string $endpoint, \Throwable $exception): void
    {
        Log::warning(
            'OAuth 2.0 %s request failed: %s (%s)',
            $endpoint,
            get_class($exception),
            (string) $exception->getCode()
        );
    }

    private function checkDoesUserHasAccessAsPerScope(string $scope): void
    {
        switch ($scope) {
            case 'matomo:read':
                Piwik::checkUserHasSomeViewAccess();
                break;
            case 'matomo:write':
                Piwik::checkUserHasSomeWriteAccess();
                break;
            case 'matomo:admin':
                Piwik::checkUserHasSomeAdminAccess();
                break;
            case 'matomo:superuser':
                Piwik::checkUserHasSuperUserAccess();
                break;
        }
    }
}
