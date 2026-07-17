<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Access;
use Piwik\API\Request;
use Piwik\Auth\Password;
use Piwik\AuthResult;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Plugins\OAuth2\Auth\Oauth2Auth;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Plugins\UsersManager\UsersManager;
use Piwik\Settings\Storage\UserScopedSettingsAccessManager;
use Piwik\Tests\Framework\Fixture;

class CreateAppSpecificTokenGuardTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private const ATTACKER_LOGIN = 'oauth_lowpriv_attacker';
    private const VICTIM_LOGIN = 'oauth_victim_superuser';

    public function setUp(): void
    {
        parent::setUp();

        OAuth2::setupRSAKeys(true);
        OAuth2::setEncryptionKey(true);
        Fixture::loadAllTranslations();

        $userModel = new UserModel();

        if (empty($userModel->getUser(self::ATTACKER_LOGIN))) {
            UsersManagerAPI::getInstance()->addUser(
                self::ATTACKER_LOGIN,
                'Password123',
                'oauth-attacker@example.com',
                false,
                self::$fixture->idSite
            );
        }

        if (empty($userModel->getUser(self::VICTIM_LOGIN))) {
            $hashedPassword = (new Password())->hash(UsersManager::getPasswordHash('VictimPassword123'));
            $userModel->addUser(
                self::VICTIM_LOGIN,
                $hashedPassword,
                'oauth-victim@example.com',
                Date::now()->getDatetime()
            );
            $userModel->setSuperUserAccess(self::VICTIM_LOGIN, true);
        }
    }

    public function test_oauth2Auth_refusesToAuthenticateADifferentIdentity()
    {
        $auth = new Oauth2Auth(self::ATTACKER_LOGIN, false, 'token-id', 'client-id', ['matomo:read']);

        $auth->setLogin(Fixture::ADMIN_USER_LOGIN);
        $auth->setPasswordHash(null);
        $auth->setPassword('definitely-wrong-password');
        $auth->setTokenAuth(null);

        $result = $auth->authenticate();

        $this->assertFalse($result->wasAuthenticationSuccessful());
        $this->assertSame(AuthResult::FAILURE, $result->getCode());
    }

    public function test_oauth2Auth_authenticatesOwnSubject_whenNoPasswordSupplied()
    {
        $auth = new Oauth2Auth(self::ATTACKER_LOGIN, false, 'token-id', 'client-id', ['matomo:read']);

        $result = $auth->authenticate();

        $this->assertTrue($result->wasAuthenticationSuccessful());
        $this->assertSame(self::ATTACKER_LOGIN, $result->getIdentity());
    }

    public function test_oauth2Auth_refusesPasswordVerification_forOwnSubject()
    {
        $auth = new Oauth2Auth(self::ATTACKER_LOGIN, false, 'token-id', 'client-id', ['matomo:read']);

        $auth->setPassword('definitely-wrong-password');

        $result = $auth->authenticate();

        $this->assertFalse($result->wasAuthenticationSuccessful());
        $this->assertSame(AuthResult::FAILURE, $result->getCode());
    }

    public function test_guard_blocksCreateAppSpecificTokenAuth_whenAuthenticatedViaOauth2()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(Piwik::translate('OAuth2_CreateAppSpecificTokenAuthBlocked'));

        $parameters = ['userLogin' => Fixture::ADMIN_USER_LOGIN];
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'createAppSpecificTokenAuth');
    }

    public function test_guard_blocksSetUserPreference_whenAuthenticatedViaOauth2()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(Piwik::translate('OAuth2_SetUserPreferenceBlocked'));

        $parameters = ['userLogin' => self::ATTACKER_LOGIN];
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'setUserPreference');
    }

    public function test_guard_blocksInitUserPreferenceWithDefault_whenAuthenticatedViaOauth2()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(Piwik::translate('OAuth2_SetUserPreferenceBlocked'));

        $parameters = ['userLogin' => self::ATTACKER_LOGIN];
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'initUserPreferenceWithDefault');
    }

    public function test_guard_allowsSetUserPreference_whenWriteScope()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:write']);

        // A write-scope token may set its own preferences - the guard must not fire.
        $parameters = ['userLogin' => self::ATTACKER_LOGIN];
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'setUserPreference');

        $this->assertTrue(true);
    }

    public function test_guard_allowsOtherUsersManagerMethods_whenAuthenticatedViaOauth2()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $parameters = [];
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'getUsers');

        $this->assertTrue(true);
    }

    public function test_guard_allowsReadingOwnPreference_whenAuthenticatedViaOauth2()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $parameters = ['userLogin' => self::ATTACKER_LOGIN];
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'getUserPreference');

        $this->assertTrue(true);
    }

    public function test_endToEnd_readScopeToken_cannotChangeOwnPreference()
    {
        UsersManagerAPI::getInstance()->setUserPreference(
            self::ATTACKER_LOGIN,
            UsersManagerAPI::PREFERENCE_DEFAULT_REPORT,
            'MultiSites'
        );

        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $threw = false;
        try {
            Request::processRequest('UsersManager.setUserPreference', [
                'userLogin'       => self::ATTACKER_LOGIN,
                'preferenceName'  => UsersManagerAPI::PREFERENCE_DEFAULT_REPORT,
                'preferenceValue' => 'Live',
            ], []);
        } catch (\Exception $e) {
            $threw = true;
            $this->assertStringContainsString(
                Piwik::translate('OAuth2_SetUserPreferenceBlocked'),
                $e->getMessage()
            );
        }

        $this->assertTrue($threw, 'Expected the dispatch guard to reject the request');

        $stored = StaticContainer::get(UserScopedSettingsAccessManager::class)
            ->get('UsersManager', self::ATTACKER_LOGIN, UsersManagerAPI::PREFERENCE_DEFAULT_REPORT, false);
        $this->assertSame('MultiSites', $stored);
    }

    public function test_guard_doesNotBlock_whenNotAuthenticatedViaOauth2()
    {
        $access = Access::getInstance();
        $tokenAuthProperty = new \ReflectionProperty(Access::class, 'token_auth');
        $tokenAuthProperty->setAccessible(true);
        $tokenAuthProperty->setValue($access, 'some-regular-token');

        $parameters = ['userLogin' => Fixture::ADMIN_USER_LOGIN];
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'createAppSpecificTokenAuth');

        $this->assertTrue(true);
    }

    public function test_endToEnd_oauth2BearerRequest_cannotMintTokenForSuperUser()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $threw = false;
        try {
            Request::processRequest('UsersManager.createAppSpecificTokenAuth', [
                'userLogin'            => self::VICTIM_LOGIN,
                'passwordConfirmation' => 'this-is-not-the-victims-password',
                'description'          => 'pwned',
            ], []);
        } catch (\Exception $e) {
            $threw = true;
            $this->assertStringContainsString(
                Piwik::translate('OAuth2_CreateAppSpecificTokenAuthBlocked'),
                $e->getMessage()
            );
        }

        $this->assertTrue($threw, 'Expected the dispatch guard to reject the request');
        $this->assertNoTokenExistsFor(self::VICTIM_LOGIN);
    }

    public function test_endToEnd_bulkRequestChild_cannotMintTokenForSuperUser()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $childUrl = 'method=UsersManager.createAppSpecificTokenAuth'
            . '&userLogin=' . urlencode(self::VICTIM_LOGIN)
            . '&passwordConfirmation=' . urlencode('this-is-not-the-victims-password')
            . '&description=pwned';

        try {
            Request::processRequest('API.getBulkRequest', [
                'urls' => [$childUrl],
            ], []);
        } catch (\Exception $e) {
            // acceptable - the important assertion is that no token was created
        }

        $this->assertNoTokenExistsFor(self::VICTIM_LOGIN);
    }

    public function test_endToEnd_superUserScopeToken_cannotGrantSuperUserWithoutPassword()
    {
        $this->installOauth2Context(self::VICTIM_LOGIN, true, ['matomo:superuser']);

        $threw = false;
        try {
            Request::processRequest('UsersManager.setSuperUserAccess', [
                'userLogin'            => self::ATTACKER_LOGIN,
                'hasSuperUserAccess'   => 1,
                'passwordConfirmation' => 'not-the-victims-password',
            ], []);
        } catch (\Exception $e) {
            $threw = true;
            $this->assertStringContainsString(
                Piwik::translate('UsersManager_CurrentPasswordNotCorrect'),
                $e->getMessage()
            );
        }

        $this->assertTrue($threw, 'Expected the password step-up to reject the request');
        $this->assertFalse(
            (new UserModel())->getUser(self::ATTACKER_LOGIN)['superuser_access'] == 1,
            'Attacker must not have been granted super user access'
        );
    }

    private function installOauth2Context(string $login, bool $isSuperUser, array $scopes): void
    {
        $auth = new Oauth2Auth($login, $isSuperUser, 'scoped-token', 'scoped-client', $scopes);
        StaticContainer::getContainer()->set('Piwik\Auth', $auth);

        $access = Access::getInstance();
        if (!$isSuperUser) {
            $access->setSuperUserAccess(false);
        }
        $access->reloadAccess($auth);

        $tokenAuthProperty = new \ReflectionProperty(Access::class, 'token_auth');
        $tokenAuthProperty->setAccessible(true);
        $tokenAuthProperty->setValue($access, 'oauth2:scoped-token');
    }

    private function assertNoTokenExistsFor(string $login): void
    {
        $this->assertEmpty(
            $this->hashedTokensFor($login),
            'A token_auth was created for ' . $login . ' - privilege escalation guard failed'
        );
    }

    private function hashedTokensFor(string $login): array
    {
        return (new UserModel())->getAllHashedTokensForLogins([$login]);
    }
}

CreateAppSpecificTokenGuardTest::$fixture = new OAuth2Fixture();
