<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\Hmac;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\Hmac;
final class Sha384 extends Hmac
{
    public function algorithmId() : string
    {
        return 'HS384';
    }
    public function algorithm() : string
    {
        return 'sha384';
    }
    public function minimumBitsLengthForKey() : int
    {
        return 384;
    }
}
