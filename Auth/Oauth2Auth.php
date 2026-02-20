<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Auth;

use Piwik\Auth;
use Piwik\AuthResult;

class Oauth2Auth implements Auth
{
    /**
     * @var string
     */
    private $login;

    private bool $isSuperUser;

    private string $tokenAuth;

    public array $scopes;

    public string $clientId;

    public function __construct(string $login, bool $isSuperUser, string $tokenId, string $clientId, array $scopes)
    {
        $this->login = $login;
        $this->isSuperUser = $isSuperUser;
        $this->tokenAuth = 'oauth2:' . $tokenId;
        $this->clientId = $clientId;
        $this->scopes = $scopes;
    }

    public function getName()
    {
        return 'Oauth2';
    }

    public function setTokenAuth(
        #[\SensitiveParameter]
        $token_auth
    ) {
        // not used for OAuth2 authentication
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getTokenAuthSecret()
    {
        return null;
    }

    public function setLogin($login)
    {
        $this->login = $login;
    }

    public function setPassword(
        #[\SensitiveParameter]
        $password
    ) {
        // not used
    }

    public function setPasswordHash(
        #[\SensitiveParameter]
        $passwordHash
    ) {
        // not used
    }

    public function authenticate()
    {
        $code = $this->isSuperUser ? AuthResult::SUCCESS_SUPERUSER_AUTH_CODE : AuthResult::SUCCESS;
        return new AuthResult($code, $this->login, $this->tokenAuth);
    }
}
