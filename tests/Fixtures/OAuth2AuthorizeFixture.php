<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Fixtures;

use Piwik\Plugins\OAuth2\OAuth2;
use Piwik\Tests\Fixtures\UITestFixture;

class OAuth2AuthorizeFixture extends UITestFixture
{
    public function setUp(): void
    {
        parent::setUp();

        OAuth2::setupRSAKeys(true);
        OAuth2::setEncryptionKey(true);
    }
}
