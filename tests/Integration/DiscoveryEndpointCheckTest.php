<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Log\NullLogger;
use Piwik\Option;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Piwik\Plugins\OAuth2\Diagnostic\DiscoveryEndpointCheck;
use Piwik\Plugins\OAuth2\Repositories\ScopeRepository;
use Piwik\Plugins\OAuth2\Service\AuthorizationServerMetadata;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;
use Piwik\Plugins\OAuth2\SystemSettings;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\SettingsPiwik;

/**
 * @group OAuth2
 * @group DiscoveryFile
 * @group Plugins
 */
class DiscoveryEndpointCheckTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private AuthorizationServerMetadata $metadata;

    public function setUp(): void
    {
        parent::setUp();

        $settings = new SystemSettings();
        $this->metadata = new AuthorizationServerMetadata($settings, new ScopeRepository($settings));
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/');
    }

    public function test_getDiscoveryUrl_insertsWellKnownSegmentBeforeIssuerPath()
    {
        $this->assertSame('https://matomo.example/.well-known/oauth-authorization-server', DiscoveryEndpointCheck::getDiscoveryUrl('https://matomo.example'));
        $this->assertSame('https://matomo.example:8443/.well-known/oauth-authorization-server/analytics', DiscoveryEndpointCheck::getDiscoveryUrl('https://matomo.example:8443/analytics'));
    }

    public function test_execute_isOkWhenTheCurrentDocumentIsServed()
    {
        $check = $this->makeCheck(200, (string) json_encode($this->metadata->build('https://matomo.example')));

        $result = $check->execute()[0];

        $this->assertSame(DiagnosticResult::STATUS_OK, $result->getStatus());
        $this->assertSame(['https://matomo.example/.well-known/oauth-authorization-server'], $check->requestedUrls);
    }

    public function test_execute_warnsWithRuleForDetectedServerWhenNotFound()
    {
        $result = $this->makeCheck(404, 'Not Found', 'nginx/1.24.0')->execute()[0];

        $this->assertSame(DiagnosticResult::STATUS_WARNING, $result->getStatus());
        $comment = $result->getItems()[0]->getComment();
        $this->assertStringContainsString('HTTP status 404', $comment);
        $this->assertStringContainsString('oauth2:write-discovery-file', $comment);
        $this->assertStringContainsString('location = /.well-known/oauth-authorization-server {', $comment);
        $this->assertStringContainsString('rewrite ^ /index.php?module=OAuth2&amp;action=metadata last;', $comment);
    }

    public function test_execute_suggestsDomainRootRuleForSubdirectoryInstalls()
    {
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/analytics/');
        $check = $this->makeCheck(404, '', 'Apache/2.4.52 (Ubuntu)');

        $comment = $check->execute()[0]->getItems()[0]->getComment();

        $this->assertSame(['https://matomo.example/.well-known/oauth-authorization-server/analytics'], $check->requestedUrls);
        $this->assertStringNotContainsString('oauth2:write-discovery-file', $comment);
        $this->assertStringContainsString(
            'RewriteRule ^\.well-known/oauth-authorization-server/analytics$ /analytics/index.php?module=OAuth2&amp;action=metadata [L,QSA]',
            $comment
        );
    }

    public function test_execute_escapesRegexCharactersOfTheSubdirectory()
    {
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/stats+(eu)/');

        $comment = $this->makeCheck(404, '', 'Apache/2.4.52')->execute()[0]->getItems()[0]->getComment();

        $this->assertStringContainsString('RewriteRule ^\.well-known/oauth-authorization-server/stats\+\(eu\)$ ', $comment);
    }

    public function test_execute_warnsWithNginxRuleWhenNotServedAsJson()
    {
        $document = (string) json_encode($this->metadata->build('https://matomo.example'));

        $result = $this->makeCheck(200, $document, 'nginx/1.24.0', null, 'application/octet-stream')->execute()[0];

        $this->assertSame(DiagnosticResult::STATUS_WARNING, $result->getStatus());
        $comment = $result->getItems()[0]->getComment();
        $this->assertStringContainsString('sent as "application/octet-stream"', $comment);
        $this->assertStringContainsString('default_type application/json;', $comment);
    }

    public function test_execute_acceptsJsonWithCharset()
    {
        $document = (string) json_encode($this->metadata->build('https://matomo.example'));

        $result = $this->makeCheck(200, $document, '', null, 'application/json; charset=utf-8')->execute()[0];

        $this->assertSame(DiagnosticResult::STATUS_OK, $result->getStatus());
    }

    public function test_execute_warnsAndEscapesWhenIssuerDoesNotMatch()
    {
        $result = $this->makeCheck(200, '{"issuer":"https://other.example/<b>"}')->execute()[0];

        $this->assertSame(DiagnosticResult::STATUS_WARNING, $result->getStatus());
        $this->assertStringContainsString('https://other.example/&lt;b&gt;', $result->getItems()[0]->getComment());
    }

    public function test_execute_warnsWhenServedDocumentIsOutdated()
    {
        $document = $this->metadata->build('https://matomo.example');
        $document['grant_types_supported'] = ['authorization_code'];

        $result = $this->makeCheck(200, (string) json_encode($document))->execute()[0];

        $this->assertSame(DiagnosticResult::STATUS_WARNING, $result->getStatus());
        $this->assertStringContainsString('outdated', $result->getItems()[0]->getComment());
    }

    public function test_execute_keepsMatomoUrlWhenRequestOverwritesIt()
    {
        // What a rewritten discovery request does when Matomo dispatches it.
        $check = $this->makeCheck(404, '', '', function () {
            Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/.well-known/');
        });

        $check->execute();

        Option::clearCachedOption(SettingsPiwik::OPTION_PIWIK_URL);
        $this->assertSame('https://matomo.example/', Option::get(SettingsPiwik::OPTION_PIWIK_URL));
    }

    private function makeCheck(
        int $status,
        string $body,
        string $serverSoftware = '',
        ?\Closure $onFetch = null,
        string $contentType = 'application/json'
    ): DiscoveryEndpointCheck {
        $discoveryFile = new DiscoveryFile($this->metadata, new NullLogger(), '/nonexistent');

        return new class ($discoveryFile, $this->metadata, $status, $body, $serverSoftware, $onFetch, $contentType) extends DiscoveryEndpointCheck {
            /** @var string[] */
            public array $requestedUrls = [];

            public function __construct(
                DiscoveryFile $discoveryFile,
                AuthorizationServerMetadata $metadata,
                private int $status,
                private string $body,
                private string $serverSoftware,
                private ?\Closure $onFetch,
                private string $contentType
            ) {
                parent::__construct($discoveryFile, $metadata);
            }

            protected function fetch(string $url): array
            {
                $this->requestedUrls[] = $url;
                if ($this->onFetch) {
                    ($this->onFetch)();
                }
                return ['status' => $this->status, 'data' => $this->body, 'contentType' => $this->contentType];
            }

            protected function getServerSoftware(): string
            {
                return strtolower($this->serverSoftware);
            }
        };
    }
}

DiscoveryEndpointCheckTest::$fixture = new OAuth2Fixture();
