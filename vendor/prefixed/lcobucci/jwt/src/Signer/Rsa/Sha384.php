<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer\Rsa;

use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Signer\Rsa;
use const OPENSSL_ALGO_SHA384;
final class Sha384 extends Rsa
{
    public function algorithmId() : string
    {
        return 'RS384';
    }
    public function algorithm() : int
    {
        return OPENSSL_ALGO_SHA384;
    }
}
