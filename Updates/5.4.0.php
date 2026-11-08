<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Piwik\Updater;
use Piwik\Updater\Migration\Custom as CustomMigration;
use Piwik\Updates as PiwikUpdates;

/**
 * Writes the static discovery file for existing installs, so clients can discover the
 * authorization server without a web server rewrite rule. Fresh installs get it from
 * OAuth2::activate().
 *
 * Also pauses the clients of users deleted before clients were paused on user deletion, which
 * are otherwise still listed as active although they can no longer authenticate.
 */
class Updates_5_4_0 extends PiwikUpdates
{
    public function getMigrations(Updater $updater): array
    {
        return [
            new CustomMigration([self::class, 'writeDiscoveryFile'], './console oauth2:write-discovery-file'),
            new CustomMigration([self::class, 'pauseClientsWithoutOwner'], './console oauth2:pause-clients-without-owner'),
        ];
    }

    public function doUpdate(Updater $updater): void
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }

    public static function writeDiscoveryFile(): void
    {
        (new OAuth2())->writeDiscoveryFile();
    }

    public static function pauseClientsWithoutOwner(): void
    {
        (new OAuth2())->pauseClientsWithoutOwner();
    }
}
