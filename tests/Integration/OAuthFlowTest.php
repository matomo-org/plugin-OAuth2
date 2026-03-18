<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Matomo\Dependencies\Oauth2\Nyholm\Psr7\Response;
use Matomo\Dependencies\Oauth2\Nyholm\Psr7\ServerRequest;
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\Plugins\OAuth2\API;
use Piwik\Plugins\OAuth2\Entities\UserEntity;
use Piwik\Plugins\OAuth2\Model\AuthCodeModel;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Repositories\AccessTokenRepository;
use Piwik\Plugins\OAuth2\Repositories\AuthCodeRepository;
use Piwik\Plugins\OAuth2\Repositories\ClientRepository;
use Piwik\Plugins\OAuth2\Repositories\RefreshTokenRepository;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\ServerFactory;
use Piwik\Plugins\OAuth2\SystemSettings;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;

/**
 * @group OAuth2
 * @group OAuthFlow
 * @group Plugins
 */
class OAuthFlowTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private API $api;
    private ServerFactory $serverFactory;
    private AuthCodeModel $authCodeModel;

    public function setUp(): void
    {
        parent::setUp();

        OAuth2::setupRSAKeys(true);
        OAuth2::setEncryptionKey(true);

        FakeAccess::clearAccess(true);
        $this->api = API::getInstance();
        $this->serverFactory = new ServerFactory(
            StaticContainer::get(ClientRepository::class),
            StaticContainer::get(AccessTokenRepository::class),
            StaticContainer::get(ScopeRepository::class),
            StaticContainer::get(AuthCodeRepository::class),
            StaticContainer::get(RefreshTokenRepository::class),
            new SystemSettings()
        );
        $this->authCodeModel = new AuthCodeModel();
    }

    public function test_authorizationCodeFlow_returnsAccessAndRefreshTokensForConfidentialClient()
    {
        $client = $this->createConfidentialAuthCodeClient();
        $tokenPayload = $this->exchangeAuthorizationCodeForTokens($client);

        $this->assertNotEmpty($tokenPayload['refresh_token']);
    }

    public function test_refreshTokenFlow_rotatesTokensForConfidentialClient()
    {
        $client = $this->createConfidentialAuthCodeClient();
        $tokenPayload = $this->exchangeAuthorizationCodeForTokens($client);
        $authorizationServer = $this->serverFactory->makeAuthorizationServer();

        $beforeRefreshAccessTokens = (int) Db::fetchOne('SELECT COUNT(*) FROM ' . \Piwik\Common::prefixTable('oauth2_access_token'));
        $beforeRefreshRefreshTokens = (int) Db::fetchOne('SELECT COUNT(*) FROM ' . \Piwik\Common::prefixTable('oauth2_refresh_token'));

        $refreshResponse = $authorizationServer->respondToAccessTokenRequest(
            (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                'grant_type' => 'refresh_token',
                'client_id' => $client['client']['client_id'],
                'client_secret' => $client['secret'],
                'refresh_token' => $tokenPayload['refresh_token'],
            ]),
            new Response()
        );

        $refreshPayload = json_decode((string) $refreshResponse->getBody(), true);

        $this->assertSame(200, $refreshResponse->getStatusCode());
        $this->assertSame('Bearer', $refreshPayload['token_type']);
        $this->assertNotEmpty($refreshPayload['access_token']);
        $this->assertNotEmpty($refreshPayload['refresh_token']);
        $this->assertNotSame($tokenPayload['access_token'], $refreshPayload['access_token']);
        $this->assertGreaterThan(
            $beforeRefreshAccessTokens,
            (int) Db::fetchOne('SELECT COUNT(*) FROM ' . \Piwik\Common::prefixTable('oauth2_access_token'))
        );
        $this->assertGreaterThan(
            $beforeRefreshRefreshTokens,
            (int) Db::fetchOne('SELECT COUNT(*) FROM ' . \Piwik\Common::prefixTable('oauth2_refresh_token'))
        );
    }

    public function test_clientCredentialsFlow_returnsAccessTokenForConfidentialClient()
    {
        $client = $this->api->createClient(
            'Machine client',
            ['client_credentials'],
            'matomo:write',
            [],
            'Server to server client',
            'confidential'
        );

        $response = $this->serverFactory->makeAuthorizationServer()->respondToAccessTokenRequest(
            (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                'grant_type' => 'client_credentials',
                'client_id' => $client['client']['client_id'],
                'client_secret' => $client['secret'],
                'scope' => 'matomo:write',
            ]),
            new Response()
        );

        $payload = json_decode((string) $response->getBody(), true);
        $storedToken = Db::fetchRow(
            'SELECT * FROM ' . \Piwik\Common::prefixTable('oauth2_access_token') . ' ORDER BY created_at DESC LIMIT 1'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bearer', $payload['token_type']);
        $this->assertNotEmpty($payload['access_token']);
        $this->assertArrayNotHasKey('refresh_token', $payload);
        $this->assertSame(Fixture::ADMIN_USER_LOGIN, $storedToken['user_login']);
        $this->assertSame($client['client']['client_id'], $storedToken['client_id']);
    }

    private function createConfidentialAuthCodeClient(): array
    {
        $client = $this->api->createClient(
            'Confidential auth code client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://confidential-client.example/callback'],
            'Authorization code client',
            'confidential'
        );
        return $client;
    }

    private function exchangeAuthorizationCodeForTokens(array $client): array
    {
        $authorizationServer = $this->serverFactory->makeAuthorizationServer();
        $authorizationRequest = $authorizationServer->validateAuthorizationRequest(
            (new ServerRequest('GET', 'https://matomo.example/authorize'))->withQueryParams([
                'response_type' => 'code',
                'client_id' => $client['client']['client_id'],
                'redirect_uri' => 'https://confidential-client.example/callback',
                'scope' => 'matomo:read',
                'state' => 'test-state',
            ])
        );

        $user = new UserEntity();
        $user->setIdentifier(Fixture::ADMIN_USER_LOGIN);
        $authorizationRequest->setUser($user);
        $authorizationRequest->setAuthorizationApproved(true);

        $authorizationResponse = $authorizationServer->completeAuthorizationRequest($authorizationRequest, new Response());
        $redirectLocation = $authorizationResponse->getHeaderLine('Location');

        parse_str((string) parse_url($redirectLocation, PHP_URL_QUERY), $redirectParams);

        $this->assertSame('test-state', $redirectParams['state']);
        $this->assertNotEmpty($redirectParams['code']);

        $persistedCode = Db::fetchRow(
            'SELECT * FROM ' . \Piwik\Common::prefixTable('oauth2_auth_code') . ' WHERE client_id = ? ORDER BY created_at DESC LIMIT 1',
            [$client['client']['client_id']]
        );
        $this->assertNotNull($persistedCode);

        $tokenResponse = $authorizationServer->respondToAccessTokenRequest(
            (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                'grant_type' => 'authorization_code',
                'client_id' => $client['client']['client_id'],
                'client_secret' => $client['secret'],
                'code' => $redirectParams['code'],
                'redirect_uri' => 'https://confidential-client.example/callback',
            ]),
            new Response()
        );

        $tokenPayload = json_decode((string) $tokenResponse->getBody(), true);

        $this->assertSame(200, $tokenResponse->getStatusCode());
        $this->assertSame('Bearer', $tokenPayload['token_type']);
        $this->assertNotEmpty($tokenPayload['access_token']);
        $this->assertNotEmpty($tokenPayload['refresh_token']);
        $this->assertArrayHasKey('expires_in', $tokenPayload);
        $this->assertTrue($this->authCodeModel->isRevoked($redirectParams['code']));

        return $tokenPayload;
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}

OAuthFlowTest::$fixture = new OAuth2Fixture();
