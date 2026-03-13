<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Commands;

use Piwik\Option;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\OAuth2\OAuth2;

class GenerateEncryptionKey extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('oauth2:generate-encryption-key');
        $this->setDescription('Setup Random 32+ character string to encrypt auth codes and refresh tokens, it will throw an error if key already exist');
        $this->addNoValueOption('force', null, 'Flag to forcefully create new Encryption keys.');
    }

    /**
     * The actual task is defined in this method. Here you can access any option or argument that was defined on the
     * command line via $this->getInput() and write anything to the console via $this->getOutput().
     * In case anything went wrong during the execution you should throw an exception to make sure the user will get a
     * useful error message and to make sure the command does not exit with the status code 0.
     *
     * Ideally, the actual command is quite short as it acts like a controller. It should only receive the input values,
     * execute the task by calling a method of another class and output any useful information.
     *
     * Execute the command like: ./console oauth2:generate-encryption-key --name="The Matomo Team"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $isForce = $input->getOption('force');

        $value = Option::get(OAuth2::OAUTH2_ENCRYPTION_OPTION_KEY);
        if ($value && !$isForce) {
            $output->writeln('<error>Key already setup, send --force parameter to forcefully generate new key.</error>');
            return self::FAILURE;
        }

        OAuth2::setEncryptionKey($isForce);

        $output->writeln('Generated new encryption key.');

        return self::SUCCESS;
    }
}
