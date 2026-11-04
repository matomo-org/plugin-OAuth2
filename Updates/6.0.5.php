<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;
use Piwik\Updater;
use Piwik\Updater\Migration\Custom as CustomMigration;
use Piwik\Updates as PiwikUpdates;

/**
 * Writes the static discovery file for existing installs, so clients can discover the
 * authorization server without a web server rewrite rule. Fresh installs get it from
 * OAuth2::activate().
 */
class Updates_6_0_5 extends PiwikUpdates
{
    public function getMigrations(Updater $updater): array
    {
        return [
            new CustomMigration([self::class, 'writeDiscoveryFile'], './console oauth2:write-discovery-file'),
        ];
    }

    public function doUpdate(Updater $updater): void
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }

    public static function writeDiscoveryFile(): void
    {
        StaticContainer::get(DiscoveryFile::class)->tryWrite();
    }
}
