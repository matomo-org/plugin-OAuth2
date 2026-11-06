<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Db;
use Piwik\Filesystem;
use Piwik\Log\LoggerInterface;
use Piwik\Option;
use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Plugins\OAuth2\Service\AuthorizationServerMetadata;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;
use Piwik\Plugins\OAuth2\Tasks;
use Piwik\Plugins\OAuth2\tests\Fixtures\OAuth2Fixture;
use Piwik\Plugins\OAuth2\Updates_5_4_0;
use Piwik\SettingsPiwik;

require_once __DIR__ . '/../../Updates/5.4.0.php';

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
        $this->assertFileNotExists($this->getFilePath());
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

        $this->assertFileNotExists($this->getFilePath());
    }

    public function test_update_writesTheFile()
    {
        Updates_5_4_0::writeDiscoveryFile();

        $this->assertSame('https://matomo.example', $this->getWrittenIssuer());
    }

    public function test_hooks_doNotFailWhenTheFileCannotBeBuilt()
    {
        // E.g. a listener of OAuth2.authorizationServerMetadata throws.
        $metadata = $this->createMock(AuthorizationServerMetadata::class);
        $metadata->method('build')->willThrowException(new \RuntimeException('listener failed'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(4))->method('warning');
        StaticContainer::getContainer()->set(
            DiscoveryFile::class,
            new DiscoveryFile($metadata, $logger, $this->documentRoot)
        );

        Updates_5_4_0::writeDiscoveryFile();
        StaticContainer::get(Tasks::class)->writeDiscoveryFile();
        (new OAuth2())->onPluginActivationChanged('SomeOtherPlugin');
        (new OAuth2())->writeDiscoveryFile();

        $this->assertFileNotExists($this->getFilePath());
    }

    public function test_hooks_doNotFailWhenTheServiceCannotBeCreated()
    {
        StaticContainer::getContainer()->set(DiscoveryFile::class, \Piwik\DI::factory(function () {
            throw new \Error('Interface not found');
        }));

        $plugin = new OAuth2();
        $plugin->activate();
        $plugin->onPluginActivationChanged('SomeOtherPlugin');
        $plugin->writeDiscoveryFile();
        Updates_5_4_0::writeDiscoveryFile();

        $this->assertFileNotExists($this->getFilePath());
    }

    public function test_uninstall_dropsTheTablesWhenTheFileCannotBeRemoved()
    {
        // E.g. the plugin's dependencies cannot be loaded while it is inactive.
        $plugin = new OAuth2();
        $plugin->activate();
        StaticContainer::getContainer()->set(DiscoveryFile::class, \Piwik\DI::factory(function () {
            throw new \Error('Interface not found');
        }));

        try {
            $plugin->deactivate();
            $plugin->uninstall();

            $this->assertSame([], Db::fetchAll("SHOW TABLES LIKE '" . Common::prefixTable('oauth2_client') . "'"));
        } finally {
            $plugin->install();
        }
        $this->assertFileExists($this->getFilePath());
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
