<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugins\OAuth2\API;
use Piwik\Plugins\OAuth2\Auth\Oauth2Auth;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Tests\Framework\Fixture;

/**
 * @group OAuth2
 * @group ScopedAccess
 * @group Plugins
 */
class ScopedAccessTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private API $api;

    public function setUp(): void
    {
        parent::setUp();

        OAuth2::setupRSAKeys(true);
        OAuth2::setEncryptionKey(true);

        $this->api = API::getInstance();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_readScopedToken_cannotPerformWriteLevelChecksWhenTokenRepresentsSuperUser()
    {
        $this->authenticateReadScopedSuperUserToken('Annotations', 'add');

        $this->assertFalse(\Piwik\Access::getInstance()->hasSuperUserAccess());

        $this->expectException(\Piwik\NoAccessException::class);

        Piwik::checkUserHasSomeWriteAccess();
    }

    public function test_readScopedToken_cannotPerformSuperUserActionsWhenTokenRepresentsSuperUser()
    {
        $this->authenticateReadScopedSuperUserToken('OAuth2', 'getClients');

        $this->assertFalse(\Piwik\Access::getInstance()->hasSuperUserAccess());

        $this->expectException(\Piwik\NoAccessException::class);

        $this->api->getClients();
    }

    private function authenticateReadScopedSuperUserToken(string $pluginName, string $methodName): void
    {
        $auth = new Oauth2Auth(
            Fixture::ADMIN_USER_LOGIN,
            true,
            'scoped-token',
            'scoped-client',
            ['matomo:read']
        );
        StaticContainer::getContainer()->set('Piwik\Auth', $auth);
        $access = \Piwik\Access::getInstance();
        $access->reloadAccess($auth);

        $tokenAuthProperty = new \ReflectionProperty(\Piwik\Access::class, 'token_auth');
        $tokenAuthProperty->setAccessible(true);
        $tokenAuthProperty->setValue($access, 'oauth2:scoped-token');

        $parameters = [];
        (new OAuth2())->onApiRequestDispatch($parameters, $pluginName, $methodName);
    }
}

ScopedAccessTest::$fixture = new OAuth2Fixture();
