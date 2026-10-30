<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\OAuth2\Activity\AuthorizeClient;
use Piwik\Plugins\OAuth2\Activity\CreateClient;
use Piwik\Plugins\OAuth2\Activity\DeleteClient;
use Piwik\Plugins\OAuth2\Activity\RotateSecret;
use Piwik\Translation\Translator;

/**
 * @group OAuth2
 * @group ActivityObject
 * @group Plugins
 */
class ActivityObjectTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public function setUp(): void
    {
        if (!class_exists('\\Piwik\\Plugins\\ActivityLog\\Activity\\Activity')) {
            $this->markTestSkipped('The ActivityLog plugin is not installed.');
        }

        parent::setUp();

        StaticContainer::get(Translator::class)->addDirectory(PIWIK_INCLUDE_PATH . '/plugins/OAuth2/lang');
    }

    /**
     * @dataProvider getObjectData
     */
    public function testGetObject(string $class, array $parameters, ?array $expected): void
    {
        $this->assertSame($expected, (new $class())->getObject($parameters));
    }

    public function testGetObjectTypeLabels(): void
    {
        $this->assertSame(['oauth_client' => 'OAuth 2.0 Clients'], (new CreateClient())->getObjectTypeLabels());
    }

    public function getObjectData(): array
    {
        $clientId = 'c0dec0dec0dec0dec0dec0dec0dec0de';

        return [
            'created client' => [
                CreateClient::class,
                ['version' => 'v1', 'client' => ['id' => $clientId, 'name' => 'Demo', 'type' => 'confidential', 'active' => true]],
                ['type' => 'oauth_client', 'id' => $clientId, 'name' => 'Demo', 'context' => ''],
            ],
            'authorization decision' => [
                AuthorizeClient::class,
                ['version' => 'v1', 'client' => ['id' => $clientId, 'name' => 'Demo'], 'decision' => 'allowed'],
                ['type' => 'oauth_client', 'id' => $clientId, 'name' => 'Demo', 'context' => ''],
            ],
            'deleted client, logged without a name' => [
                DeleteClient::class,
                ['version' => 'v1', 'client' => ['id' => $clientId], 'action' => 'deleted', 'deleted' => true],
                ['type' => 'oauth_client', 'id' => $clientId, 'name' => null, 'context' => ''],
            ],
            'empty name' => [
                RotateSecret::class,
                ['version' => 'v1', 'client' => ['id' => $clientId, 'name' => '']],
                ['type' => 'oauth_client', 'id' => $clientId, 'name' => null, 'context' => ''],
            ],
            'non-scalar client id' => [
                CreateClient::class,
                ['version' => 'v1', 'client' => ['id' => [$clientId], 'name' => 'Demo']],
                null,
            ],
            'no client' => [
                CreateClient::class,
                ['version' => 'v1'],
                null,
            ],
        ];
    }
}
