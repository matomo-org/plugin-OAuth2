<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Fixtures;

use Piwik\Access;
use Piwik\Auth\Password;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use Piwik\Plugins\UsersManager\Model as UsersModel;
use Piwik\Plugins\UsersManager\UsersManager;

/**
 * One client owned by a user who has since been deleted, next to one that is still active.
 */
class OAuth2DeletedOwnerFixture extends OAuth2Fixture
{
    public const DELETED_OWNER_CLIENT_ID = '33333333333333333333333333333333';
    public const ACTIVE_CLIENT_ID = '44444444444444444444444444444444';
    private const DELETED_OWNER = 'departed_owner';

    public function setUp(): void
    {
        parent::setUp();

        $this->createClient(self::ACTIVE_CLIENT_ID, 'Active UI client', self::ADMIN_USER_LOGIN);

        if (!empty(StaticContainer::get(ClientModel::class)->find(self::DELETED_OWNER_CLIENT_ID))) {
            return;
        }

        $passwordHash = (new Password())->hash(UsersManager::getPasswordHash('test-password'));
        StaticContainer::get(UsersModel::class)->addUser(
            self::DELETED_OWNER,
            $passwordHash,
            self::DELETED_OWNER . '@example.org',
            Date::now()->getDatetime()
        );
        $this->createClient(self::DELETED_OWNER_CLIENT_ID, 'Deleted owner UI client', self::DELETED_OWNER);

        Access::doAsSuperUser(function () {
            UsersManagerAPI::getInstance()->deleteUser(self::DELETED_OWNER);
        });
    }

    private function createClient(string $clientId, string $name, string $ownerLogin): void
    {
        if (!empty(StaticContainer::get(ClientModel::class)->find($clientId))) {
            return;
        }

        StaticContainer::get(ClientManager::class)->create([
            'client_id' => $clientId,
            'name' => $name,
            'description' => $name . ' for the deleted owner UI test',
            'redirect_uris' => ['https://client.example/callback'],
            'grant_types' => ['authorization_code'],
            'scopes' => ['matomo:read'],
            'type' => 'confidential',
            'active' => true,
        ], $ownerLogin);
    }
}
