<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Matomo\Dependencies\Oauth2\Nyholm\Psr7\Factory\Psr17Factory;
use Matomo\Dependencies\Oauth2\Nyholm\Psr7\Response;
use Matomo\Dependencies\Oauth2\Nyholm\Psr7Server\ServerRequestCreator;
use Piwik\Common;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugin\ControllerAdmin;
use Piwik\Plugin\Manager;
use Piwik\Plugins\OAuth2\Entities\ClientEntity;
use Piwik\Plugins\OAuth2\Entities\UserEntity;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\ServerFactory;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Request;
use Matomo\Dependencies\Oauth2\Psr\Http\Message\ResponseInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Exception\OAuthServerException;

class Controller extends ControllerAdmin
{
    public function __construct(
        private ClientModel $clientModel,
        private ScopeRepository $scopeRepository,
        private ServerFactory $serverFactory,
        private SystemSettings $settings,
        private UserModel $userModel
    ) {
        parent::__construct();
    }

    public function index()
    {
        Piwik::checkUserHasSuperUserAccess();

        $viewData = [
            'clients' => $this->clientModel->all(),
            'scopes' => $this->scopeRepository->describeScopes(),
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
            return $this->emitResponse($e->generateHttpResponse(new Response()));
        } catch (\Throwable $e) {
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

            $authRequest->setAuthorizationApproved($decision === 'allow');

            try {
                $response = $authServer->completeAuthorizationRequest($authRequest, new Response());
            } catch (OAuthServerException $e) {
                $response = $e->generateHttpResponse(new Response());
            } catch (\Throwable $e) {
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
            $response = $e->generateHttpResponse($response);
        } catch (\Throwable $e) {
            $response = $response->withStatus(500)->withBody((new Psr17Factory())->createStream(Piwik::translate('OAuth2_ServerError')));
        }

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
