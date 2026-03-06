<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Access;

use Piwik\Access;

class OAuth2Access extends Access
{
    public static function loadSitesIfNeededFor(Access &$access): void
    {
        $method = new \ReflectionMethod(Access::class, 'loadSitesIfNeeded');
        $method->setAccessible(true);
        $method->invoke($access);
    }

    public static function getSiteAccessFor(Access $access): array
    {
        $property = new \ReflectionProperty(Access::class, 'idsitesByAccess');
        $property->setAccessible(true);
        return (array) $property->getValue($access);
    }

    public static function setSiteAccessFor(Access $access, array $siteAccess): void
    {
        $property = new \ReflectionProperty(Access::class, 'idsitesByAccess');
        $property->setAccessible(true);
        $property->setValue($access, $siteAccess);
    }
}
