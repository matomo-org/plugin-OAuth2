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
use Piwik\Plugins\OAuth2\Activity\CreateClient;
use Piwik\Plugins\OAuth2\Activity\DeleteClient;
use Piwik\Plugins\OAuth2\Activity\DeleteClientWithOwner;
use Piwik\Plugins\OAuth2\Activity\RotateSecret;
use Piwik\Plugins\OAuth2\Activity\SetClientActive;
use Piwik\Plugins\OAuth2\Activity\UpdateClient;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Translation\Translator;

/**
 * @group OAuth2
 * @group ActivityDescriptions
 * @group Plugins
 */
class ActivityDescriptionsTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
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

    public function test_getTranslatedDescription_namesTheClientAndTheOwnerAUserDeletionRemoved()
    {
        $activity = new DeleteClientWithOwner();

        $description = $activity->getTranslatedDescription([
            'version' => 'v1',
            'client' => ['id' => 'c0dec0dec0dec0dec0dec0dec0dec0de', 'name' => 'Claude Code Demo'],
            'ownerLogin' => 'departedUser',
        ], 'superUserLogin');

        $this->assertSame(
            'deleted OAuth 2.0 client "Claude Code Demo (c0dec0dec0dec0dec0dec0dec0dec0de)"'
                . ' while deleting its owner "departedUser"',
            $description
        );
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

    public function test_getTranslatedDescription_namesOnlyTheGrantedScopeWhenRequestedScopesWereNotStored()
    {
        $description = $this->activity->getTranslatedDescription($this->activityData([
            'scopes' => ['matomo:write'],
            'decision' => 'allowed',
        ]), 'superUserLogin');

        $this->assertSame(
            'allowed OAuth 2.0 authorization request for client "Claude Code Demo (c0dec0dec0dec0dec0dec0dec0dec0de)"'
                . ' with scope matomo:write',
            $description
        );
    }

    public function test_getTranslatedDescription_describesDenialsRecordedBeforeScopesWereStored()
    {
        $description = $this->activity->getTranslatedDescription($this->activityData([
            'decision' => 'denied',
        ]), 'superUserLogin');

        $this->assertSame(
            'denied OAuth 2.0 authorization request for client "Claude Code Demo (c0dec0dec0dec0dec0dec0dec0dec0de)"',
            $description
        );
    }

    /**
     * @dataProvider getClientActivityDescriptions
     */
    public function test_getTranslatedDescription_describesTheClientActivities(
        string $activityClass,
        array $activityData,
        string $expected
    ) {
        $activity = new $activityClass();

        $description = $activity->getTranslatedDescription($this->activityData($activityData), 'superUserLogin');

        $this->assertSame($expected, $description);
    }

    public function getClientActivityDescriptions(): array
    {
        $label = 'Claude Code Demo (c0dec0dec0dec0dec0dec0dec0dec0de)';

        return [
            [CreateClient::class, [], 'created OAuth 2.0 client "' . $label . '"'],
            [UpdateClient::class, [], 'updated OAuth 2.0 client "' . $label . '"'],
            [DeleteClient::class, [], 'deleted OAuth 2.0 client "' . $label . '"'],
            [RotateSecret::class, [], 'rotated secret for OAuth 2.0 client "' . $label . '"'],
            [SetClientActive::class, ['client' => $this->client(true)], 'resumed OAuth 2.0 client "' . $label . '"'],
            [SetClientActive::class, ['client' => $this->client(false)], 'paused OAuth 2.0 client "' . $label . '"'],
        ];
    }

    private function client(bool $active = true): array
    {
        return [
            'id' => 'c0dec0dec0dec0dec0dec0dec0dec0de',
            'name' => 'Claude Code Demo',
            'active' => $active,
        ];
    }

    private function activityData(array $data): array
    {
        return array_merge([
            'version' => 'v1',
            'client' => $this->client(),
            'userLogin' => 'superUserLogin',
        ], $data);
    }
}

ActivityDescriptionsTest::$fixture = new OAuth2Fixture();
