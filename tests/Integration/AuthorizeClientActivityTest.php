<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Plugins\OAuth2\Activity\AuthorizeClient;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Translation\Translator;

/**
 * @group OAuth2
 * @group AuthorizeClientActivity
 * @group Plugins
 */
class AuthorizeClientActivityTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private AuthorizeClient $activity;

    public function setUp(): void
    {
        parent::setUp();

        // the descriptions are translated, so assert against the real strings rather than keys
        StaticContainer::get(Translator::class)->addDirectory(PIWIK_INCLUDE_PATH . '/plugins/OAuth2/lang');

        $this->activity = new AuthorizeClient();
    }

    public function test_getTranslatedDescription_namesTheGrantedAndTheRequestedScopes()
    {
        $description = $this->activity->getTranslatedDescription($this->activityData([
            'scopes' => ['matomo:write'],
            'requestedScopes' => ['matomo:read', 'matomo:write', 'matomo:admin'],
            'decision' => 'allowed',
        ]), 'superUserLogin');

        $this->assertSame(
            'allowed OAuth 2.0 authorization request for client "Claude Code Demo (c0dec0dec0dec0dec0dec0dec0dec0de)" with scope matomo:write'
                . ', requested matomo:read, matomo:write, matomo:admin',
            $description
        );
    }

    public function test_getTranslatedDescription_namesOnlyTheRequestedScopesWhenDenied()
    {
        $description = $this->activity->getTranslatedDescription($this->activityData([
            'scopes' => [],
            'requestedScopes' => ['matomo:read', 'matomo:write'],
            'decision' => 'denied',
        ]), 'superUserLogin');

        $this->assertSame(
            'denied OAuth 2.0 authorization request for client "Claude Code Demo (c0dec0dec0dec0dec0dec0dec0dec0de)"'
                . ', requested matomo:read, matomo:write',
            $description
        );
    }

    public function test_getTranslatedDescription_describesDecisionsRecordedBeforeScopesWereStored()
    {
        // rows written by earlier versions carry neither the granted nor the requested scopes
        $description = $this->activity->getTranslatedDescription($this->activityData([
            'decision' => 'allowed',
        ]), 'superUserLogin');

        $this->assertSame(
            'allowed OAuth 2.0 authorization request for client "Claude Code Demo (c0dec0dec0dec0dec0dec0dec0dec0de)"',
            $description
        );
    }

    private function activityData(array $data): array
    {
        return array_merge([
            'version' => 'v1',
            'client' => ['id' => 'c0dec0dec0dec0dec0dec0dec0dec0de', 'name' => 'Claude Code Demo'],
            'userLogin' => 'superUserLogin',
        ], $data);
    }
}

AuthorizeClientActivityTest::$fixture = new OAuth2Fixture();
