<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Container\StaticContainer;
use Piwik\Filesystem;
use Piwik\Option;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;
use Piwik\Plugins\OAuth2\Tasks;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Plugins\OAuth2\Updates_6_0_4;
use Piwik\SettingsPiwik;

require_once __DIR__ . '/../../Updates/6.0.4.php';

/**
 * Covers the places that write or remove the discovery file. The test environment points the
 * container's DiscoveryFile at a temporary document root, see config/test.php.
 *
 * @group OAuth2
 * @group DiscoveryFile
 * @group Plugins
 */
class DiscoveryFileHooksTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private string $documentRoot;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentRoot = StaticContainer::get('path.tmp') . '/oauth2-discovery';
        Filesystem::unlinkRecursive($this->documentRoot, true);
        Filesystem::mkdir($this->documentRoot);

        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/');
        Option::delete(DiscoveryFile::OPTION_FILE_HASHES);
    }

    public function tearDown(): void
    {
        Filesystem::unlinkRecursive($this->documentRoot, true);

        parent::tearDown();
    }

    public function test_activate_writesTheFile_andDeactivateRemovesIt()
    {
        $plugin = new OAuth2();

        $plugin->activate();
        $this->assertSame('https://matomo.example', $this->getWrittenIssuer());

        $plugin->deactivate();
        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_activatingOrDeactivatingAnotherPlugin_rewritesTheFile()
    {
        // Other plugins can add fields to the document, so their activation changes it.
        $plugin = new OAuth2();

        $plugin->onPluginActivationChanged('SomeOtherPlugin');
        $this->assertSame('https://matomo.example', $this->getWrittenIssuer());

        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://analytics.example/');
        $plugin->onPluginActivationChanged('SomeOtherPlugin');
        $this->assertSame('https://analytics.example', $this->getWrittenIssuer());
    }

    public function test_activatingOrDeactivatingThisPlugin_isLeftToItsOwnHooks()
    {
        (new OAuth2())->onPluginActivationChanged('OAuth2');

        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_update_writesTheFile()
    {
        Updates_6_0_4::writeDiscoveryFile();

        $this->assertSame('https://matomo.example', $this->getWrittenIssuer());
    }

    public function test_update_doesNotFailWhenTheFileCannotBeBuilt()
    {
        $discoveryFile = $this->createMock(DiscoveryFile::class);
        $discoveryFile->method('write')->willThrowException(new \RuntimeException('listener failed'));
        StaticContainer::getContainer()->set(DiscoveryFile::class, $discoveryFile);

        Updates_6_0_4::writeDiscoveryFile();

        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_scheduledTask_updatesTheIssuerAfterTheMatomoUrlChanged()
    {
        $tasks = StaticContainer::get(Tasks::class);
        $tasks->writeDiscoveryFile();

        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://analytics.example/');
        $tasks->writeDiscoveryFile();

        $this->assertSame('https://analytics.example', $this->getWrittenIssuer());
    }

    private function getWrittenIssuer(): ?string
    {
        $this->assertFileExists($this->getFilePath());
        $document = json_decode((string) file_get_contents($this->getFilePath()), true);

        return $document['issuer'] ?? null;
    }

    private function getFilePath(): string
    {
        return $this->documentRoot . '/' . DiscoveryFile::DIRECTORY . '/' . DiscoveryFile::FILE_NAME;
    }
}

DiscoveryFileHooksTest::$fixture = new OAuth2Fixture();
