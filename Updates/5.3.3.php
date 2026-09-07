<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Piwik\Updater;
use Piwik\Updater\Migration\Factory as MigrationFactory;
use Piwik\Updates as PiwikUpdates;

/**
 * Indexes the columns the user deletion cleanup filters on.
 *
 * Deleting a user removes the access tokens and authorization codes issued to it, which scanned
 * both tables in full as they were only indexed by client. Fresh installs get these indexes from
 * the table definitions in OAuth2::install().
 */
class Updates_5_3_3 extends PiwikUpdates
{
    /**
     * @var MigrationFactory
     */
    private $migration;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
    }

    public function getMigrations(Updater $updater): array
    {
        return [
            $this->migration->db->addIndex('oauth2_access_token', 'user_login', 'idx_oauth2_access_user'),
            $this->migration->db->addIndex('oauth2_auth_code', 'user_login', 'idx_oauth2_authcode_user'),
        ];
    }

    public function doUpdate(Updater $updater): void
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));
    }
}
