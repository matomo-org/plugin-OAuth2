<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Oauth2\Service;

use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Piwik\Plugins\OAuth2\Repositories\AccessTokenRepository;
use Piwik\Plugins\OAuth2\Repositories\AuthCodeRepository;
use Piwik\Plugins\OAuth2\Repositories\ClientRepository;
use Piwik\Plugins\OAuth2\Repositories\RefreshTokenRepository;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\SystemSettings;
use Piwik\Plugins\OAuth2\Service\MatomoAuthCodeGrant;

class ServerFactory
{
    private ?AuthorizationServer $authorizationServer = null;
    private ?ResourceServer $resourceServer = null;

    public function __construct(
        private ClientRepository $clientRepository,
        private AccessTokenRepository $accessTokenRepository,
        private ScopeRepository $scopeRepository,
        private AuthCodeRepository $authCodeRepository,
        private RefreshTokenRepository $refreshTokenRepository,
        private SystemSettings $settings
    ) {
    }

    public function makeAuthorizationServer(): AuthorizationServer
    {
        if ($this->authorizationServer) {
            return $this->authorizationServer;
        }

        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            new CryptKey($this->settings->privateKeyPath->getValue(), null, true),
            $this->getEncryptionKey()
        );

        $accessTokenTtl = $this->getAccessTokenTtl();
        $refreshTokenTtl = $this->getRefreshTokenTtl();
        $refreshTokensEnabled = $this->settings->enableRefreshTokens->getValue();

        if ($this->settings->enableAuthorizationCode->getValue()) {
            $grant = new MatomoAuthCodeGrant(
                $this->authCodeRepository,
                $this->refreshTokenRepository,
                $this->getAuthCodeTtl(),
                $refreshTokensEnabled
            );
            if ($refreshTokensEnabled) {
                $grant->setRefreshTokenTTL($refreshTokenTtl);
            }
            $server->enableGrantType($grant, $accessTokenTtl);
        }

        if ($this->settings->enableClientCredentials->getValue()) {
            $server->enableGrantType(new ClientCredentialsGrant(), $accessTokenTtl);
        }

        if ($refreshTokensEnabled) {
            $grant = new RefreshTokenGrant($this->refreshTokenRepository);
            $grant->setRefreshTokenTTL($refreshTokenTtl);
            $server->enableGrantType($grant, $accessTokenTtl);
        }

        $this->authorizationServer = $server;

        return $server;
    }

    public function makeResourceServer(): ResourceServer
    {
        if ($this->resourceServer) {
            return $this->resourceServer;
        }

        $this->resourceServer = new ResourceServer(
            $this->accessTokenRepository,
            new CryptKey($this->settings->publicKeyPath->getValue(), null, true)
        );

        return $this->resourceServer;
    }

    private function getAccessTokenTtl(): DateInterval
    {
        $value = (int) $this->settings->accessTokenTtl->getValue();
        $value = $value > 0 ? $value : 3600;
        return new DateInterval(sprintf('PT%dS', $value));
    }

    private function getRefreshTokenTtl(): DateInterval
    {
        $value = (int) $this->settings->refreshTokenTtl->getValue();
        $value = $value > 0 ? $value : 2592000;
        return new DateInterval(sprintf('PT%dS', $value));
    }

    private function getAuthCodeTtl(): DateInterval
    {
        $value = (int) $this->settings->authCodeTtl->getValue();
        $value = $value > 0 ? $value : 600;
        return new DateInterval(sprintf('PT%dS', $value));
    }

    private function getEncryptionKey(): string
    {
        $key = (string) $this->settings->encryptionKey->getValue();
        if ($key === '') {
            throw new \RuntimeException('OAuth2 encryption key is not configured.');
        }

        $decoded = base64_decode($key, true);
        if ($decoded !== false && strlen($decoded) >= 32) {
            return $decoded;
        }

        return $key;
    }
}
