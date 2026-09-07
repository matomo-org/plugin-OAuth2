<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Auth;

use Matomo\Dependencies\OAuth2\Nyholm\Psr7\Factory\Psr17Factory;
use Matomo\Dependencies\OAuth2\Nyholm\Psr7Server\ServerRequestCreator;
use Piwik\Access;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Service\ServerFactory;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Matomo\Dependencies\OAuth2\Psr\Http\Message\ServerRequestInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Exception\OAuthServerException;

class ResourceServerAuthenticator
{
    public function __construct(
        private ServerFactory $serverFactory,
        private ClientModel $clientModel,
        private UserModel $userModel
    ) {
    }

    public function prepareAuthenticationFromToken(
        #[\SensitiveParameter]
        ?string $tokenAuth
    ): void {
        $authorizationHeader = OAuth2::getAuthorizationHeader();
        $hasAuthorizationHeader = !empty($authorizationHeader) && strpos($authorizationHeader, 'Bearer ') === 0;

        if (!$hasAuthorizationHeader && empty($tokenAuth)) {
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
        $scopes = (array) ($validated->getAttribute('oauth_scopes') ?? []);
        $tokenId = (string) $validated->getAttribute('oauth_access_token_id');

        if (empty($scopes)) {
            return;
        }

        $client = $this->clientModel->find($clientId);
        if (empty($client) || empty($client['active']) || empty($client['owner_login'])) {
            return;
        }

        // A client is removed together with its owner, so one that outlived them was left behind by
        // a cleanup that did not complete. Its login is free to be given to somebody else, and the
        // token must not start authenticating as whoever holds it now.
        $owner = $this->userModel->getUser($client['owner_login']);
        if (empty($owner['login'])) {
            return;
        }

        if (empty($login)) {
            $login = $client['owner_login'];
        }

        $user = $login === $client['owner_login'] ? $owner : $this->userModel->getUser($login);
        if (empty($user['login'])) {
            return;
        }

        $isSuperUser = !empty($user['superuser_access']);

        $auth = new Oauth2Auth($login, $isSuperUser, $tokenId, $clientId, (array) $scopes);
        StaticContainer::getContainer()->set(
            'Piwik\Auth',
            $auth
        );
        Access::getInstance()->reloadAccess($auth);
    }

    private function buildRequest(
        #[\SensitiveParameter]
        ?string $tokenAuth
    ): ServerRequestInterface {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

        $request = $creator->fromGlobals();
        if (!$request->hasHeader('Authorization') && !empty($tokenAuth)) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $tokenAuth);
        }

        return $request;
    }
}
