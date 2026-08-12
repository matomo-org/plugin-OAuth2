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
     * The identity the OAuth2 token was issued for. authenticate() only succeeds for this
     * identity; $login may be mutated via setLogin() but the subject is fixed.
     *
     * @var string
     */
    private $subject;

    private bool $isSuperUser;

    /**
     * Set once password-based authentication is requested.
     *
     * @var bool
     */
    private bool $passwordAuthRequested = false;

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
        if ($token_auth === null || $token_auth === '') {
            $this->passwordAuthRequested = true;
        }
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

    /**
     * Piwik\Auth documents this as a string, but the tracker passes null
     * (Tracker\Request and BulkTracking\Tracker\Handler both do).
     *
     * @param string|null $password
     */
    public function setPassword(
        #[\SensitiveParameter]
        $password
    ) {
        // OAuth2 authenticates by bearer token, not by password.
        if ($password !== null) {
            $this->passwordAuthRequested = true;
        }
    }

    /**
     * Piwik\Auth documents this as a string, but the tracker passes null.
     *
     * @param string|null $passwordHash
     */
    public function setPasswordHash(
        #[\SensitiveParameter]
        $passwordHash
    ) {
        if ($passwordHash !== null) {
            $this->passwordAuthRequested = true;
        }
    }

    public function authenticate()
    {
        // A token does not carry a password; refuse if asked to authenticate by one.
        if ($this->passwordAuthRequested) {
            return new AuthResult(AuthResult::FAILURE, $this->login, $this->tokenAuth);
        }

        // Only authenticate the identity the token was issued for.
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
