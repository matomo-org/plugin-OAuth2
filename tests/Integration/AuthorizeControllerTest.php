<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Matomo\Dependencies\OAuth2\Nyholm\Psr7\Response;
use Matomo\Dependencies\OAuth2\Nyholm\Psr7\ServerRequest;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugins\OAuth2\Controller;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Repositories\AccessTokenRepository;
use Piwik\Plugins\OAuth2\Repositories\AuthCodeRepository;
use Piwik\Plugins\OAuth2\Repositories\ClientRepository;
use Piwik\Plugins\OAuth2\Repositories\RefreshTokenRepository;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\ClientManager;
use Piwik\Plugins\OAuth2\Service\ServerFactory;
use Piwik\Plugins\OAuth2\SystemSettings;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;

/**
 * @group OAuth2
 * @group AuthorizeController
 * @group Plugins
 */
class AuthorizeControllerTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private const REDIRECT_URI = 'https://client.example/callback';

    private Controller $controller;
    private ClientManager $clientManager;

    private array $backupGet;
    private array $backupPost;
    private array $backupRequest;
    private $backupRequestMethod;

    public function setUp(): void
    {
        parent::setUp();

        OAuth2::setupRSAKeys(true);
        OAuth2::setEncryptionKey(true);

        FakeAccess::clearAccess(true);

        $this->backupGet = $_GET;
        $this->backupPost = $_POST;
        $this->backupRequest = $_REQUEST;
        $this->backupRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;

        $this->controller = StaticContainer::get(Controller::class);
        $this->clientManager = StaticContainer::get(ClientManager::class);
    }

    public function tearDown(): void
    {
        $_GET = $this->backupGet;
        $_POST = $this->backupPost;
        $_REQUEST = $this->backupRequest;

        if ($this->backupRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->backupRequestMethod;
        }

        Common::$headersSentInTests = [];

        parent::tearDown();
    }

    public function test_get_showsRadioGroupWithLeastPrivilegedScopePreselected()
    {
        $client = $this->createClient(['matomo:read', 'matomo:write', 'matomo:admin']);

        $html = $this->requestAuthorize($this->authorizeQuery($client, 'matomo:admin matomo:write matomo:read'));

        $this->assertSame(3, substr_count($html, 'name="selected_scope"'));
        $this->assertStringContainsString('value="matomo:read" checked', $html);
        $this->assertStringContainsString(Piwik::translate('OAuth2_SelectScopeToGrant'), $html);

        // ascending privilege order regardless of the order in the request
        $this->assertLessThan(strpos($html, 'value="matomo:write"'), strpos($html, 'value="matomo:read"'));
        $this->assertLessThan(strpos($html, 'value="matomo:admin"'), strpos($html, 'value="matomo:write"'));
    }

    public function test_get_showsSingleScopeWithoutRadiosWhenClientAllowsOnlyOne()
    {
        $client = $this->createClient(['matomo:read']);

        $html = $this->requestAuthorize($this->authorizeQuery($client, 'matomo:read matomo:write matomo:admin'));

        $this->assertStringNotContainsString('type="radio"', $html);
        $this->assertStringContainsString('type="hidden" name="selected_scope" value="matomo:read"', $html);
        $this->assertStringContainsString(Piwik::translate('OAuth2_RequestedScopes'), $html);
    }

    public function test_get_fallsBackToGloballyAllowedScopesForClientWithoutScopeRestriction()
    {
        $client = $this->createClient([]);

        $html = $this->requestAuthorize($this->authorizeQuery($client, 'matomo:read matomo:write matomo:admin'));

        $this->assertSame(3, substr_count($html, 'name="selected_scope"'));
    }

    public function test_get_hidesScopesTheUserCannotGrant()
    {
        $client = $this->createClient(['matomo:read', 'matomo:write', 'matomo:admin']);
        FakeAccess::clearAccess(false, [], [1], 'writeUserLogin', [1]);

        $html = $this->requestAuthorize($this->authorizeQuery($client, 'matomo:read matomo:write matomo:admin'));

        $this->assertSame(2, substr_count($html, 'name="selected_scope"'));
        $this->assertStringContainsString('value="matomo:read" checked', $html);
        $this->assertStringContainsString('value="matomo:write"', $html);
        $this->assertStringNotContainsString('matomo:admin', $html);
    }

    public function test_get_rejectsRequestWhenTheClientAllowsNoneOfTheRequestedScopes()
    {
        $client = $this->createClient(['matomo:read']);

        $response = $this->requestAuthorize($this->authorizeQuery($client, 'matomo:write matomo:admin'));

        $this->assertSame(Piwik::translate('OAuth2_InvalidClientScope'), $response);
        $this->assertResponseCodeSent(400, 'Bad Request');
    }

    public function test_get_rejectsRequestWithADistinctMessageWhenTheUserCannotGrantAnyScope()
    {
        $client = $this->createClient(['matomo:write', 'matomo:admin']);
        FakeAccess::clearAccess(false, [], [], 'noAccessLogin');

        $response = $this->requestAuthorize($this->authorizeQuery($client, 'matomo:write matomo:admin'));

        $this->assertSame(
            Piwik::translate('OAuth2_NoAccessForRequestedScopes', 'matomo:write, matomo:admin'),
            $response
        );
        $this->assertResponseCodeSent(400, 'Bad Request');
        // the scope mapping is fine here, so the message must not blame the client configuration
        $this->assertStringNotContainsString(Piwik::translate('OAuth2_InvalidClientScope'), $response);
    }

    public function test_get_deduplicatesRequestedScopes()
    {
        $client = $this->createClient(['matomo:read', 'matomo:write', 'matomo:admin']);

        $html = $this->requestAuthorize($this->authorizeQuery($client, 'matomo:read matomo:read matomo:write'));

        $this->assertSame(2, substr_count($html, 'name="selected_scope"'));
        $this->assertSame(1, substr_count($html, 'value="matomo:read"'));
    }

    public function test_post_allow_issuesAuthCodeNarrowedToSelectedScope()
    {
        $client = $this->createClient(['matomo:read', 'matomo:write', 'matomo:admin']);
        $capturedEvents = $this->captureAuthorizeDecisionEvents();

        $this->requestAuthorize(
            $this->authorizeQuery($client, 'matomo:read matomo:write matomo:admin'),
            ['decision' => 'allow', 'selected_scope' => 'matomo:write', 'nonce' => Nonce::getNonce('Oauth2.authorize')]
        );

        $redirectParams = $this->parseRedirect();
        $this->assertNotEmpty($redirectParams['code']);
        $this->assertSame('test-state', $redirectParams['state']);
        $this->assertResponseCodeSent(302, 'Found');

        $this->assertCount(1, $capturedEvents);
        $this->assertSame(['matomo:write'], $capturedEvents[0]['scopes']);
        $this->assertSame('allowed', $capturedEvents[0]['decision']);

        // the narrowed code passes finalizeScopes at the token endpoint and yields a matomo:write token
        $tokenPayload = $this->exchangeCodeForTokens($client, $redirectParams['code']);
        $this->assertNotEmpty($tokenPayload['access_token']);

        $storedToken = Db::fetchRow(
            'SELECT * FROM ' . Common::prefixTable('oauth2_access_token') . ' ORDER BY created_at DESC LIMIT 1'
        );
        $this->assertSame(['matomo:write'], json_decode($storedToken['scopes'], true));
    }

    public function test_post_allow_rejectsScopeOutsideTheSelectableSet()
    {
        $client = $this->createClient(['matomo:read', 'matomo:write']);
        $capturedEvents = $this->captureAuthorizeDecisionEvents();

        $response = $this->requestAuthorize(
            $this->authorizeQuery($client, 'matomo:read matomo:write'),
            ['decision' => 'allow', 'selected_scope' => 'matomo:superuser', 'nonce' => Nonce::getNonce('Oauth2.authorize')]
        );

        $this->assertSame(Piwik::translate('OAuth2_InvalidScopeValue'), $response);
        $this->assertResponseCodeSent(400, 'Bad Request');
        $this->assertCount(0, $capturedEvents);
        $this->assertSame(0, $this->countAuthCodes($client['client']['client_id']));
    }

    public function test_post_allow_rejectsMissingSelectedScope()
    {
        $client = $this->createClient(['matomo:read', 'matomo:write']);
        $capturedEvents = $this->captureAuthorizeDecisionEvents();

        $response = $this->requestAuthorize(
            $this->authorizeQuery($client, 'matomo:read matomo:write'),
            ['decision' => 'allow', 'nonce' => Nonce::getNonce('Oauth2.authorize')]
        );

        $this->assertSame(Piwik::translate('OAuth2_InvalidScopeValue'), $response);
        $this->assertResponseCodeSent(400, 'Bad Request');
        $this->assertCount(0, $capturedEvents);
        $this->assertSame(0, $this->countAuthCodes($client['client']['client_id']));
    }

    public function test_post_deny_recordsSelectedScopeInAuditEvent()
    {
        $client = $this->createClient(['matomo:read', 'matomo:write']);
        $capturedEvents = $this->captureAuthorizeDecisionEvents();

        $this->requestAuthorize(
            $this->authorizeQuery($client, 'matomo:read matomo:write'),
            ['decision' => 'deny', 'selected_scope' => 'matomo:read', 'nonce' => Nonce::getNonce('Oauth2.authorize')]
        );

        $redirectParams = $this->parseRedirect();
        $this->assertSame('access_denied', $redirectParams['error']);

        $this->assertCount(1, $capturedEvents);
        $this->assertSame(['matomo:read'], $capturedEvents[0]['scopes']);
        $this->assertSame('denied', $capturedEvents[0]['decision']);
        $this->assertSame(0, $this->countAuthCodes($client['client']['client_id']));
    }

    public function test_post_allow_singleScopeClientStillIssuesCode()
    {
        $client = $this->createClient(['matomo:read']);

        $this->requestAuthorize(
            $this->authorizeQuery($client, 'matomo:read matomo:write matomo:admin'),
            ['decision' => 'allow', 'selected_scope' => 'matomo:read', 'nonce' => Nonce::getNonce('Oauth2.authorize')]
        );

        $redirectParams = $this->parseRedirect();
        $this->assertNotEmpty($redirectParams['code']);

        $tokenPayload = $this->exchangeCodeForTokens($client, $redirectParams['code']);
        $this->assertNotEmpty($tokenPayload['access_token']);
    }

    public function test_emitResponse_sendsStatusCodesCoreHasNoReasonPhraseFor()
    {
        // the token endpoint answers a GET with 405, which Common::sendResponseCode() does not know
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['module' => 'OAuth2', 'action' => 'token'];
        $_POST = [];
        $_REQUEST = $_GET;

        ob_start();
        try {
            $this->controller->token();
        } finally {
            $body = ob_get_clean();
        }

        $this->assertSame(Piwik::translate('OAuth2_TokenEndpointException'), $body);
        $this->assertResponseCodeSent(405, 'Method Not Allowed');
    }

    private function createClient(array $scopes): array
    {
        return $this->clientManager->create([
            'name' => 'Consent test client',
            'description' => 'Authorize controller test client',
            'redirect_uris' => [self::REDIRECT_URI],
            'grant_types' => ['authorization_code'],
            'scopes' => $scopes,
            'type' => 'confidential',
            'active' => true,
        ], Fixture::ADMIN_USER_LOGIN);
    }

    private function authorizeQuery(array $client, string $scope): array
    {
        return [
            'module' => 'OAuth2',
            'action' => 'authorize',
            'response_type' => 'code',
            'client_id' => $client['client']['client_id'],
            'redirect_uri' => self::REDIRECT_URI,
            'scope' => $scope,
            'state' => 'test-state',
        ];
    }

    private function requestAuthorize(array $query, ?array $post = null): string
    {
        $_SERVER['REQUEST_METHOD'] = $post === null ? 'GET' : 'POST';
        $_GET = $query;
        $_POST = $post ?? [];
        $_REQUEST = array_merge($_POST, $_GET);

        ob_start();
        try {
            $result = $this->controller->authorize();
        } finally {
            $echoed = ob_get_clean();
        }

        return (string) ($result ?? $echoed);
    }

    private function captureAuthorizeDecisionEvents(): \ArrayObject
    {
        $capturedEvents = new \ArrayObject();
        Piwik::addAction('OAuth2.authorize.decision.end', function ($activityData) use ($capturedEvents) {
            $capturedEvents[] = $activityData;
        });

        return $capturedEvents;
    }

    private function assertResponseCodeSent(int $statusCode, string $reasonPhrase): void
    {
        // Common::sendResponseCode() sends the status line as a header without a colon, so it is
        // recorded under the full status line in test mode
        $this->assertArrayHasKey('HTTP/1.1 ' . $statusCode . ' ' . $reasonPhrase, Common::$headersSentInTests);
    }

    private function parseRedirect(): array
    {
        $location = trim(Common::$headersSentInTests['Location'] ?? '');
        $this->assertNotEmpty($location, 'expected authorize() to respond with a redirect');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $redirectParams);

        return $redirectParams;
    }

    private function countAuthCodes(string $clientId): int
    {
        return (int) Db::fetchOne(
            'SELECT COUNT(*) FROM ' . Common::prefixTable('oauth2_auth_code') . ' WHERE client_id = ?',
            [$clientId]
        );
    }

    private function exchangeCodeForTokens(array $client, string $code): array
    {
        $serverFactory = new ServerFactory(
            StaticContainer::get(ClientRepository::class),
            StaticContainer::get(AccessTokenRepository::class),
            StaticContainer::get(ScopeRepository::class),
            StaticContainer::get(AuthCodeRepository::class),
            StaticContainer::get(RefreshTokenRepository::class),
            new SystemSettings()
        );

        $tokenResponse = $serverFactory->makeAuthorizationServer()->respondToAccessTokenRequest(
            (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                'grant_type' => 'authorization_code',
                'client_id' => $client['client']['client_id'],
                'client_secret' => $client['secret'],
                'code' => $code,
                'redirect_uri' => self::REDIRECT_URI,
            ]),
            new Response()
        );

        $this->assertSame(200, $tokenResponse->getStatusCode());

        return json_decode((string) $tokenResponse->getBody(), true);
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}

AuthorizeControllerTest::$fixture = new OAuth2Fixture();
