<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\Hmac;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\Hmac;
final class Sha256 extends Hmac
{
    public function algorithmId() : string
    {
        return 'HS256';
    }
    public function algorithm() : string
    {
        return 'sha256';
    }
    public function minimumBitsLengthForKey() : int
    {
        return 256;
    }
}
