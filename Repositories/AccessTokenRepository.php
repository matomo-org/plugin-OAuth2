<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Repositories;

use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\ClientEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\ScopeEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Piwik\Plugins\OAuth2\Entities\AccessTokenEntity;
use Piwik\Plugins\OAuth2\Entities\ClientEntity;
use Piwik\Plugins\OAuth2\Model\AccessTokenModel;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    private AccessTokenModel $model;

    public function __construct(AccessTokenModel $model)
    {
        $this->model = $model;
    }

    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        string|null $userIdentifier = null
    ): AccessTokenEntityInterface {
        $token = new AccessTokenEntity();
        $token->setIdentifier($this->generateIdentifier());
        $token->setClient($clientEntity);
        if (empty($userIdentifier) && $clientEntity instanceof ClientEntity && !empty($clientEntity->ownerLogin)) {
            $userIdentifier = $clientEntity->ownerLogin;
        }

        if (!empty($userIdentifier)) {
            $token->setUserIdentifier($userIdentifier);
        }

        /** @var ScopeEntityInterface $scope */
        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }

        return $token;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $this->model->persist([
            'token_id' => $accessTokenEntity->getIdentifier(),
            'user_login' => $accessTokenEntity->getUserIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => array_map(static function (ScopeEntityInterface $scope) {
                return $scope->getIdentifier();
            }, $accessTokenEntity->getScopes()),
            'expires_at' => $accessTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
        ]);
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $this->model->revoke($tokenId);
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        return $this->model->isRevoked($tokenId);
    }

    private function generateIdentifier(): string
    {
        return bin2hex(random_bytes(16));
    }
}
