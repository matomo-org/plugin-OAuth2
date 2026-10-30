<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Integration;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Filesystem;
use Piwik\Log\NullLogger;
use Piwik\Option;
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
class DiscoveryFileTest extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public static $fixture = null;

    private string $documentRoot;

    private SystemSettings $settings;

    public function setUp(): void
    {
        parent::setUp();

        $this->documentRoot = StaticContainer::get('path.tmp') . '/oauth2-discovery-test';
        Filesystem::unlinkRecursive($this->documentRoot, true);
        Filesystem::mkdir($this->documentRoot);

        $this->settings = new SystemSettings();
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/');
        Option::delete(DiscoveryFile::OPTION_FILE_HASHES);
    }

    public function tearDown(): void
    {
        Filesystem::unlinkRecursive($this->documentRoot, true);

        parent::tearDown();
    }

    public function test_write_writesMetadataForConfiguredUrlWithHelperFiles()
    {
        $this->assertSame(DiscoveryFile::STATUS_WRITTEN, $this->makeDiscoveryFile()->write());

        $document = json_decode((string) file_get_contents($this->getFilePath()), true);
        $this->assertSame('https://matomo.example', $document['issuer']);
        $this->assertSame('https://matomo.example/index.php?module=OAuth2&action=token', $document['token_endpoint']);

        $this->assertStringContainsString('ForceType application/json', (string) file_get_contents($this->getDirectory() . '/.htaccess'));
        $this->assertStringContainsString('mimeType="application/json"', (string) file_get_contents($this->getDirectory() . '/web.config'));
    }

    public function test_write_isUnchangedWhenContentIsTheSame()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        $this->assertSame(DiscoveryFile::STATUS_UNCHANGED, $discoveryFile->write());
    }

    public function test_write_updatesItsOwnFileWhenSettingsChange()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        $this->settings->enableClientCredentials->setValue(false);

        $this->assertSame(DiscoveryFile::STATUS_WRITTEN, $discoveryFile->write());
        $document = json_decode((string) file_get_contents($this->getFilePath()), true);
        $this->assertNotContains('client_credentials', $document['grant_types_supported']);
    }

    public function test_write_skipsSubdirectoryInstalls()
    {
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/analytics/');

        $this->assertSame(DiscoveryFile::STATUS_SKIPPED_SUBDIRECTORY, $this->makeDiscoveryFile()->write());
        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_write_skipsWhenMatomoUrlIsUnknown()
    {
        Option::delete(SettingsPiwik::OPTION_PIWIK_URL);

        $this->assertSame(DiscoveryFile::STATUS_SKIPPED_NO_URL, $this->makeDiscoveryFile()->write());
        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_write_keepsFilesNotWrittenByMatomoUnlessForced()
    {
        Filesystem::mkdir($this->getDirectory());
        file_put_contents($this->getFilePath(), '{"issuer":"https://custom.example"}');
        file_put_contents($this->getDirectory() . '/.htaccess', 'Options -Indexes');

        $discoveryFile = $this->makeDiscoveryFile();

        $this->assertSame(DiscoveryFile::STATUS_SKIPPED_FOREIGN_FILE, $discoveryFile->write());
        $this->assertSame('{"issuer":"https://custom.example"}', file_get_contents($this->getFilePath()));

        $this->assertSame(DiscoveryFile::STATUS_WRITTEN, $discoveryFile->write(true));
        $this->assertStringContainsString('https://matomo.example', (string) file_get_contents($this->getFilePath()));
        // A helper file written by somebody else is kept even when forced.
        $this->assertSame('Options -Indexes', file_get_contents($this->getDirectory() . '/.htaccess'));
    }

    public function test_write_doesNotTakeOverIdenticalFileNotWrittenByMatomo()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();
        $content = (string) file_get_contents($this->getFilePath());
        $discoveryFile->remove();
        // As if Matomo had never written it.
        Option::delete(DiscoveryFile::OPTION_FILE_HASHES);

        Filesystem::mkdir($this->getDirectory());
        file_put_contents($this->getFilePath(), $content);

        $this->assertSame(DiscoveryFile::STATUS_UNCHANGED, $discoveryFile->write());
        $this->assertFalse(Option::get(DiscoveryFile::OPTION_FILE_HASHES));
        $this->assertFileDoesNotExist($this->getDirectory() . '/.htaccess');

        $this->settings->enableClientCredentials->setValue(false);
        $this->assertSame(DiscoveryFile::STATUS_SKIPPED_FOREIGN_FILE, $discoveryFile->write());
        $this->assertSame($content, file_get_contents($this->getFilePath()));
    }

    public function test_write_takesOverIdenticalFileWhenForced()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();
        $content = (string) file_get_contents($this->getFilePath());
        $discoveryFile->remove();
        Option::delete(DiscoveryFile::OPTION_FILE_HASHES);

        Filesystem::mkdir($this->getDirectory());
        file_put_contents($this->getFilePath(), $content);

        $this->assertSame(DiscoveryFile::STATUS_UNCHANGED, $discoveryFile->write(true));
        $this->assertFileExists($this->getDirectory() . '/.htaccess');

        $this->settings->enableClientCredentials->setValue(false);
        $this->assertSame(DiscoveryFile::STATUS_WRITTEN, $discoveryFile->write());
    }

    public function test_write_removesItsFileWhenTurnedOff()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        Config::getInstance()->OAuth2 = ['write_discovery_file' => 0];

        $this->assertFalse($discoveryFile->isEnabled());
        $this->assertSame(DiscoveryFile::STATUS_DISABLED, $discoveryFile->write());
        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_write_keepsAnotherInstancesFileWhenTurnedOff()
    {
        // Another instance on the same codebase wrote it, so its hash is not in this database.
        Filesystem::mkdir($this->getDirectory());
        file_put_contents($this->getFilePath(), '{"issuer":"https://other-instance.example"}');
        Config::getInstance()->OAuth2 = ['write_discovery_file' => 0];

        $this->assertSame(DiscoveryFile::STATUS_DISABLED, $this->makeDiscoveryFile()->write(true));
        $this->assertFileExists($this->getFilePath());
    }

    public function test_isEnabled_isOffByDefaultWithAHostSpecificConfig()
    {
        // Instances on one codebase share the document root, so none of them can own the file.
        $GLOBALS['CONFIG_INI_PATH_RESOLVER'] = static function (): string {
            return PIWIK_USER_PATH . '/config/analytics.example.config.ini.php';
        };

        try {
            $this->assertFalse($this->makeDiscoveryFile()->isEnabled());
            $this->assertSame(DiscoveryFile::STATUS_DISABLED, $this->makeDiscoveryFile()->write());
            $this->assertFileDoesNotExist($this->getFilePath());

            Config::getInstance()->OAuth2 = ['write_discovery_file' => 1];
            $this->assertTrue($this->makeDiscoveryFile()->isEnabled());
        } finally {
            unset($GLOBALS['CONFIG_INI_PATH_RESOLVER']);
        }
    }

    public function test_isEnabled_isOnByDefaultWithTheDefaultConfig()
    {
        $this->assertTrue($this->makeDiscoveryFile()->isEnabled());
    }

    public function test_write_usesTheChangedMatomoUrl()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://analytics.example/');

        $this->assertSame(DiscoveryFile::STATUS_WRITTEN, $discoveryFile->write());
        $document = json_decode((string) file_get_contents($this->getFilePath()), true);
        $this->assertSame('https://analytics.example', $document['issuer']);
    }

    public function test_remove_keepsOwnershipWhenFileCannotBeRemoved()
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root can remove files from a read-only directory');
        }

        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        chmod($this->getDirectory(), 0555);
        try {
            $discoveryFile->remove();
        } finally {
            chmod($this->getDirectory(), 0755);
        }

        $this->assertFileExists($this->getFilePath());

        $discoveryFile->remove();
        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_remove_removesOnlyFilesWrittenByMatomo()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();
        Filesystem::mkdir($this->getDirectory() . '/acme-challenge');

        $discoveryFile->remove();

        $this->assertFileDoesNotExist($this->getFilePath());
        $this->assertFileDoesNotExist($this->getDirectory() . '/.htaccess');
        $this->assertFileDoesNotExist($this->getDirectory() . '/web.config');
        $this->assertDirectoryExists($this->getDirectory() . '/acme-challenge');
    }

    public function test_olderCopyWrittenByMatomoIsStillUpdatedAndRemoved()
    {
        // E.g. a second web node that still has the file from before the last settings change.
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();
        $olderCopy = (string) file_get_contents($this->getFilePath());

        $this->settings->enableClientCredentials->setValue(false);
        $discoveryFile->write();
        file_put_contents($this->getFilePath(), $olderCopy);

        $this->settings->enableRefreshTokens->setValue(false);
        $this->assertSame(DiscoveryFile::STATUS_WRITTEN, $discoveryFile->write());

        file_put_contents($this->getFilePath(), $olderCopy);
        $discoveryFile->remove();
        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_write_removesItsFileWhenMatomoMovedToASubdirectory()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/analytics/');

        $this->assertSame(DiscoveryFile::STATUS_SKIPPED_SUBDIRECTORY, $discoveryFile->write());
        $this->assertFileDoesNotExist($this->getFilePath());
        $this->assertFileDoesNotExist($this->getDirectory() . '/.htaccess');
    }

    public function test_write_keepsFileNotWrittenByMatomoWhenSkipped()
    {
        Filesystem::mkdir($this->getDirectory());
        file_put_contents($this->getFilePath(), '{"issuer":"https://custom.example"}');
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/analytics/');

        $this->assertSame(DiscoveryFile::STATUS_SKIPPED_SUBDIRECTORY, $this->makeDiscoveryFile()->write());
        $this->assertFileExists($this->getFilePath());
    }

    public function test_remove_keepsFileNotWrittenByMatomo()
    {
        Filesystem::mkdir($this->getDirectory());
        file_put_contents($this->getFilePath(), '{"issuer":"https://custom.example"}');

        $this->makeDiscoveryFile()->remove();

        $this->assertFileExists($this->getFilePath());
    }

    public function test_savingSystemSettingsUpdatesTheFile()
    {
        // The test environment points the container's instance at a temporary document root.
        Filesystem::mkdir(StaticContainer::get('path.tmp') . '/oauth2-discovery');
        $discoveryFile = StaticContainer::get(DiscoveryFile::class);
        $discoveryFile->write();

        $settings = new SystemSettings();
        $settings->enableRefreshTokens->setValue(false);
        $settings->save();

        $document = json_decode((string) file_get_contents($discoveryFile->getFilePath()), true);
        $this->assertSame(['authorization_code', 'client_credentials'], $document['grant_types_supported']);

        $discoveryFile->remove();
    }

    private function makeDiscoveryFile(): DiscoveryFile
    {
        return new DiscoveryFile(
            new AuthorizationServerMetadata($this->settings, new ScopeRepository($this->settings)),
            new NullLogger(),
            $this->documentRoot
        );
    }

    private function getDirectory(): string
    {
        return $this->documentRoot . '/' . DiscoveryFile::DIRECTORY;
    }

    private function getFilePath(): string
    {
        return $this->getDirectory() . '/' . DiscoveryFile::FILE_NAME;
    }
}

DiscoveryFileTest::$fixture = new OAuth2Fixture();
