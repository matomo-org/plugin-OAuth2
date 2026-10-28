<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Commands;

use Piwik\Container\StaticContainer;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;

class WriteDiscoveryFile extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('oauth2:write-discovery-file');
        $this->setDescription('Write the OAuth 2.0 discovery document to .well-known/oauth-authorization-server, so clients can discover the authorization server without a web server rewrite rule');
        $this->addNoValueOption('force', null, 'Also replace a file that was not written by Matomo.');
    }

    protected function doExecute(): int
    {
        $output = $this->getOutput();

        /** @var DiscoveryFile $discoveryFile */
        $discoveryFile = StaticContainer::get(DiscoveryFile::class);
        $status = $discoveryFile->write((bool) $this->getInput()->getOption('force'));
        $path = $discoveryFile->getFilePath();

        switch ($status) {
            case DiscoveryFile::STATUS_WRITTEN:
                $output->writeln('Wrote ' . $path . '.');
                return self::SUCCESS;
            case DiscoveryFile::STATUS_UNCHANGED:
                $output->writeln($path . ' is up to date.');
                return self::SUCCESS;
            case DiscoveryFile::STATUS_SKIPPED_NO_URL:
                $output->writeln('<comment>The Matomo URL is not known yet. Open Matomo in a browser once, then run this command again.</comment>');
                return self::SUCCESS;
            case DiscoveryFile::STATUS_SKIPPED_SUBDIRECTORY:
                $output->writeln('<comment>Matomo is installed in a subdirectory, so the discovery URL is at the domain root. Add a web server rule instead, see the plugin README.</comment>');
                return self::SUCCESS;
            case DiscoveryFile::STATUS_SKIPPED_FOREIGN_FILE:
                $output->writeln('<comment>' . $path . ' was not written by Matomo and was left unchanged. Use --force to replace it.</comment>');
                return self::SUCCESS;
            default:
                $output->writeln('<error>Could not write ' . $path . '. Make sure the directory is writable, or add a web server rule instead, see the plugin README.</error>');
                return self::FAILURE;
        }
    }
}
