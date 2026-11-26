<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\tests\Framework;

use Piwik\Option;
use Piwik\Plugins\OAuth2\OAuth2;

class OAuth2TestKeys
{
    /** @var array{string, string}|null */
    private static ?array $keyPair = null;

    /**
     * Stores an RSA key pair and a fresh encryption key. The key pair is generated once per process
     * and reused, as generating a 4096-bit key takes around half a second.
     */
    public static function install(): void
    {
        if (self::$keyPair === null) {
            if (!OAuth2::setupRSAKeys(true)) {
                throw new \RuntimeException('Could not generate the OAuth2 test RSA key pair');
            }
            self::$keyPair = [
                Option::get(OAuth2::OAUTH2_PRIVATE_OPTION_KEY),
                Option::get(OAuth2::OAUTH2_PUBLIC_OPTION_KEY),
            ];
        } else {
            Option::set(OAuth2::OAUTH2_PRIVATE_OPTION_KEY, self::$keyPair[0]);
            Option::set(OAuth2::OAUTH2_PUBLIC_OPTION_KEY, self::$keyPair[1]);
        }

        OAuth2::setEncryptionKey(true);
    }
}
