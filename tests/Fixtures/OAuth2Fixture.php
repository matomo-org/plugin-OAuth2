<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Fixtures;

use Piwik\Tests\Framework\Fixture;

class OAuth2Fixture extends Fixture
{
    public string $dateTime = '2024-01-01 00:00:00';
    public int $idSite = 1;
    public $extraPluginsToLoad = ['OAuth2'];

    public function setUp(): void
    {
        Fixture::createSuperUser();

        if (!self::siteCreated($this->idSite)) {
            $this->idSite = self::createWebsite($this->dateTime);
        }
    }
}
