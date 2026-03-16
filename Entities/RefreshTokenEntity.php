<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Entities;

use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\Traits\EntityTrait;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

class RefreshTokenEntity implements RefreshTokenEntityInterface
{
    use EntityTrait;
    use RefreshTokenTrait;
}
