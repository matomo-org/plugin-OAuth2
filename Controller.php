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
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\ClientEntityInterface;
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

        $requestedClientScopes = $this->getScopesAllowedForClient(array_values($scopes), $authRequest->getClient());

        if (empty($requestedClientScopes)) {
            return $this->renderUnauthorized(Piwik::translate('OAuth2_InvalidClientScope'));
        }

        $selectableScopes = $this->getScopesUserCanGrant($requestedClientScopes);

        if (empty($selectableScopes)) {
            return $this->renderUnauthorized(Piwik::translate(
                'OAuth2_NoAccessForRequestedScopes',
                implode(', ', $requestedClientScopes)
            ));
        }

        if ($this->isPostRequest()) {
            // the consent form is submitted as POST, and Request::fromRequest() lets a query string
            // parameter of the same name win, so the decision must be read from the POST body only
            $post = Request::fromPost();
            $decision = $post->getStringParameter('decision', '');
            $nonce = $post->getStringParameter('nonce', '');

            if (!Nonce::verifyNonce('Oauth2.authorize', $nonce)) {
                return $this->renderUnauthorized(Piwik::translate('OAuth2_InvalidAuthorizationRequest'));
            }

            if (!in_array($decision, ['allow', 'deny'], true)) {
                return $this->renderUnauthorized(Piwik::translate('OAuth2_InvalidAuthorizationRequest'));
            }

            $selectedScope = $post->getStringParameter('selected_scope', '');

            if (!in_array($selectedScope, $selectableScopes, true)) {
                return $this->renderUnauthorized(Piwik::translate('OAuth2_InvalidScopeValue'));
            }

            $this->checkDoesUserHasAccessAsPerScope($selectedScope);

            $authRequest->setScopes([$this->scopeRepository->getScopeEntityByIdentifier($selectedScope)]);

            $isApproved = $decision === 'allow';
            $authRequest->setAuthorizationApproved($isApproved);

            /**
             * Triggered after a user allowed or denied an OAuth 2.0 authorization request on the
             * consent screen, before the authorization code is issued.
             *
             * Used by the plugin itself to record the decision in the activity log, and available
             * to other plugins that need to audit or react to granted and denied access.
             *
             * @param array $activityData Details of the decision:
             *
             *                            - `version`: the payload version, currently `v1`.
             *                            - `client`: the OAuth client, with `id` and `name`, plus
             *                              `type` and `active` for clients of this plugin.
             *                            - `userLogin`: the login of the user who decided.
             *                            - `scopes`: the scopes that were granted. The user grants
             *                              exactly one scope, so this holds a single scope, and it
             *                              is the scope the user selected rather than the full list
             *                              the client requested.
             *                            - `decision`: either `allowed` or `denied`.
             */
            Piwik::postEvent('OAuth2.authorize.decision.end', [
                $this->buildAuthorizationActivityData($authRequest->getClient(), $login, [$selectedScope], $isApproved),
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
            'scopes' => $selectableScopes,
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
        $this->sendResponseCode($response->getStatusCode(), $response->getReasonPhrase());
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
        $this->sendResponseCode(400);
        return $message;
    }

    private function sendResponseCode(int $statusCode, string $reasonPhrase = ''): void
    {
        try {
            Common::sendResponseCode($statusCode);
            return;
        } catch (\Exception $e) {
            // Common::sendResponseCode() only knows a fixed set of status codes and rejects the
            // others, such as the 405 the token and metadata endpoints send for a wrong method.
        }

        Common::sendHeader(rtrim($this->getStatusHeaderPrefix() . ' ' . $statusCode . ' ' . $reasonPhrase));
    }

    /**
     * Same prefix Common::sendResponseCode() uses, as FastCGI needs a Status header instead of a
     * status line and the response should keep the protocol the request was made with.
     */
    private function getStatusHeaderPrefix(): string
    {
        if (strpos(PHP_SAPI, '-fcgi') !== false) {
            return 'Status:';
        }

        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';

        if (strlen($protocol) > 1 && strlen($protocol) < 15) {
            return $protocol;
        }

        return 'HTTP/1.1';
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
            default:
                // never reached, the scope is validated against the selectable scopes beforehand
                throw new \Exception(Piwik::translate('OAuth2_InvalidScopeValue'));
        }
    }

    /**
     * Returns the requested scopes the client is allowed to use, in ascending privilege order
     * (read < write < admin < superuser).
     */
    private function getScopesAllowedForClient(array $requestedScopes, ClientEntityInterface $client): array
    {
        $allowed = $this->scopeRepository->getAllowedScopeIds();

        if ($client instanceof ClientEntity && !empty($client->getAllowedScopes())) {
            // an empty client scope list means no client specific restriction, as in ScopeRepository::finalizeScopes()
            $allowed = array_values(array_intersect($allowed, $client->getAllowedScopes()));
        }

        return array_values(array_intersect($allowed, $requestedScopes));
    }

    /**
     * Returns the scopes the current user has a high enough access level to grant.
     */
    private function getScopesUserCanGrant(array $scopes): array
    {
        return array_values(array_filter($scopes, function (string $scope) {
            return $this->canUserGrantScope($scope);
        }));
    }

    private function canUserGrantScope(string $scope): bool
    {
        switch ($scope) {
            case 'matomo:read':
                return Piwik::isUserHasSomeViewAccess();
            case 'matomo:write':
                return Piwik::isUserHasSomeWriteAccess();
            case 'matomo:admin':
                return Piwik::isUserHasSomeAdminAccess();
            case 'matomo:superuser':
                return Piwik::hasUserSuperUserAccess();
            default:
                return false;
        }
    }
}
