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
use Piwik\Tests\Framework\Fixture;

/**
 * Regression tests for the OAuth2 privilege-escalation guard on
 * UsersManager.createAppSpecificTokenAuth.
 *
 * Oauth2Auth authenticates the bearer of an OAuth2 token; its password setters are no-ops
 * and authenticate() always succeeds for whatever login is set on it. Because
 * PasswordVerifier::isPasswordCorrect() reuses the globally installed Piwik\Auth adapter,
 * an OAuth2-authenticated request could otherwise mint a full-access token_auth for ANY
 * account through createAppSpecificTokenAuth (whose only authorization gate is that password
 * confirmation). The plugin blocks that method under Oauth2Auth via its API.Request.dispatch
 * listener; these tests pin that behaviour down.
 *
 * @group OAuth2
 * @group Plugins
 */
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

        // Low-privilege attacker, holder of the OAuth2 token.
        if (empty($userModel->getUser(self::ATTACKER_LOGIN))) {
            UsersManagerAPI::getInstance()->addUser(
                self::ATTACKER_LOGIN,
                'Password123',
                'oauth-attacker@example.com',
                false,
                self::$fixture->idSite
            );
        }

        // Victim super user, deliberately created WITHOUT any app-specific token so that any
        // token found for it can only have been minted by the request under test.
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

    /**
     * The adapter must refuse to authenticate any identity other than the token's real subject,
     * even when driven through the exact setter sequence PasswordVerifier performs. This closes
     * the identity-switch that let an OAuth2 request authenticate as an arbitrary account.
     */
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

    /**
     * For its own subject the adapter still succeeds without verifying the password (it
     * authenticates the bearer of the token, not a password). This is why the dispatch guard on
     * createAppSpecificTokenAuth remains necessary even after the identity check above.
     */
    public function test_oauth2Auth_authenticatesOwnSubjectRegardlessOfPassword()
    {
        $auth = new Oauth2Auth(self::ATTACKER_LOGIN, false, 'token-id', 'client-id', ['matomo:read']);

        $auth->setPassword('definitely-wrong-password');

        $result = $auth->authenticate();

        $this->assertTrue($result->wasAuthenticationSuccessful());
        $this->assertSame(self::ATTACKER_LOGIN, $result->getIdentity());
    }

    public function test_guard_blocksCreateAppSpecificTokenAuth_whenAuthenticatedViaOauth2()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(Piwik::translate('OAuth2_CreateAppSpecificTokenAuthBlocked'));

        $parameters = ['userLogin' => Fixture::ADMIN_USER_LOGIN];
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'createAppSpecificTokenAuth');
    }

    public function test_guard_allowsOtherUsersManagerMethods_whenAuthenticatedViaOauth2()
    {
        $this->installOauth2Context(self::ATTACKER_LOGIN, false, ['matomo:read']);

        $parameters = [];
        // Must not throw for any method other than createAppSpecificTokenAuth.
        (new OAuth2())->onApiRequestDispatch($parameters, 'UsersManager', 'getUsers');

        $this->assertTrue(true);
    }

    public function test_guard_doesNotBlock_whenNotAuthenticatedViaOauth2()
    {
        // No OAuth2 token on the Access singleton: the guard must not interfere with the
        // normal password-authenticated flow.
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

        // getBulkRequest swallows per-child exceptions into the response, so we assert on the
        // security property directly: no token_auth row must exist for the victim.
        try {
            Request::processRequest('API.getBulkRequest', [
                'urls' => [$childUrl],
            ], []);
        } catch (\Exception $e) {
            // acceptable - the important assertion is that no token was created
        }

        $this->assertNoTokenExistsFor(self::VICTIM_LOGIN);
    }

    /**
     * Positive control: the guard only fires under Oauth2Auth. Genuine password authentication
     * with the correct password must still create a token for the caller's own account.
     */
    public function test_passwordAuth_withCorrectPassword_stillCreatesToken()
    {
        Access::getInstance()->setSuperUserAccess(true);

        $token = UsersManagerAPI::getInstance()->createAppSpecificTokenAuth(
            Fixture::ADMIN_USER_LOGIN,
            Fixture::ADMIN_USER_PASSWORD,
            'legitimate app token'
        );

        $this->assertNotEmpty($token);
        $this->assertNotEmpty($this->hashedTokensFor(Fixture::ADMIN_USER_LOGIN));
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
