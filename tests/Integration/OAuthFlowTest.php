<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Exception\OAuthServerException;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\CryptKey;
use Matomo\Dependencies\OAuth2\Nyholm\Psr7\Response;
use Matomo\Dependencies\OAuth2\Nyholm\Psr7\ServerRequest;
use Piwik\Auth\Password;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Db;
use Piwik\Plugins\OAuth2\Auth\ResourceServerAuthenticator;
use Piwik\Plugins\OAuth2\Entities\AccessTokenEntity;
use Piwik\Plugins\OAuth2\Entities\ClientEntity;
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
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Plugins\UsersManager\UsersManager;
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

    public function test_confidentialClientDowngradeRevokesPreviouslyIssuedCredentials(): void
    {
        $client = $this->createConfidentialAuthCodeClient();
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
        parse_str((string) parse_url($authorizationResponse->getHeaderLine('Location'), PHP_URL_QUERY), $redirectParams);

        $this->assertNotEmpty($redirectParams['code']);

        // The redirect "code" is an encrypted payload, not the stored primary key, so capture the
        // persisted code_id to check its revocation state directly. This is the only code for the
        // freshly created client and it is never exchanged.
        $codeIds = $this->authCodeIdsForClient($client['client']['client_id']);
        $this->assertCount(1, $codeIds);
        $unusedCodeId = $codeIds[0];

        $baselineException = null;
        try {
            $authorizationServer->respondToAccessTokenRequest(
                (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                    'grant_type' => 'authorization_code',
                    'client_id' => $client['client']['client_id'],
                    'code' => $redirectParams['code'],
                    'redirect_uri' => 'https://confidential-client.example/callback',
                ]),
                new Response()
            );
            $this->fail('Expected missing client_secret to be rejected before downgrade.');
        } catch (OAuthServerException $e) {
            $baselineException = $e;
        }

        $this->assertInstanceOf(OAuthServerException::class, $baselineException);

        $tokenPayload = $this->exchangeAuthorizationCodeForTokens($client);

        // Sanity check that the still-unused authorization code and the freshly issued access
        // token are valid *before* the downgrade, so the assertions afterwards prove the
        // downgrade is what revoked them (rather than them being invalid all along).
        $this->assertFalse($this->authCodeModel->isRevoked($unusedCodeId));
        $this->assertSame(
            $client['client']['client_id'],
            $this->serverFactory->makeResourceServer()->validateAuthenticatedRequest(
                (new ServerRequest('GET', 'https://matomo.example/index.php'))
                    ->withHeader('Authorization', 'Bearer ' . $tokenPayload['access_token'])
            )->getAttribute('oauth_client_id')
        );

        $this->api->updateClient(
            $client['client']['client_id'],
            'Now public client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://confidential-client.example/callback'],
            'Downgraded for hardening test',
            'public',
            '1',
            Fixture::ADMIN_USER_PASSWORD
        );

        // The unused authorization code is revoked by the downgrade itself.
        $this->assertTrue($this->authCodeModel->isRevoked($unusedCodeId));

        // The previously issued access token must no longer authenticate against the resource server.
        try {
            $this->serverFactory->makeResourceServer()->validateAuthenticatedRequest(
                (new ServerRequest('GET', 'https://matomo.example/index.php'))
                    ->withHeader('Authorization', 'Bearer ' . $tokenPayload['access_token'])
            );
            $this->fail('Expected the previously issued access token to be rejected after downgrade.');
        } catch (OAuthServerException $e) {
            $this->assertTrue(true);
        }

        try {
            $authorizationServer->respondToAccessTokenRequest(
                (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                    'grant_type' => 'authorization_code',
                    'client_id' => $client['client']['client_id'],
                    'code' => $redirectParams['code'],
                    'redirect_uri' => 'https://confidential-client.example/callback',
                ]),
                new Response()
            );
            $this->fail('Expected old authorization code to be rejected after downgrade.');
        } catch (OAuthServerException $e) {
            $this->assertTrue(true);
        }

        try {
            $authorizationServer->respondToAccessTokenRequest(
                (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                    'grant_type' => 'refresh_token',
                    'client_id' => $client['client']['client_id'],
                    'refresh_token' => $tokenPayload['refresh_token'],
                ]),
                new Response()
            );
            $this->fail('Expected old refresh token to be rejected after downgrade.');
        } catch (OAuthServerException $e) {
            $this->assertTrue(true);
        }
    }

    public function test_authorizationCodeFlow_withRefreshEnabled_issuesRefreshTokenForUnrestrictedPublicClient()
    {
        // Baseline: a public client allowed to use refresh_token still gets one, so the
        // absence in the restricted case below is due to the grant restriction, not PKCE.
        $client = $this->api->createClient(
            'Public refreshable client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://public-client.example/callback'],
            'Public authorization code client',
            'public',
            '1',
            Fixture::ADMIN_USER_PASSWORD
        );

        $tokenPayload = $this->exchangePublicClientAuthorizationCodeForTokens($client);

        $this->assertNotEmpty($tokenPayload['refresh_token']);
    }

    public function test_authorizationCodeFlow_doesNotIssueRefreshTokenForPublicClientRestrictedToAuthorizationCode()
    {
        // Public client the admin restricted to authorization_code only. Even though
        // refresh tokens are globally enabled, the per-client grant restriction must be
        // enforced for public clients: no refresh token may be issued or redeemed.
        $client = $this->api->createClient(
            'Public auth code only client',
            ['authorization_code'],
            'matomo:read',
            ['https://public-client.example/callback'],
            'Public authorization code client',
            'public',
            '1',
            Fixture::ADMIN_USER_PASSWORD
        );

        $tokenPayload = $this->exchangePublicClientAuthorizationCodeForTokens($client);

        $this->assertArrayNotHasKey('refresh_token', $tokenPayload);
        $this->assertSame(
            0,
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
            'confidential',
            '1',
            Fixture::ADMIN_USER_PASSWORD
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

    public function test_clientCredentialsFlow_withoutScope_fallsBackToReadWithinTheClientMaximum()
    {
        // the configured scope is a maximum, so a write client also permits the default read
        // scope and no longer has to name a scope explicitly to get a token
        $client = $this->api->createClient(
            'Write only machine client',
            ['client_credentials'],
            'matomo:write',
            [],
            'Server to server client',
            'confidential',
            '1',
            Fixture::ADMIN_USER_PASSWORD
        );

        $response = $this->serverFactory->makeAuthorizationServer()->respondToAccessTokenRequest(
            (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                'grant_type' => 'client_credentials',
                'client_id' => $client['client']['client_id'],
                'client_secret' => $client['secret'],
            ]),
            new Response()
        );

        $storedToken = Db::fetchRow(
            'SELECT * FROM ' . \Piwik\Common::prefixTable('oauth2_access_token') . ' ORDER BY created_at DESC LIMIT 1'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['matomo:read'], json_decode($storedToken['scopes'], true));
    }

    public function test_accessTokenForPausedClientIsRejected()
    {
        $client = $this->api->createClient(
            'Machine client',
            ['client_credentials'],
            'matomo:write',
            [],
            'Server to server client',
            'confidential',
            '1',
            Fixture::ADMIN_USER_PASSWORD
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

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($payload['access_token']);

        $this->api->setClientActive($client['client']['client_id'], '0');

        $this->expectException(OAuthServerException::class);
        $this->serverFactory->makeResourceServer()->validateAuthenticatedRequest(
            (new ServerRequest('GET', 'https://matomo.example/index.php'))->withHeader(
                'Authorization',
                'Bearer ' . $payload['access_token']
            )
        );
    }

    public function test_clientCredentialsFlow_preservesOriginalOwnerAfterAnotherSuperUserEditsClient()
    {
        $client = $this->api->createClient(
            'Machine client',
            ['client_credentials'],
            'matomo:write',
            [],
            'Server to server client',
            'confidential',
            '1',
            Fixture::ADMIN_USER_PASSWORD
        );
        $this->createSuperUser('otherSuperUser');

        FakeAccess::clearAccess(true, [], [], 'otherSuperUser');
        $this->api->updateClient(
            $client['client']['client_id'],
            'Machine client edited',
            ['client_credentials'],
            'matomo:write',
            [],
            'Edited by another superuser',
            'confidential',
            '1',
            'test-password'
        );

        FakeAccess::clearAccess(true);

        $response = $this->serverFactory->makeAuthorizationServer()->respondToAccessTokenRequest(
            (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                'grant_type' => 'client_credentials',
                'client_id' => $client['client']['client_id'],
                'client_secret' => $client['secret'],
                'scope' => 'matomo:write',
            ]),
            new Response()
        );

        $storedToken = Db::fetchRow(
            'SELECT * FROM ' . \Piwik\Common::prefixTable('oauth2_access_token') . ' ORDER BY created_at DESC LIMIT 1'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(Fixture::ADMIN_USER_LOGIN, $storedToken['user_login']);
        $this->assertSame($client['client']['client_id'], $storedToken['client_id']);
    }

    public function test_prepareAuthenticationFromToken_rejectsZeroScopeBearerTokens()
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setIdentifier('zero-scope-token');
        $accessToken->setClient($this->makeClientEntity('zero-scope-client', Fixture::ADMIN_USER_LOGIN));
        $accessToken->setUserIdentifier(Fixture::ADMIN_USER_LOGIN);
        $accessToken->setExpiryDateTime(new \DateTimeImmutable('+1 hour'));
        $accessToken->setPrivateKey(new CryptKey(OAuth2::getRSAKey('private'), null, true));

        StaticContainer::get(AccessTokenRepository::class)->persistNewAccessToken($accessToken);

        $authenticator = StaticContainer::get(ResourceServerAuthenticator::class);
        $authenticator->prepareAuthenticationFromToken($accessToken->toString());

        $this->assertFalse(StaticContainer::get('Piwik\Auth') instanceof \Piwik\Plugins\OAuth2\Auth\Oauth2Auth);
    }

    private function createConfidentialAuthCodeClient(): array
    {
        $client = $this->api->createClient(
            'Confidential auth code client',
            ['authorization_code', 'refresh_token'],
            'matomo:read',
            ['https://confidential-client.example/callback'],
            'Authorization code client',
            'confidential',
            '1',
            Fixture::ADMIN_USER_PASSWORD
        );
        return $client;
    }

    private function exchangePublicClientAuthorizationCodeForTokens(array $client): array
    {
        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $authorizationServer = $this->serverFactory->makeAuthorizationServer();
        $authorizationRequest = $authorizationServer->validateAuthorizationRequest(
            (new ServerRequest('GET', 'https://matomo.example/authorize'))->withQueryParams([
                'response_type' => 'code',
                'client_id' => $client['client']['client_id'],
                'redirect_uri' => 'https://public-client.example/callback',
                'scope' => 'matomo:read',
                'state' => 'test-state',
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => 'S256',
            ])
        );

        $user = new UserEntity();
        $user->setIdentifier(Fixture::ADMIN_USER_LOGIN);
        $authorizationRequest->setUser($user);
        $authorizationRequest->setAuthorizationApproved(true);

        $authorizationResponse = $authorizationServer->completeAuthorizationRequest($authorizationRequest, new Response());
        parse_str((string) parse_url($authorizationResponse->getHeaderLine('Location'), PHP_URL_QUERY), $redirectParams);

        $this->assertNotEmpty($redirectParams['code']);

        $tokenResponse = $authorizationServer->respondToAccessTokenRequest(
            (new ServerRequest('POST', 'https://matomo.example/token'))->withParsedBody([
                'grant_type' => 'authorization_code',
                'client_id' => $client['client']['client_id'],
                'code' => $redirectParams['code'],
                'redirect_uri' => 'https://public-client.example/callback',
                'code_verifier' => $codeVerifier,
            ]),
            new Response()
        );

        $tokenPayload = json_decode((string) $tokenResponse->getBody(), true);

        $this->assertSame(200, $tokenResponse->getStatusCode());
        $this->assertNotEmpty($tokenPayload['access_token']);

        return $tokenPayload;
    }

    private function makeClientEntity(string $clientId, string $ownerLogin): ClientEntity
    {
        $client = new ClientEntity();
        $client->setIdentifier($clientId);
        $client->setName('Zero scope client');
        $client->setRedirectUris([]);
        $client->setConfidential(true);
        $client->ownerLogin = $ownerLogin;

        return $client;
    }

    private function authCodeIdsForClient(string $clientId): array
    {
        $rows = Db::fetchAll(
            'SELECT code_id FROM ' . \Piwik\Common::prefixTable('oauth2_auth_code') . ' WHERE client_id = ?',
            [$clientId]
        );

        return array_column($rows, 'code_id');
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

        // The same client can already have other codes with an identical created_at second, so
        // remember what exists before minting this one to identify the new code_id unambiguously.
        $existingCodeIds = $this->authCodeIdsForClient($client['client']['client_id']);

        $authorizationResponse = $authorizationServer->completeAuthorizationRequest($authorizationRequest, new Response());
        $redirectLocation = $authorizationResponse->getHeaderLine('Location');

        parse_str((string) parse_url($redirectLocation, PHP_URL_QUERY), $redirectParams);

        $this->assertSame('test-state', $redirectParams['state']);
        $this->assertNotEmpty($redirectParams['code']);

        $newCodeIds = array_values(array_diff(
            $this->authCodeIdsForClient($client['client']['client_id']),
            $existingCodeIds
        ));
        $this->assertCount(1, $newCodeIds);
        $issuedCodeId = $newCodeIds[0];

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
        // Redeeming the code marks the stored code_id as revoked (single-use).
        $this->assertTrue($this->authCodeModel->isRevoked($issuedCodeId));

        return $tokenPayload;
    }

    private function createSuperUser(string $login): void
    {
        $userModel = new UserModel();
        $passwordHelper = new Password();
        $hashedPassword = $passwordHelper->hash(UsersManager::getPasswordHash('test-password'));

        if (empty($userModel->getUser($login))) {
            $userModel->addUser($login, $hashedPassword, $login . '@example.org', Date::now()->getDatetime());
        } else {
            $userModel->updateUser($login, $hashedPassword, $login . '@example.org');
        }

        $userModel->setSuperUserAccess($login, true);
    }

    public function provideContainerConfig()
    {
        return [
            'Piwik\Access' => new FakeAccess(),
        ];
    }
}

OAuthFlowTest::$fixture = new OAuth2Fixture();
