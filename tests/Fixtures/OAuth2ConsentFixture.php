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
    public const MULTI_SCOPE_CLIENT_ID = '11111111111111111111111111111111';
    public const SINGLE_SCOPE_CLIENT_ID = '22222222222222222222222222222222';
    public const REDIRECT_URI = 'https://client.example/callback';

    public function setUp(): void
    {
        parent::setUp();

        OAuth2::setupRSAKeys(true);
        OAuth2::setEncryptionKey(true);

        $this->createClient(self::MULTI_SCOPE_CLIENT_ID, 'Multi scope UI client', ['matomo:read', 'matomo:write', 'matomo:admin']);
        $this->createClient(self::SINGLE_SCOPE_CLIENT_ID, 'Single scope UI client', ['matomo:read']);
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
