<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Repositories;

use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Piwik\Plugins\OAuth2\Entities\RefreshTokenEntity;
use Piwik\Plugins\OAuth2\Model\RefreshTokenModel;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private RefreshTokenModel $model;

    public function __construct(RefreshTokenModel $model)
    {
        $this->model = $model;
    }

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        $token = new RefreshTokenEntity();
        $token->setIdentifier($this->generateIdentifier());
        return $token;
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $this->model->persist([
            'token_id' => $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'expires_at' => $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
        ]);
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $this->model->revoke($tokenId);
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        return $this->model->isRevoked($tokenId);
    }

    private function generateIdentifier(): string
    {
        return bin2hex(random_bytes(16));
    }
}
