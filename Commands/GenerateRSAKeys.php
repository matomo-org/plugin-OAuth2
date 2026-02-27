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

class GenerateRSAKeys extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('oauth2:generate-rsa-keys');
        $this->setDescription('Setup public and private RSA keys used to sign access tokens, it will throw an error if keys already exist.');
        $this->addNoValueOption('force', null, 'Flag to forcefully create new RSA keys.');
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
     * Execute the command like: ./console oauth2:setup-r-s-a-keys --name="The Matomo Team"
     */
    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $isForce = $input->getOption('force');
        $privateKeyValue = Option::get(OAuth2::OAUTH2_PRIVATE_OPTION_KEY);
        $publicKeyValue = Option::get(OAuth2::OAUTH2_PUBLIC_OPTION_KEY);
        if (!empty($privateKeyValue) && !empty($publicKeyValue) && !$isForce) {
            $output->writeln('<error>Keys already setup, send --force parameter to forcefully generate new keys.</error>');
            return self::FAILURE;
        }

        if (
            !defined('OPENSSL_KEYTYPE_RSA')
            || !function_exists('openssl_pkey_new')
            || !function_exists('openssl_pkey_export')
            || !function_exists('openssl_pkey_get_details')
        ) {
            $output->writeln('<error>OpenSSL is not setup.</error>');
            return self::FAILURE;
        }

        if ($isForce) {
            $output->writeln('<info>Forcefully generating new keys</info>');
        }
        if (OAuth2::setupRSAKeys(false)) {
            $output->writeln('<info>Generated new keys.</info>');
            return self::SUCCESS;
        }

        $output->writeln('<error>Failed generating new keys.</error>');
        return self::FAILURE;
    }
}
