<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\NoAccessException;
use Piwik\Nonce;
use Piwik\Plugins\OAuth2\API;
use Piwik\Plugins\OAuth2\Controller;
use Piwik\Plugins\OAuth2\Model\AuthCodeModel;
use Piwik\Plugins\OAuth2\Model\ClientModel;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\ServerFactory;
use Piwik\Plugins\OAuth2\SystemSettings;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;

/**
 * @group OAuth2
 * @group OAuth2Controller
 * @group Plugins
 */
class ControllerTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private array $server;
    private array $get;
    private array $post;

    public function setUp(): void
    {
        parent::setUp();

        OAuth2::setupRSAKeys(true);
        OAuth2::setEncryptionKey(true);
        FakeAccess::clearAccess(true);

        $this->server = $_SERVER;
        $this->get = $_GET;
        $this->post = $_POST;
    }

    public function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = $this->get;
        $_POST = $this->post;
        http_response_code(200);

        parent::tearDown();
    }

    public function test_authorizationServerMetadata_returnsConfiguredMetadata()
    {
        $settings = new SystemSettings();
        $settings->enableClientCredentials->setValue(false);

        $metadata = $this->emitJson(function () {
            $this->makeController()->authorizationServerMetadata();
        });

        $this->assertSame(200, http_response_code());
        $this->assertStringEndsWith('/tests/PHPUnit/proxy', $metadata['issuer']);
        $this->assertSame(
            $metadata['issuer'] . '/index.php?module=OAuth2&action=authorize',
            $metadata['authorization_endpoint']
        );
        $this->assertSame(
            $metadata['issuer'] . '/index.php?module=OAuth2&action=token',
            $metadata['token_endpoint']
        );
        $this->assertSame(['matomo:read', 'matomo:write', 'matomo:admin'], $metadata['scopes_supported']);
        $this->assertSame(['code'], $metadata['response_types_supported']);
        $this->assertSame(['query'], $metadata['response_modes_supported']);
        $this->assertSame(['authorization_code', 'refresh_token'], $metadata['grant_types_supported']);
        $this->assertSame(
            ['client_secret_basic', 'client_secret_post', 'none'],
            $metadata['token_endpoint_auth_methods_supported']
        );
        $this->assertSame(['S256', 'plain'], $metadata['code_challenge_methods_supported']);
    }

    public function test_authorizeGet_rendersSelectableRequestedScopesWithinClientCeiling()
    {
        $client = $this->createClient('matomo:admin');
        $this->setAuthorizeRequestGlobals('GET', $client['client']['client_id'], [
            'scope' => 'matomo:read matomo:write matomo:admin',
        ]);

        $html = $this->makeController()->authorize();

        $this->assertStringContainsString('OAuth2_SelectScope', $html);
        $this->assertSame(3, substr_count($html, 'name="selected_scope"'));
        $this->assertStringContainsString('<span class="scope">matomo:read</span>', $html);
        $this->assertStringContainsString('<span class="scope">matomo:write</span>', $html);
        $this->assertStringContainsString('<span class="scope">matomo:admin</span>', $html);
        $this->assertStringNotContainsString('<span class="scope">matomo:superuser</span>', $html);
    }

    public function test_authorizePost_persistsOnlySelectedScope()
    {
        $client = $this->createClient('matomo:admin');
        $this->setAuthorizeRequestGlobals('POST', $client['client']['client_id'], [
            'scope' => 'matomo:read matomo:write matomo:admin',
            'state' => 'selected-scope-state',
        ], [
            'nonce' => Nonce::getNonce('Oauth2.authorize'),
            'decision' => 'allow',
            'selected_scope' => 'matomo:write',
        ]);

        $this->emit(function () {
            $this->makeController()->authorize();
        });

        $this->assertSame(302, http_response_code());
        $authCode = $this->getLatestAuthCode($client['client']['client_id']);
        $this->assertSame(['matomo:write'], $authCode['scopes']);
    }

    public function test_authorizePost_rejectsSelectedScopeOutsideClientCeiling()
    {
        $client = $this->createClient('matomo:admin');
        $countBefore = $this->countAuthCodes($client['client']['client_id']);
        $this->setAuthorizeRequestGlobals('POST', $client['client']['client_id'], [
            'scope' => 'matomo:read matomo:write matomo:admin',
        ], [
            'nonce' => Nonce::getNonce('Oauth2.authorize'),
            'decision' => 'allow',
            'selected_scope' => 'matomo:superuser',
        ]);

        $response = $this->makeController()->authorize();

        $this->assertSame(400, http_response_code());
        $this->assertSame('OAuth2_InvalidClientScope', $response);
        $this->assertSame($countBefore, $this->countAuthCodes($client['client']['client_id']));
    }

    public function test_authorizePost_rejectsSelectedScopeWhenUserLacksAccess()
    {
        $client = $this->createClient('matomo:admin');
        $this->setAuthorizeRequestGlobals('POST', $client['client']['client_id'], [
            'scope' => 'matomo:read matomo:write matomo:admin',
        ], [
            'nonce' => Nonce::getNonce('Oauth2.authorize'),
            'decision' => 'allow',
            'selected_scope' => 'matomo:write',
        ]);
        FakeAccess::clearAccess(false, [], [1], Fixture::ADMIN_USER_LOGIN);

        $this->expectException(NoAccessException::class);

        $this->makeController()->authorize();
    }

    private function makeController(): Controller
    {
        return new Controller(
            StaticContainer::get(ClientModel::class),
            StaticContainer::get(ScopeRepository::class),
            StaticContainer::get(ServerFactory::class),
            new SystemSettings(),
            StaticContainer::get(UserModel::class)
        );
    }

    private function createClient(string $scope): array
    {
        return API::getInstance()->createClient(
            'Authorization UI client',
            ['authorization_code', 'refresh_token'],
            $scope,
            ['https://client.example/callback'],
            'Authorization code client',
            'confidential'
        );
    }

    private function setAuthorizeRequestGlobals(
        string $method,
        string $clientId,
        array $queryParams = [],
        array $postParams = []
    ): void {
        $_GET = array_merge([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => 'https://client.example/callback',
            'scope' => 'matomo:read',
            'state' => 'test-state',
        ], $queryParams);
        $_POST = $postParams;

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_HOST'] = 'example.org';
        $_SERVER['SERVER_NAME'] = 'example.org';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/matomo/index.php?' . http_build_query($_GET);
        $_SERVER['QUERY_STRING'] = http_build_query($_GET);
        $_SERVER['SCRIPT_NAME'] = '/matomo/index.php';
        $_SERVER['PHP_SELF'] = '/matomo/index.php';
    }

    private function emit(callable $callback): string
    {
        ob_start();
        $callback();

        return ob_get_clean();
    }

    private function emitJson(callable $callback): array
    {
        $json = $this->emit($callback);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded, $json);

        return $decoded;
    }

    private function getLatestAuthCode(string $clientId): array
    {
        $row = Db::fetchRow(
            'SELECT code_id FROM ' . \Piwik\Common::prefixTable('oauth2_auth_code') . ' WHERE client_id = ? ORDER BY created_at DESC LIMIT 1',
            [$clientId]
        );
        $this->assertNotEmpty($row);

        $authCode = (new AuthCodeModel())->find($row['code_id']);
        $this->assertNotNull($authCode);

        return $authCode;
    }

    private function countAuthCodes(string $clientId): int
    {
        return (int) Db::fetchOne(
            'SELECT COUNT(*) FROM ' . \Piwik\Common::prefixTable('oauth2_auth_code') . ' WHERE client_id = ?',
            [$clientId]
        );
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}

ControllerTest::$fixture = new OAuth2Fixture();
