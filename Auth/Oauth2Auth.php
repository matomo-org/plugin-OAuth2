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

    /**
     * The real subject the OAuth2 token was issued for. authenticate() only ever succeeds for
     * this identity. setLogin() may mutate $login (the Piwik\Auth contract allows it, and core
     * code such as PasswordVerifier does so), but the token's subject is fixed and must never
     * change - otherwise an OAuth2-authenticated request could authenticate as an arbitrary
     * account.
     *
     * @var string
     */
    private $subject;

    private bool $isSuperUser;

    private string $tokenAuth;

    public array $scopes;

    public string $clientId;

    public function __construct(string $login, bool $isSuperUser, string $tokenId, string $clientId, array $scopes)
    {
        $this->login = $login;
        $this->subject = $login;
        $this->isSuperUser = $isSuperUser;
        $this->tokenAuth = 'oauth2:' . $tokenId;
        $this->clientId = $clientId;
        $this->scopes = $scopes;
    }

    public function getName()
    {
        return 'OAuth2';
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
        // Only authenticate the identity the token was actually issued for. If the login was
        // mutated to a different account (e.g. via setLogin() from PasswordVerifier), refuse -
        // this token proves nothing about any other user.
        if ($this->login !== $this->subject) {
            return new AuthResult(AuthResult::FAILURE, $this->login, $this->tokenAuth);
        }

        $code = $this->allowsSuperUserAccess() ? AuthResult::SUCCESS_SUPERUSER_AUTH_CODE : AuthResult::SUCCESS;
        return new AuthResult($code, $this->login, $this->tokenAuth);
    }

    public function getPrimaryScope(): ?string
    {
        return $this->scopes[0] ?? null;
    }

    public function isSubjectSuperUser(): bool
    {
        return $this->isSuperUser;
    }

    public function allowsSuperUserAccess(): bool
    {
        return $this->isSuperUser && $this->getPrimaryScope() === 'matomo:superuser';
    }
}
