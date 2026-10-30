<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\System\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Filesystem;
use Piwik\Option;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\SettingsPiwik;
use Piwik\Tests\Framework\TestCase\ConsoleCommandTestCase;

/**
 * @group OAuth2
 * @group DiscoveryFile
 * @group Plugins
 */
class WriteDiscoveryFileTest extends ConsoleCommandTestCase
{
    public static $fixture = null;

    private string $documentRoot;

    public function setUp(): void
    {
        parent::setUp();

        // The container's DiscoveryFile writes here in tests, see config/test.php.
        $this->documentRoot = StaticContainer::get('path.tmp') . '/oauth2-discovery';
        Filesystem::unlinkRecursive($this->documentRoot, true);
        Filesystem::mkdir($this->documentRoot);

        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/');
        Option::delete(DiscoveryFile::OPTION_FILE_HASHES);
    }

    public function tearDown(): void
    {
        @chmod($this->documentRoot, 0755);
        Filesystem::unlinkRecursive($this->documentRoot, true);

        parent::tearDown();
    }

    public function test_succeedsWhenTheFileIsWrittenOrUpToDate()
    {
        $this->assertSame(0, $this->runCommand());
        $this->assertStringContainsString('Wrote ', $this->applicationTester->getDisplay());

        $this->assertSame(0, $this->runCommand());
        $this->assertStringContainsString('is up to date', $this->applicationTester->getDisplay());
    }

    public function test_succeedsWhenTheFileIsSkipped()
    {
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/analytics/');

        $this->assertSame(0, $this->runCommand());
        $this->assertStringContainsString('installed in a subdirectory', $this->applicationTester->getDisplay());
    }

    public function test_keepsAFileNotWrittenByMatomoUnlessForced()
    {
        Filesystem::mkdir($this->documentRoot . '/' . DiscoveryFile::DIRECTORY);
        file_put_contents($this->getFilePath(), '{"issuer":"https://custom.example"}');

        $this->assertSame(0, $this->runCommand());
        $this->assertStringContainsString('Use --force', $this->applicationTester->getDisplay());

        $this->assertSame(0, $this->runCommand(['--force' => true]));
        $this->assertStringContainsString('https://matomo.example', (string) file_get_contents($this->getFilePath()));
    }

    public function test_failsWhenTheDirectoryIsNotWritable()
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root can write to a read-only directory');
        }

        chmod($this->documentRoot, 0555);

        $this->assertSame(1, $this->runCommand());
        $this->assertStringContainsString('Could not write', $this->applicationTester->getDisplay());
    }

    private function runCommand(array $options = []): int
    {
        return $this->applicationTester->run(array_merge(['command' => 'oauth2:write-discovery-file'], $options));
    }

    private function getFilePath(): string
    {
        return $this->documentRoot . '/' . DiscoveryFile::DIRECTORY . '/' . DiscoveryFile::FILE_NAME;
    }
}

WriteDiscoveryFileTest::$fixture = new OAuth2Fixture();
