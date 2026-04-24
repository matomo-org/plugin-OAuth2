<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer;

use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer;
use function hash_equals;
use function hash_hmac;
use function strlen;
abstract class Hmac implements Signer
{
    public final function sign(string $payload, Key $key) : string
    {
        $actualKeyLength = 8 * strlen($key->contents());
        $expectedKeyLength = $this->minimumBitsLengthForKey();
        if ($actualKeyLength < $expectedKeyLength) {
            throw InvalidKeyProvided::tooShort($expectedKeyLength, $actualKeyLength);
        }
        return hash_hmac($this->algorithm(), $payload, $key->contents(), \true);
    }
    public final function verify(string $expected, string $payload, Key $key) : bool
    {
        return hash_equals($expected, $this->sign($payload, $key));
    }
    /**
     * @internal
     *
     * @return non-empty-string
     */
    public abstract function algorithm() : string;
    /**
     * @internal
     *
     * @return positive-int
     */
    public abstract function minimumBitsLengthForKey() : int;
}
