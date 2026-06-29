<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Config;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\AuthorizationServerMetadata;
use Piwik\Plugins\OAuth2\SystemSettings;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;

/**
 * @group OAuth2
 * @group WellKnownMetadata
 * @group Plugins
 */
class WellKnownMetadataTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    /** Enables the metadata-extension observer for a single test without affecting the others. */
    public static bool $extendMetadata = false;

    private SystemSettings $settings;

    public function setUp(): void
    {
        parent::setUp();

        $this->settings = new SystemSettings();

        // Resolve the host from the request without trusted-host validation so the
        // simulated requests below produce predictable issuer URLs.
        Config::getInstance()->General['enable_trusted_host_check'] = 0;
        $this->simulateDiscoveryRequest('matomo.example', '');
    }

    public function test_build_returnsFullMetadataForDefaultConfiguration()
    {
        $metadata = $this->makeMetadata()->build();

        $this->assertSame('https://matomo.example', $metadata['issuer']);
        $this->assertSame('https://matomo.example/index.php?module=OAuth2&action=authorize', $metadata['authorization_endpoint']);
        $this->assertSame('https://matomo.example/index.php?module=OAuth2&action=token', $metadata['token_endpoint']);
        $this->assertSame(['matomo:read', 'matomo:write', 'matomo:admin'], $metadata['scopes_supported']);
        $this->assertSame(['code'], $metadata['response_types_supported']);
        $this->assertSame(['S256', 'plain'], $metadata['code_challenge_methods_supported']);
        $this->assertSame(['authorization_code', 'client_credentials', 'refresh_token'], $metadata['grant_types_supported']);
        $this->assertSame(['client_secret_basic', 'client_secret_post'], $metadata['token_endpoint_auth_methods_supported']);
    }

    public function test_build_resolvesIssuerForSubdirectoryInstall()
    {
        $this->simulateDiscoveryRequest('matomo.example', '/analytics');

        $metadata = $this->makeMetadata()->build();

        $this->assertSame('https://matomo.example/analytics', $metadata['issuer']);
        $this->assertSame('https://matomo.example/analytics/index.php?module=OAuth2&action=token', $metadata['token_endpoint']);
    }

    public function test_build_omitsAuthorizationCodeMetadataWhenGrantDisabled()
    {
        $this->settings->enableAuthorizationCode->setValue(false);

        $metadata = $this->makeMetadata()->build();

        $this->assertArrayNotHasKey('authorization_endpoint', $metadata);
        $this->assertArrayNotHasKey('response_types_supported', $metadata);
        $this->assertArrayNotHasKey('code_challenge_methods_supported', $metadata);
        $this->assertSame(['client_credentials', 'refresh_token'], $metadata['grant_types_supported']);
    }

    public function test_build_reflectsDisabledGrants()
    {
        $this->settings->enableClientCredentials->setValue(false);
        $this->settings->enableRefreshTokens->setValue(false);

        $metadata = $this->makeMetadata()->build();

        $this->assertSame(['authorization_code'], $metadata['grant_types_supported']);
    }

    public function test_build_allowsOtherPluginsToExtendMetadataViaEvent()
    {
        self::$extendMetadata = true;

        try {
            $metadata = $this->makeMetadata()->build();
        } finally {
            self::$extendMetadata = false;
        }

        $this->assertSame('https://matomo.example/oauth/register', $metadata['registration_endpoint']);
        // The observer can also override values built by this plugin.
        $this->assertSame('https://matomo.example/oauth/token', $metadata['token_endpoint']);
    }

    private function simulateDiscoveryRequest(string $host, string $basePath): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['REQUEST_URI'] = $basePath . AuthorizationServerMetadata::WELL_KNOWN_PATH;
    }

    private function makeMetadata(): AuthorizationServerMetadata
    {
        return new AuthorizationServerMetadata($this->settings, new ScopeRepository($this->settings));
    }

    public function provideContainerConfig()
    {
        return [
            'observers.global' => \DI\add(
                [
                    [
                        'OAuth2.authorizationServerMetadata',
                        \DI\value(
                            function (array &$metadata) {
                                if (!WellKnownMetadataTest::$extendMetadata) {
                                    return;
                                }
                                $metadata['registration_endpoint'] = 'https://matomo.example/oauth/register';
                                $metadata['token_endpoint'] = 'https://matomo.example/oauth/token';
                            }
                        ),
                    ],
                ]
            ),
        ];
    }
}

WellKnownMetadataTest::$fixture = new OAuth2Fixture();
