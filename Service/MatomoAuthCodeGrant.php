<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Service;

use DateInterval;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Grant\AuthCodeGrant;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class MatomoAuthCodeGrant extends AuthCodeGrant
{
    public function __construct(
        AuthCodeRepositoryInterface $authCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        DateInterval $authCodeTTL,
        private bool $refreshTokensEnabled
    ) {
        parent::__construct($authCodeRepository, $refreshTokenRepository, $authCodeTTL);
    }

    protected function issueRefreshToken(AccessTokenEntityInterface $accessToken): ?RefreshTokenEntityInterface
    {
        if (!$this->refreshTokensEnabled) {
            return null;
        }

        return parent::issueRefreshToken($accessToken);
    }
}
