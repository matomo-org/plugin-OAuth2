<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\OAuth2\OAuth2;

class PauseClientsWithoutOwner extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('oauth2:pause-clients-without-owner');
        $this->setDescription('Pause the OAuth 2.0 clients whose owner was deleted, remove their credentials, and record each in the activity log');
    }

    protected function doExecute(): int
    {
        $count = (new OAuth2())->pauseClientsWithoutOwner();

        $this->getOutput()->writeln($count === 0
            ? 'No OAuth 2.0 client has a deleted owner.'
            : 'Paused ' . $count . ' OAuth 2.0 client(s) whose owner was deleted.');

        return self::SUCCESS;
    }
}
