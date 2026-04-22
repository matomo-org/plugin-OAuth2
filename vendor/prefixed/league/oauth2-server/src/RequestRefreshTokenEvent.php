<?php

/**
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\OAuth2\Server;

use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use Matomo\Dependencies\OAuth2\Psr\Http\Message\ServerRequestInterface;
use SensitiveParameter;
class RequestRefreshTokenEvent extends RequestEvent
{
    /**
     * @var \Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\RefreshTokenEntityInterface
     */
    private $refreshToken;
    public function __construct(string $name, ServerRequestInterface $request, #[\SensitiveParameter]
    RefreshTokenEntityInterface $refreshToken)
    {
        $this->refreshToken = $refreshToken;
        parent::__construct($name, $request);
    }
    /**
     * @codeCoverageIgnore
     */
    public function getRefreshToken() : RefreshTokenEntityInterface
    {
        return $this->refreshToken;
    }
}
