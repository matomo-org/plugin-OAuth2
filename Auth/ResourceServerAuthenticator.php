<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Oauth2\Auth;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\Oauth2\Model\ClientModel;
use Piwik\Plugins\Oauth2\Service\ServerFactory;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Exception\OAuthServerException;

class ResourceServerAuthenticator
{
    public function __construct(
        private ServerFactory $serverFactory,
        private ClientModel $clientModel,
        private UserModel $userModel
    ) {
    }

    public function prepareAuthenticationFromToken(?string $tokenAuth): void
    {
        if (empty($tokenAuth)) {
            return;
        }

        try {
            $request = $this->buildRequest($tokenAuth);
            $validated = $this->serverFactory->makeResourceServer()->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $e) {
            // fall back to default authentication flow
            return;
        } catch (\Throwable $e) {
            return;
        }

        $login = $validated->getAttribute('oauth_user_id');
        $clientId = (string) $validated->getAttribute('oauth_client_id');
        $scopes = $validated->getAttribute('oauth_scopes') ?? [];
        $tokenId = (string) $validated->getAttribute('oauth_access_token_id');

        if (empty($login)) {
            $client = $this->clientModel->find($clientId);
            $login = $client['owner_login'] ?? '';
        }

        if (empty($login)) {
            return;
        }

        $user = $this->userModel->getUser($login);
        if (empty($user['login'])) {
            return;
        }

        $isSuperUser = !empty($user['superuser_access']);

        StaticContainer::getContainer()->set(
            'Piwik\Auth',
            new Oauth2Auth($login, $isSuperUser, $tokenId, $clientId, (array) $scopes)
        );
    }

    private function buildRequest(string $tokenAuth): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        $request = $creator->fromGlobals();
        if (!$request->hasHeader('Authorization')) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $tokenAuth);
        }

        return $request;
    }
}
