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
use Piwik\Log\LoggerInterface;
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
        $previousResolver = $GLOBALS['CONFIG_INI_PATH_RESOLVER'] ?? null;
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
            $GLOBALS['CONFIG_INI_PATH_RESOLVER'] = $previousResolver;
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

    public function test_write_recognisesTheFileEvenWhenWritingItFailed()
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root can write to a read-only directory');
        }

        // The hash is stored before the file is put in place, so a file that ends up written
        // without the rest of write() completing is still recognised as Matomo's.
        chmod($this->documentRoot, 0555);
        try {
            $this->assertSame(DiscoveryFile::STATUS_NOT_WRITABLE, $this->makeDiscoveryFile()->write());
        } finally {
            chmod($this->documentRoot, 0755);
        }

        $metadata = new AuthorizationServerMetadata($this->settings, new ScopeRepository($this->settings));
        Filesystem::mkdir($this->getDirectory());
        file_put_contents($this->getFilePath(), json_encode($metadata->build('https://matomo.example'), JSON_UNESCAPED_SLASHES));

        $this->makeDiscoveryFile()->remove();
        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function test_write_leavesUnchangedHelperFilesInPlace()
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();
        $helperInode = fileinode($this->getDirectory() . '/.htaccess');

        $this->settings->enableClientCredentials->setValue(false);
        $this->assertSame(DiscoveryFile::STATUS_WRITTEN, $discoveryFile->write());

        clearstatcache();
        $this->assertSame($helperInode, fileinode($this->getDirectory() . '/.htaccess'));
    }

    public function test_removeOnUninstall_keepsTheHashesWhenTheFileCannotBeRemoved()
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root can remove files from a read-only directory');
        }

        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        chmod($this->getDirectory(), 0555);
        try {
            $discoveryFile->removeOnUninstall();
        } finally {
            chmod($this->getDirectory(), 0755);
        }

        // Still recognised, so it is removed once possible instead of being left public for good.
        $this->assertNotFalse(Option::get(DiscoveryFile::OPTION_FILE_HASHES));

        $discoveryFile->removeOnUninstall();
        $this->assertFileDoesNotExist($this->getFilePath());
        $this->assertFalse(Option::get(DiscoveryFile::OPTION_FILE_HASHES));
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

    public function test_write_reportsAFileThatShouldNoLongerBeServedButCannotBeRemoved()
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root can remove files from a read-only directory');
        }

        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/analytics/');

        chmod($this->getDirectory(), 0555);
        try {
            $this->assertSame(DiscoveryFile::STATUS_NOT_REMOVABLE, $discoveryFile->write());
        } finally {
            chmod($this->getDirectory(), 0755);
        }
        $this->assertFileExists($this->getFilePath());
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

        try {
            $settings = new SystemSettings();
            $settings->enableRefreshTokens->setValue(false);
            $settings->save();

            $document = json_decode((string) file_get_contents($discoveryFile->getFilePath()), true);
            $this->assertSame(['authorization_code', 'client_credentials'], $document['grant_types_supported']);
        } finally {
            $discoveryFile->remove();
        }
    }

    /**
     * @dataProvider getUrlsStoredFromAForwardedDiscoveryRequest
     */
    public function test_write_ignoresAMatomoUrlStoredFromAForwardedDiscoveryRequest(string $storedUrl)
    {
        $discoveryFile = $this->makeDiscoveryFile();
        $discoveryFile->write();

        // What Matomo stores when a web server rule forwards the discovery URL to index.php.
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, $storedUrl);

        $this->assertNull($discoveryFile->getIssuer());
        $this->assertSame(DiscoveryFile::STATUS_SKIPPED_NO_URL, $discoveryFile->write());
        $this->assertFileDoesNotExist($this->getFilePath());
    }

    public function getUrlsStoredFromAForwardedDiscoveryRequest(): iterable
    {
        yield 'root install' => ['https://matomo.example/.well-known/'];
        yield 'subdirectory install' => ['https://matomo.example/.well-known/oauth-authorization-server/'];
    }

    public function test_getIssuer_keepsAPathThatOnlyStartsLikeTheWellKnownDirectory()
    {
        Option::set(SettingsPiwik::OPTION_PIWIK_URL, 'https://matomo.example/.well-knownish/');

        $this->assertSame('https://matomo.example/.well-knownish', $this->makeDiscoveryFile()->getIssuer());
    }

    public function test_write_failsWithoutWritingWhenTheDocumentCannotBeEncoded()
    {
        $metadata = $this->createMock(AuthorizationServerMetadata::class);
        $metadata->method('build')->willReturn(['issuer' => "\xB1"]);
        $discoveryFile = new DiscoveryFile($metadata, new NullLogger(), $this->documentRoot);

        try {
            $discoveryFile->write();
            $this->fail('Expected a JsonException.');
        } catch (\JsonException $e) {
            $this->assertFileDoesNotExist($this->getFilePath());
        }
    }

    public function test_tryWrite_logsInsteadOfThrowing()
    {
        $metadata = $this->createMock(AuthorizationServerMetadata::class);
        $metadata->method('build')->willThrowException(new \RuntimeException('listener failed'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')
            ->with($this->stringContains('Could not write the discovery file'));

        (new DiscoveryFile($metadata, $logger, $this->documentRoot))->tryWrite();

        $this->assertFileDoesNotExist($this->getFilePath());
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
