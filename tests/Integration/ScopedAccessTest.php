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
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use Piwik\Tests\Framework\Fixture;

class WriteAdminCapability extends \Piwik\Access\Capability
{
    public const ID = 'oauth2_test_write_admin_cap';
    public function getId(): string
    {
        return self::ID;
    }
    public function getName(): string
    {
        return 'write admin cap';
    }
    public function getCategory(): string
    {
        return 'test';
    }
    public function getDescription(): string
    {
        return 'lorem ipsum';
    }
    public function getIncludedInRoles(): array
    {
        return [\Piwik\Access\Role\Write::ID, \Piwik\Access\Role\Admin::ID];
    }
}

class AdminOnlyCapability extends \Piwik\Access\Capability
{
    public const ID = 'oauth2_test_admin_only_cap';
    public function getId(): string
    {
        return self::ID;
    }
    public function getName(): string
    {
        return 'admin only cap';
    }
    public function getCategory(): string
    {
        return 'test';
    }
    public function getDescription(): string
    {
        return 'lorem ipsum';
    }
    public function getIncludedInRoles(): array
    {
        return [\Piwik\Access\Role\Admin::ID];
    }
}

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

    public function test_readScopedToken_cannotGrantUserAccessAfterAccessReload()
    {
        $subject = 'oauth_subject';
        $victim = 'oauth_victim';
        $idSite = self::$fixture->idSite;

        $usersManager = UsersManagerAPI::getInstance();
        $usersManager->addUser($subject, 'Password123', 'oauth-subject@example.com', false, $idSite);
        $usersManager->addUser($victim, 'Password123', 'oauth-victim@example.com', false, $idSite);
        $usersManager->setUserAccess($subject, 'admin', [$idSite]);
        $usersManager->setUserAccess($victim, 'view', [$idSite]);

        $this->authenticateReadScopedToken($subject, false, 'UsersManager', 'setUserAccess');

        $this->expectException(\Piwik\NoAccessException::class);

        $usersManager->setUserAccess($victim, 'admin', [$idSite]);
    }

    public function test_readScopedToken_stripsRoleCapabilitiesForNonSuperUserSubject()
    {
        $this->registerTestCapabilities();

        $subject = 'oauth_admin_subject';
        $idSite = self::$fixture->idSite;

        $usersManager = UsersManagerAPI::getInstance();
        $usersManager->addUser($subject, 'Password123', 'oauth-admin-subject@example.com', false, $idSite);
        $usersManager->setUserAccess($subject, 'admin', [$idSite]);

        // A read scoped token confines the admin subject to the view role, so it must
        // keep neither the write-level nor the admin-level capability core seeded from
        // the subject's real admin role.
        $this->authenticateScopedToken($subject, false, ['matomo:read'], 'TagManager', 'getContainer');

        $this->assertEmpty($this->getSitesIdWithCapability(WriteAdminCapability::ID));
        $this->assertEmpty($this->getSitesIdWithCapability(AdminOnlyCapability::ID));
    }

    public function test_writeScopedToken_retainsWriteCapabilityButStripsAdminCapability()
    {
        $this->registerTestCapabilities();

        $subject = 'oauth_admin_subject2';
        $idSite = self::$fixture->idSite;

        $usersManager = UsersManagerAPI::getInstance();
        $usersManager->addUser($subject, 'Password123', 'oauth-admin-subject2@example.com', false, $idSite);
        $usersManager->setUserAccess($subject, 'admin', [$idSite]);

        // A write scoped token keeps capabilities the write role grants, but must drop
        // the admin-only capability even though the subject is an admin.
        $this->authenticateScopedToken($subject, false, ['matomo:write'], 'TagManager', 'getContainer');

        $this->assertSame([$idSite], $this->getSitesIdWithCapability(WriteAdminCapability::ID));
        $this->assertEmpty($this->getSitesIdWithCapability(AdminOnlyCapability::ID));
    }

    private function getSitesIdWithCapability(string $capability): array
    {
        $property = new \ReflectionProperty(\Piwik\Access::class, 'idsitesByAccess');
        $property->setAccessible(true);
        $idsitesByAccess = $property->getValue(\Piwik\Access::getInstance());

        return $idsitesByAccess[$capability] ?? [];
    }

    private function registerTestCapabilities(): void
    {
        Piwik::addAction('Access.Capability.addCapabilities', function (&$capabilities) {
            $capabilities[] = new WriteAdminCapability();
            $capabilities[] = new AdminOnlyCapability();
        });
        \Piwik\Cache::flushAll();
    }

    private function authenticateReadScopedSuperUserToken(string $pluginName, string $methodName): void
    {
        $this->authenticateReadScopedToken(Fixture::ADMIN_USER_LOGIN, true, $pluginName, $methodName);
    }

    private function authenticateReadScopedToken(string $login, bool $isSuperUser, string $pluginName, string $methodName): void
    {
        $this->authenticateScopedToken($login, $isSuperUser, ['matomo:read'], $pluginName, $methodName);
    }

    private function authenticateScopedToken(string $login, bool $isSuperUser, array $scopes, string $pluginName, string $methodName): void
    {
        $auth = new Oauth2Auth(
            $login,
            $isSuperUser,
            'scoped-token',
            'scoped-client',
            $scopes
        );
        StaticContainer::getContainer()->set('Piwik\Auth', $auth);
        $access = \Piwik\Access::getInstance();
        // The test framework leaves the Access singleton in super user mode. For a
        // non-super subject, clear it so reloadAccess derives access from the subject's
        // real role (and seeds role capabilities), as in production. A super subject must
        // keep super access here, as the scope reduction relies on it being applied after.
        if (!$isSuperUser) {
            $access->setSuperUserAccess(false);
        }
        $access->reloadAccess($auth);

        $tokenAuthProperty = new \ReflectionProperty(\Piwik\Access::class, 'token_auth');
        $tokenAuthProperty->setAccessible(true);
        $tokenAuthProperty->setValue($access, 'oauth2:scoped-token');

        $parameters = [];
        (new OAuth2())->onApiRequestDispatch($parameters, $pluginName, $methodName);
    }
}

ScopedAccessTest::$fixture = new OAuth2Fixture();
