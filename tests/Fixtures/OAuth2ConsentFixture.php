<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Fixtures;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Tests\Framework\Fixture;

class OAuth2ConsentFixture extends OAuth2Fixture
{
    public const ADMIN_SCOPE_CLIENT_ID = '11111111111111111111111111111111';
    public const READ_SCOPE_CLIENT_ID = '22222222222222222222222222222222';
    public const REDIRECT_URI = 'https://client.example/callback';

    public function setUp(): void
    {
        parent::setUp();

        OAuth2::setupRSAKeys(true);
        OAuth2::setEncryptionKey(true);

        // a client is configured with a single scope, as that is all the API accepts, and the
        // consent screen then offers every scope up to that maximum
        $this->createClient(self::ADMIN_SCOPE_CLIENT_ID, 'Admin scope UI client', ['matomo:admin']);
        $this->createClient(self::READ_SCOPE_CLIENT_ID, 'Read scope UI client', ['matomo:read']);
    }

    private function createClient(string $clientId, string $name, array $scopes): void
    {
        if (!empty(StaticContainer::get(ClientModel::class)->find($clientId))) {
            return;
        }

        StaticContainer::get(ClientManager::class)->create([
            'client_id' => $clientId,
            'name' => $name,
            'description' => $name . ' for consent screen UI tests',
            'redirect_uris' => [self::REDIRECT_URI],
            'grant_types' => ['authorization_code'],
            'scopes' => $scopes,
            'type' => 'confidential',
            'active' => true,
        ], Fixture::ADMIN_USER_LOGIN);
    }
}
