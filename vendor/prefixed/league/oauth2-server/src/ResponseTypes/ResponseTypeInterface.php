<?php

/**
 * OAuth 2.0 Response Type Interface.
 *
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\OAuth2\Server\ResponseTypes;

use Matomo\Dependencies\OAuth2\Defuse\Crypto\Key;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use Matomo\Dependencies\OAuth2\Psr\Http\Message\ResponseInterface;
interface ResponseTypeInterface
{
    public function setAccessToken(AccessTokenEntityInterface $accessToken) : void;
    public function setRefreshToken(RefreshTokenEntityInterface $refreshToken) : void;
    public function generateHttpResponse(ResponseInterface $response) : ResponseInterface;
    public function setEncryptionKey(Key|string|null $key = null) : void;
}
