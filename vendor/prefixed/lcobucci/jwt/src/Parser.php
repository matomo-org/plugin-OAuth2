<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT;

use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Encoding\CannotDecodeContent;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token\InvalidTokenStructure;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token\UnsupportedHeaderFound;
interface Parser
{
    /**
     * Parses the JWT and returns a token
     *
     * @param non-empty-string $jwt
     *
     * @throws CannotDecodeContent      When something goes wrong while decoding.
     * @throws InvalidTokenStructure    When token string structure is invalid.
     * @throws UnsupportedHeaderFound   When parsed token has an unsupported header.
     */
    public function parse(string $jwt) : Token;
}
