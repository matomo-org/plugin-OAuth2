<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\System\Commands;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Db;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group OAuth2
 * @group PauseClientsWithoutOwner
 * @group Plugins
 */
class PauseClientsWithoutOwnerTest extends ConsoleCommandTestCase
{
    public static $fixture = null;

    public function test_pausesTheClientsOfDeletedUsers_andLeavesTheOthersActive()
    {
        $orphanId = $this->createClientOf('departedUser');
        $keptId = $this->createClientOf('stayingUser');
        // deleting only the row leaves the client as a user deleted before 6.1.0 left it
        Db::query('DELETE FROM ' . Common::prefixTable('user') . ' WHERE login = ?', ['departedUser']);

        $this->assertSame(0, $this->runCommand());
        $this->assertStringContainsString('Paused 1 OAuth 2.0 client(s)', $this->applicationTester->getDisplay());

        $clientModel = StaticContainer::get(ClientModel::class);
        $this->assertSame(0, (int) $clientModel->find($orphanId)['active']);
        $this->assertSame('', $clientModel->find($orphanId)['owner_login']);
        $this->assertSame(1, (int) $clientModel->find($keptId)['active']);

        $this->assertSame(0, $this->runCommand());
        $this->assertStringContainsString('No OAuth 2.0 client has a deleted owner.', $this->applicationTester->getDisplay());
    }

    private function createClientOf(string $login): string
    {
        $userModel = new UserModel();
        if (empty($userModel->getUser($login))) {
            $userModel->addUser($login, 'not-a-real-hash', $login . '@example.org', Date::now()->getDatetime());
        }

        $result = StaticContainer::get(ClientManager::class)->create([
            'name' => 'Client of ' . $login,
            'grant_types' => ['client_credentials'],
            'scopes' => ['matomo:read'],
            'redirect_uris' => [],
            'type' => 'confidential',
            'active' => true,
        ], $login);

        return $result['client']['client_id'];
    }

    private function runCommand(): int
    {
        return $this->applicationTester->run(['command' => 'oauth2:pause-clients-without-owner']);
    }
}

PauseClientsWithoutOwnerTest::$fixture = new OAuth2Fixture();
