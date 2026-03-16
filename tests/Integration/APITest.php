<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\OAuth2\API;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\Repositories\ClientRepository;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
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

    public function test_getClients_returnsPersistedClientsForSuperUsers()
    {
        $created = $this->createConfidentialClient();

        $clients = $this->api->getClients();

        $this->assertCount(1, $clients);
        $this->assertSame($created['client']['client_id'], $clients[0]['client_id']);
        $this->assertSame('Test confidential client', $clients[0]['name']);
        $this->assertSame(['authorization_code', 'refresh_token'], $clients[0]['grant_types']);
    }

    public function test_getScopes_returnsConfiguredScopesForSuperUsers()
    {
        $this->assertSame(
            [
                'matomo:read' => 'Matomo read level access.',
                'matomo:write' => 'Matomo write level access.',
                'matomo:admin' => 'Matomo admin level access.',
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
}

APITest::$fixture = new OAuth2Fixture();
