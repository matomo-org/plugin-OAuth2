<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Entities;

use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\Traits\EntityTrait;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    use EntityTrait;
    use AccessTokenTrait;
    use TokenEntityTrait;
}
