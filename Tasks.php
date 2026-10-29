<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2;

use Piwik\Plugins\OAuth2\Service\DiscoveryFile;

class Tasks extends \Piwik\Plugin\Tasks
{
    public function __construct(private DiscoveryFile $discoveryFile)
    {
    }

    public function schedule(): void
    {
        $this->daily('writeDiscoveryFile');
    }

    /**
     * Nothing notifies the plugin when the Matomo URL changes (new domain, or http to https), so
     * the file's issuer is checked daily. write() only replaces the file when its content changed.
     */
    public function writeDiscoveryFile(): void
    {
        $this->discoveryFile->write();
    }
}
