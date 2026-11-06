<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Auth\Password;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Db;
use Piwik\Plugins\OAuth2\Model\AccessTokenModel;
use Piwik\Plugins\OAuth2\Model\AuthCodeModel;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Model\RefreshTokenModel;
use Piwik\Piwik;
use Piwik\Plugins\OAuth2\Activity\PauseClientOfDeletedOwner;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Repositories\ClientRepository;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Plugins\UsersManager\UsersManager;
use Piwik\Tests\Framework\Mock\FakeAccess;

/**
 * @group OAuth2
 * @group Plugins
 */
class UserDeletionCleanupTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private const OWNER = 'oauth2_cleanup_owner';
    private const OTHER_OWNER = 'oauth2_cleanup_other_owner';

    private ClientModel $clientModel;
    private AccessTokenModel $accessTokenModel;
    private RefreshTokenModel $refreshTokenModel;
    private AuthCodeModel $authCodeModel;
    private ClientManager $clientManager;
    private UserModel $userModel;

    public function setUp(): void
    {
        parent::setUp();

        $this->clientModel = new ClientModel();
        $this->accessTokenModel = new AccessTokenModel();
        $this->refreshTokenModel = new RefreshTokenModel();
        $this->authCodeModel = new AuthCodeModel();
        $this->clientManager = StaticContainer::get(ClientManager::class);
        $this->userModel = new UserModel();

        FakeAccess::clearAccess(true);
    }

    public function test_deleteUser_pausesTheClientsTheUserOwns_clearsTheirOwner_andRemovesTheirCredentials()
    {
        $this->createUser(self::OWNER);
        $this->createUser(self::OTHER_OWNER);
        $clientId = $this->createClient(self::OWNER);
        $this->issueCredentials($clientId, self::OWNER);
        $this->issueCredentials($clientId, self::OTHER_OWNER);

        UsersManagerAPI::getInstance()->deleteUser(self::OWNER);

        $client = $this->clientModel->find($clientId);
        self::assertNotNull($client);
        self::assertFalse($client['active']);
        self::assertSame('', $client['owner_login']);
        self::assertSame(0, $this->countAccessTokens($clientId));
        self::assertSame(0, $this->countRefreshTokens($clientId));
        self::assertSame(0, $this->countAuthCodes($clientId));
    }

    public function test_pausedClientOfDeletedOwner_staysUnusable_whenTheLoginIsGivenToSomebodyElse()
    {
        $this->createUser(self::OWNER);
        $clientId = $this->createClient(self::OWNER);

        UsersManagerAPI::getInstance()->deleteUser(self::OWNER);
        $this->createUser(self::OWNER);

        $repository = StaticContainer::get(ClientRepository::class);
        self::assertNull($repository->getClientEntity($clientId));
        self::assertFalse($repository->validateClient($clientId, 'any-secret', 'client_credentials'));

        try {
            $this->clientManager->setActive($clientId, true);
            self::fail('A client of a deleted owner must not be resumed');
        } catch (\InvalidArgumentException $e) {
            self::assertSame(Piwik::translate('OAuth2_ClientOwnerDeletedCannotActivate'), $e->getMessage());
        }

        self::assertFalse($this->clientModel->find($clientId)['active']);
    }

    public function test_pausedClientOfDeletedOwner_cannotBeActivatedThroughAnUpdate_butCanBeEditedWhilePaused()
    {
        $this->createUser(self::OWNER);
        $clientId = $this->createClient(self::OWNER);
        UsersManagerAPI::getInstance()->deleteUser(self::OWNER);

        $data = [
            'name' => 'Renamed client',
            'grant_types' => ['authorization_code', 'refresh_token'],
            'scopes' => ['matomo:read'],
            'redirect_uris' => ['https://client.example/callback'],
            'type' => 'confidential',
        ];

        try {
            $this->clientManager->update($clientId, $data + ['active' => '1']);
            self::fail('A client of a deleted owner must not be activated through an update');
        } catch (\InvalidArgumentException $e) {
            self::assertSame(Piwik::translate('OAuth2_ClientOwnerDeletedCannotActivate'), $e->getMessage());
        }

        self::assertFalse($this->clientModel->find($clientId)['active']);

        $this->clientManager->update($clientId, $data + ['active' => '0']);

        $client = $this->clientModel->find($clientId);
        self::assertSame('Renamed client', $client['name']);
        self::assertFalse($client['active']);
        self::assertSame('', $client['owner_login']);
    }

    public function test_pauseClientsWithoutOwner_pausesTheClientsOfAlreadyDeletedUsers_andReportsThem()
    {
        $this->createUser(self::OWNER);
        $this->createUser(self::OTHER_OWNER);
        $orphanId = $this->createClient(self::OWNER);
        $ownedId = $this->createClient(self::OTHER_OWNER);
        $this->issueCredentials($orphanId, self::OTHER_OWNER);

        // a user deleted before clients were paused on deletion left its client active
        $this->deleteUserRowOnly(self::OWNER);

        $posted = [];
        Piwik::addAction('OAuth2.pauseClientOfDeletedOwner.end', function ($activityData) use (&$posted) {
            $posted[] = $activityData;
        });

        (new OAuth2())->pauseClientsWithoutOwner();

        $orphan = $this->clientModel->find($orphanId);
        self::assertFalse($orphan['active']);
        self::assertSame('', $orphan['owner_login']);
        self::assertSame(0, $this->countAccessTokens($orphanId));
        self::assertSame(0, $this->countAuthCodes($orphanId));

        $owned = $this->clientModel->find($ownedId);
        self::assertTrue($owned['active']);
        self::assertSame(self::OTHER_OWNER, $owned['owner_login']);

        self::assertCount(1, $posted);
        self::assertSame($orphanId, $posted[0]['client']['client_id']);
        self::assertSame(self::OWNER, $posted[0]['ownerLogin']);

        // running it again finds nothing left to pause
        $posted = [];
        (new OAuth2())->pauseClientsWithoutOwner();
        self::assertSame([], $posted);
    }

    public function test_deleteUser_removesTheCredentialsIssuedForAnotherUsersClient_butKeepsThatClient()
    {
        $this->createUser(self::OWNER);
        $this->createUser(self::OTHER_OWNER);

        $clientId = $this->createClient(self::OTHER_OWNER);
        $this->issueCredentials($clientId, self::OWNER);
        $this->issueCredentials($clientId, self::OTHER_OWNER);

        UsersManagerAPI::getInstance()->deleteUser(self::OWNER);

        // The client belongs to somebody else and has to survive, only the credentials of the
        // deleted user are removed.
        self::assertNotNull($this->clientModel->find($clientId));
        self::assertSame(0, $this->countAccessTokens($clientId, self::OWNER));
        self::assertSame(0, $this->countAuthCodes($clientId, self::OWNER));
        self::assertSame(1, $this->countAccessTokens($clientId, self::OTHER_OWNER));
        self::assertSame(1, $this->countAuthCodes($clientId, self::OTHER_OWNER));
        self::assertSame(1, $this->countRefreshTokens($clientId));
    }

    public function test_deleteUser_reportsEveryPausedClientToTheActivityLog()
    {
        $this->createUser(self::OWNER);
        $clientId = $this->createClient(self::OWNER);

        $posted = [];
        Piwik::addAction('OAuth2.pauseClientOfDeletedOwner.end', function ($activityData) use (&$posted) {
            $posted[] = $activityData;
        });

        UsersManagerAPI::getInstance()->deleteUser(self::OWNER);

        self::assertCount(1, $posted);
        self::assertSame($clientId, $posted[0]['client']['client_id']);
        self::assertSame(self::OWNER, $posted[0]['ownerLogin']);

        // the secret must never reach a listener or the stored activity
        self::assertArrayNotHasKey('secret_hash', $posted[0]['client']);

        $activity = new PauseClientOfDeletedOwner();
        $extracted = $activity->extractParams([$posted[0]]);

        self::assertSame($clientId, $extracted['client']['id']);
        self::assertTrue($extracted['client']['active'], 'active reports the state before the pause');
        self::assertSame(self::OWNER, $extracted['ownerLogin']);
        self::assertArrayNotHasKey('secret_hash', $extracted['client']);
    }

    public function test_deleteUser_keepsReportingTheRemainingClients_whenAListenerThrows()
    {
        $this->createUser(self::OWNER);
        $clientIds = [
            $this->createClient(self::OWNER),
            $this->createClient(self::OWNER),
            $this->createClient(self::OWNER),
        ];

        $seen = [];
        Piwik::addAction('OAuth2.pauseClientOfDeletedOwner.end', function ($activityData) use (&$seen) {
            $seen[] = $activityData['client']['client_id'];

            // a listener of another plugin failing on the first client must not hide the rest
            if (count($seen) === 1) {
                throw new \Exception('listener failure');
            }
        });

        UsersManagerAPI::getInstance()->deleteUser(self::OWNER);

        sort($clientIds);
        sort($seen);
        self::assertSame($clientIds, $seen);

        // the clients are paused before any event is posted, so a listener can never keep one active
        foreach ($clientIds as $clientId) {
            self::assertFalse($this->clientModel->find($clientId)['active']);
        }
    }

    public function test_deleteUser_succeedsForAUserWithoutAnyOAuthObjects()
    {
        $this->createUser(self::OWNER);

        UsersManagerAPI::getInstance()->deleteUser(self::OWNER);

        self::assertEmpty($this->userModel->getUser(self::OWNER));
    }

    public function test_onUserDeleted_isIdempotent()
    {
        $this->createUser(self::OWNER);
        $clientId = $this->createClient(self::OWNER);
        $this->issueCredentials($clientId, self::OWNER);

        $plugin = new OAuth2();
        $plugin->onUserDeleted(self::OWNER);
        $plugin->onUserDeleted(self::OWNER);

        self::assertFalse($this->clientModel->find($clientId)['active']);
        self::assertSame(0, $this->countAccessTokens($clientId));
    }

    public function test_clientOutlivingItsOwner_isRejectedInEveryGrant()
    {
        $this->createUser(self::OWNER);
        $clientId = $this->createClient(self::OWNER);

        $repository = StaticContainer::get(ClientRepository::class);
        self::assertNotNull($repository->getClientEntity($clientId));

        // Delete the user row without posting the event, standing in for a cleanup that did not
        // complete, so that the client is left behind while its owner is gone.
        $this->deleteUserRowOnly(self::OWNER);

        self::assertNull($repository->getClientEntity($clientId));
        self::assertFalse($repository->validateClient($clientId, 'any-secret', 'client_credentials'));
    }

    public function testClientOutlivingItsOwnerCannotBeActivatedWithAMessageThatDoesNotClaimItWasPaused()
    {
        $this->createUser(self::OWNER);
        $clientId = $this->createClient(self::OWNER);
        $this->deleteUserRowOnly(self::OWNER);

        try {
            $this->clientManager->setActive($clientId, true);
            self::fail('A client whose owner no longer exists must not be activated');
        } catch (\InvalidArgumentException $e) {
            self::assertSame(Piwik::translate('OAuth2_ClientOwnerMissingCannotActivate'), $e->getMessage());
        }
    }

    private function deleteUserRowOnly(string $login): void
    {
        Db::query('DELETE FROM ' . Common::prefixTable('user') . ' WHERE login = ?', [$login]);
    }

    private function createUser(string $login): void
    {
        $passwordHelper = new Password();
        $hashedPassword = $passwordHelper->hash(UsersManager::getPasswordHash('test-password'));

        if (empty($this->userModel->getUser($login))) {
            $this->userModel->addUser($login, $hashedPassword, $login . '@example.org', Date::now()->getDatetime());
        }
    }

    private function createClient(string $ownerLogin): string
    {
        $result = $this->clientManager->create([
            'name' => 'Cleanup client of ' . $ownerLogin,
            'grant_types' => ['authorization_code', 'refresh_token', 'client_credentials'],
            'scopes' => ['matomo:read'],
            'redirect_uris' => ['https://client.example/callback'],
            'type' => 'confidential',
            'active' => true,
        ], $ownerLogin);

        return $result['client']['client_id'];
    }

    private function issueCredentials(string $clientId, string $userLogin): void
    {
        $expiresAt = Date::now()->addHour(1)->getDatetime();
        $accessTokenId = 'at_' . $clientId . '_' . $userLogin;

        $this->accessTokenModel->persist([
            'token_id' => $accessTokenId,
            'user_login' => $userLogin,
            'client_id' => $clientId,
            'scopes' => ['matomo:read'],
            'expires_at' => $expiresAt,
        ]);

        $this->refreshTokenModel->persist([
            'token_id' => 'rt_' . $clientId . '_' . $userLogin,
            'access_token_id' => $accessTokenId,
            'expires_at' => $expiresAt,
        ]);

        $this->authCodeModel->persist([
            'code_id' => 'ac_' . $clientId . '_' . $userLogin,
            'user_login' => $userLogin,
            'client_id' => $clientId,
            'scopes' => ['matomo:read'],
            'redirect_uri' => 'https://client.example/callback',
            'code_challenge' => null,
            'code_challenge_method' => null,
            'expires_at' => $expiresAt,
        ]);
    }

    private function countAccessTokens(string $clientId, ?string $userLogin = null): int
    {
        return $this->countRows('oauth2_access_token', 'client_id', $clientId, $userLogin);
    }

    private function countAuthCodes(string $clientId, ?string $userLogin = null): int
    {
        return $this->countRows('oauth2_auth_code', 'client_id', $clientId, $userLogin);
    }

    private function countRefreshTokens(string $clientId): int
    {
        return (int) Db::fetchOne(
            'SELECT COUNT(*) FROM ' . Common::prefixTable('oauth2_refresh_token') . ' rt
             INNER JOIN ' . Common::prefixTable('oauth2_access_token') . ' at ON rt.access_token_id = at.token_id
             WHERE at.client_id = ?',
            [$clientId]
        );
    }

    private function countRows(string $table, string $column, string $value, ?string $userLogin): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . Common::prefixTable($table) . ' WHERE ' . $column . ' = ?';
        $bind = [$value];

        if (null !== $userLogin) {
            $sql .= ' AND user_login = ?';
            $bind[] = $userLogin;
        }

        return (int) Db::fetchOne($sql, $bind);
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}

UserDeletionCleanupTest::$fixture = new OAuth2Fixture();
