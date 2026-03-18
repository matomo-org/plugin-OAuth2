<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\SystemSettings;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;

/**
 * @group OAuth2
 * @group SystemSettings
 * @group Plugins
 */
class SystemSettingsTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private SystemSettings $settings;

    public function setUp(): void
    {
        parent::setUp();

        $this->settings = new SystemSettings();
    }

    public function test_defaults_areConfigured()
    {
        $this->assertSame(3600, $this->settings->accessTokenTtl->getValue());
        $this->assertSame(2592000, $this->settings->refreshTokenTtl->getValue());
        $this->assertSame(600, $this->settings->authCodeTtl->getValue());
        $this->assertTrue($this->settings->enableAuthorizationCode->getValue());
        $this->assertTrue($this->settings->enableClientCredentials->getValue());
        $this->assertTrue($this->settings->enableRefreshTokens->getValue());
        $this->assertSame(
            ['matomo:read', 'matomo:write', 'matomo:admin'],
            $this->settings->defaultScopes->getValue()
        );
    }

    public function test_customValues_canBeSaved()
    {
        $this->settings->accessTokenTtl->setValue(7200);
        $this->settings->refreshTokenTtl->setValue(86400);
        $this->settings->authCodeTtl->setValue(300);
        $this->settings->enableAuthorizationCode->setValue(false);
        $this->settings->enableClientCredentials->setValue(false);
        $this->settings->enableRefreshTokens->setValue(false);
        $this->settings->defaultScopes->setValue(['matomo:read', 'matomo:superuser']);
        $this->settings->save();

        $reloaded = new SystemSettings();

        $this->assertSame(7200, $reloaded->accessTokenTtl->getValue());
        $this->assertSame(86400, $reloaded->refreshTokenTtl->getValue());
        $this->assertSame(300, $reloaded->authCodeTtl->getValue());
        $this->assertFalse($reloaded->enableAuthorizationCode->getValue());
        $this->assertFalse($reloaded->enableClientCredentials->getValue());
        $this->assertFalse($reloaded->enableRefreshTokens->getValue());
        $this->assertSame(['matomo:read', 'matomo:superuser'], $reloaded->defaultScopes->getValue());
    }

    /**
     * @dataProvider getInvalidTtlValues
     */
    public function test_ttlSettings_rejectInvalidValues(string $property, int $value)
    {
        $this->expectException(\Exception::class);

        $this->settings->{$property}->setValue($value);
    }

    public function getInvalidTtlValues(): array
    {
        return [
            ['accessTokenTtl', 0],
            ['refreshTokenTtl', 0],
            ['authCodeTtl', 0],
            ['accessTokenTtl', -1],
            ['refreshTokenTtl', -1],
            ['authCodeTtl', -1],
        ];
    }

    public function test_scopeRepository_usesConfiguredScopes()
    {
        $this->settings->defaultScopes->setValue(['matomo:write', 'matomo:superuser']);
        $this->settings->save();

        $repository = new ScopeRepository(new SystemSettings());

        $this->assertSame(
            [
                'matomo:write' => ScopeRepository::DESCRIPTIONS['matomo:write'],
                'matomo:superuser' => ScopeRepository::DESCRIPTIONS['matomo:superuser'],
            ],
            $repository->describeScopes()
        );
    }
}

SystemSettingsTest::$fixture = new OAuth2Fixture();
