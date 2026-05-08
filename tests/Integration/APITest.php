<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Auth\Password;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\OAuth2\API;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Repositories\ClientRepository;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Plugins\UsersManager\UsersManager;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;

/**
 * @group OAuth2
 * @group API
 * @group Plugins
 */
class APITest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private API $api;
    private ClientModel $clientModel;
    private ClientRepository $clientRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->api = API::getInstance();
        $this->clientModel = new ClientModel();
        $this->clientRepository = StaticContainer::get(ClientRepository::class);
        $this->setSuperUser();
        Fixture::loadAllTranslations();
    }

    /**
     * @dataProvider getSuperUserOnlyReadMethods
     */
    public function test_superUserOnlyReadActions_failForNonSuperUsers(string $method, array $arguments = [])
    {
        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess');

        $this->setRegularUser();
        $this->api->{$method}(...$arguments);
    }

    public function getSuperUserOnlyReadMethods(): array
    {
        return [
            ['getClients'],
            ['getClient', ['0123456789abcdef0123456789abcdef']],
            ['getScopes'],
        ];
    }

    public function test_createClient_failsForNonSuperUsers()
    {
        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess');

        $this->setRegularUser();
        $this->api->createClient(
            'Regular user client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://client.example/callback']
        );
    }

    public function test_rotateSecret_failsForNonSuperUsers()
    {
        $client = $this->createConfidentialClient();

        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess');

        $this->setRegularUser();
        $this->api->rotateSecret($client['client']['client_id']);
    }

    public function test_deleteClient_failsForNonSuperUsers()
    {
        $client = $this->createConfidentialClient();

        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess');

        $this->setRegularUser();
        $this->api->deleteClient($client['client']['client_id']);
    }

    public function test_setClientActive_failsForNonSuperUsers()
    {
        $client = $this->createConfidentialClient();

        $this->expectException(\Piwik\NoAccessException::class);
        $this->expectExceptionMessage('checkUserHasSuperUserAccess');

        $this->setRegularUser();
        $this->api->setClientActive($client['client']['client_id'], '0');
    }

    public function test_getClients_returnsPersistedClientsForSuperUsers()
    {
        $created = $this->createConfidentialClient();

        $clients = $this->api->getClients();

        $this->assertCount(1, $clients);
        $this->assertSame($created['client']['client_id'], $clients[0]['client_id']);
        $this->assertSame('Test confidential client', $clients[0]['name']);
        $this->assertSame(['authorization_code', 'refresh_token'], $clients[0]['grant_types']);
    }

    public function test_getClient_returnsPersistedClientForSuperUsers()
    {
        $created = $this->createConfidentialClient();

        $client = $this->api->getClient($created['client']['client_id']);

        $this->assertSame($created['client']['client_id'], $client['client_id']);
        $this->assertSame('Test confidential client', $client['name']);
    }

    public function test_getScopes_returnsConfiguredScopesForSuperUsers()
    {
        $this->assertSame(
            [
                'matomo:read' => 'Matomo read level access',
                'matomo:write' => 'Matomo write level access',
                'matomo:admin' => 'Matomo admin level access',
            ],
            $this->api->getScopes()
        );
    }

    public function test_createClient_returnsSecretOnceForConfidentialClients()
    {
        $result = $this->createConfidentialClient();

        $this->assertSame('confidential', $result['client']['type']);
        $this->assertNotEmpty($result['secret']);
        $this->assertTrue(
            $this->clientRepository->validateClient(
                $result['client']['client_id'],
                $result['secret'],
                'authorization_code'
            )
        );
    }

    public function test_createClient_doesNotReturnSecretForPublicClients()
    {
        $result = $this->api->createClient(
            'Test public client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://public-client.example/callback'],
            'Public client description',
            'public'
        );

        $stored = $this->clientModel->find($result['client']['client_id']);

        $this->assertSame('public', $result['client']['type']);
        $this->assertNull($result['secret']);
        $this->assertNotNull($stored);
        $this->assertNull($stored['secret_hash']);
    }

    public function test_createClient_rejectsPublicClientCredentialsGrant()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Public clients cannot use the client_credentials grant type');

        $this->api->createClient(
            'Invalid public client',
            ['client_credentials'],
            'matomo:read',
            [],
            '',
            'public'
        );
    }

    /**
     * @dataProvider getInvalidRedirectUris
     */
    public function test_createClient_rejectsInvalidRedirectUris($redirectUris)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid redirect_uri');

        $this->api->createClient(
            'Invalid redirect client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            $redirectUris,
            'Invalid redirect test',
            'confidential'
        );
    }

    public function test_createClient_allowsRedirectUriWithUrlInQueryString()
    {
        $result = $this->api->createClient(
            'Valid redirect client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://example.com/callback?redirect=https://other.example/path'],
            'Valid redirect test',
            'confidential'
        );

        $this->assertSame(
            ['https://example.com/callback?redirect=https://other.example/path'],
            $result['client']['redirect_uris']
        );
    }

    public function test_rotateSecret_invalidatesPreviousSecret()
    {
        $result = $this->createConfidentialClient();

        $rotated = $this->api->rotateSecret($result['client']['client_id']);

        $this->assertSame($result['client']['client_id'], $rotated['client_id']);
        $this->assertNotSame($result['secret'], $rotated['secret']);
        $this->assertFalse(
            $this->clientRepository->validateClient(
                $result['client']['client_id'],
                $result['secret'],
                'authorization_code'
            )
        );
        $this->assertTrue(
            $this->clientRepository->validateClient(
                $result['client']['client_id'],
                $rotated['secret'],
                'authorization_code'
            )
        );
    }

    public function test_deleteClient_removesPersistedClient()
    {
        $result = $this->createConfidentialClient();

        $deleted = $this->api->deleteClient($result['client']['client_id']);

        $this->assertSame(['deleted' => true], $deleted);
        $this->assertNull($this->clientModel->find($result['client']['client_id']));
    }

    public function test_setClientActive_updatesPersistedClientStatus()
    {
        $result = $this->createConfidentialClient();

        $paused = $this->api->setClientActive($result['client']['client_id'], '0');
        $this->assertFalse($paused['client']['active']);

        $resumed = $this->api->setClientActive($result['client']['client_id'], '1');
        $this->assertTrue($resumed['client']['active']);
    }

    public function test_updateClient_updatesPersistedClient()
    {
        $result = $this->createConfidentialClient();

        $updated = $this->api->updateClient(
            $result['client']['client_id'],
            'Updated client',
            ['authorization_code', 'refresh_token'],
            'matomo:write',
            ['https://updated.example/callback'],
            'Updated description',
            'confidential',
            '0'
        );

        $this->assertSame('Updated client', $updated['client']['name']);
        $this->assertSame('Updated description', $updated['client']['description']);
        $this->assertSame(['matomo:write'], $updated['client']['scopes']);
        $this->assertSame(['https://updated.example/callback'], $updated['client']['redirect_uris']);
        $this->assertFalse($updated['client']['active']);
        $this->assertNull($updated['secret']);
    }

    public function test_updateClient_preservesExistingOwnerWhenEditedByAnotherSuperUser()
    {
        $result = $this->createConfidentialClient();
        $this->createSuperUser('otherSuperUser');

        FakeAccess::clearAccess(true, [], [], 'otherSuperUser');

        $updated = $this->api->updateClient(
            $result['client']['client_id'],
            'Updated client',
            ['authorization_code', 'refresh_token'],
            'matomo:write',
            ['https://updated.example/callback'],
            'Updated description',
            'confidential',
            '1'
        );

        $this->assertSame(Fixture::ADMIN_USER_LOGIN, $updated['client']['owner_login']);
        $this->assertSame(
            Fixture::ADMIN_USER_LOGIN,
            $this->clientModel->find($result['client']['client_id'])['owner_login']
        );
    }

    public function test_updateClient_generatesSecretWhenChangingPublicClientToConfidential()
    {
        $result = $this->api->createClient(
            'Test public client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://public-client.example/callback'],
            'Public client description',
            'public'
        );

        $updated = $this->api->updateClient(
            $result['client']['client_id'],
            'Updated public client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://public-client.example/callback'],
            'Updated public client description',
            'confidential'
        );

        $this->assertSame('confidential', $updated['client']['type']);
        $this->assertNotEmpty($updated['secret']);
        $this->assertTrue(
            $this->clientRepository->validateClient(
                $result['client']['client_id'],
                $updated['secret'],
                'authorization_code'
            )
        );
    }

    public function test_updateClient_clearsSecretWhenChangingConfidentialClientToPublic()
    {
        $result = $this->createConfidentialClient();

        $updated = $this->api->updateClient(
            $result['client']['client_id'],
            'Now public client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://client.example/callback'],
            'Now public client description',
            'public'
        );

        $stored = $this->clientModel->find($result['client']['client_id']);

        $this->assertSame('public', $updated['client']['type']);
        $this->assertNull($updated['secret']);
        $this->assertNotNull($stored);
        $this->assertNull($stored['secret_hash']);
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }

    private function setSuperUser(): void
    {
        FakeAccess::clearAccess(true);
    }

    public function getInvalidRedirectUris(): array
    {
        return [
            ["https://example.com/callback\thttps://example.com/other"],
            ["https://example.com/callback https://example.com/other"],
            ["https://example.com/callback\\t"],
            ["https://example.com/callback\\n"],
            ["https://example.com/callbackhttps://example.org/other"],
            ["https://example.com/callback,https://example.org/other"],
            [" https://example.com/callback"],
            ["https://example.com/callback "],
            [["https://example.com/callback\t"]],
            [["https://example.com/callback\\t"]],
            [["https://example.com/callback https://example.com/other"]],
            [["https://example.com/callbackhttps://example.org/other"]],
        ];
    }

    private function setRegularUser(): void
    {
        FakeAccess::clearAccess(false);
        FakeAccess::$identity = 'regularUser';
        FakeAccess::$idSitesView = [1];
    }

    private function createConfidentialClient(): array
    {
        return $this->api->createClient(
            'Test confidential client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://client.example/callback'],
            'Confidential client description',
            'confidential'
        );
    }

    private function createSuperUser(string $login): void
    {
        $userModel = new UserModel();
        $passwordHelper = new Password();
        $hashedPassword = $passwordHelper->hash(UsersManager::getPasswordHash('test-password'));

        if (empty($userModel->getUser($login))) {
            $userModel->addUser($login, $hashedPassword, $login . '@example.org', Date::now()->getDatetime());
        } else {
            $userModel->updateUser($login, $hashedPassword, $login . '@example.org');
        }

        $userModel->setSuperUserAccess($login, true);
    }
}

APITest::$fixture = new OAuth2Fixture();
