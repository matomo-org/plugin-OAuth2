<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Token\DataSet;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Token\Signature;
interface UnencryptedToken extends Token
{
    /**
     * Returns the token claims
     */
    public function claims() : DataSet;
    /**
     * Returns the token signature
     */
    public function signature() : Signature;
    /**
     * Returns the token payload
     *
     * @return non-empty-string
     */
    public function payload() : string;
}
