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

use Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Matomo\Dependencies\OAuth2\Psr\Http\Message\ServerRequestInterface;
use SensitiveParameter;
class RequestAccessTokenEvent extends RequestEvent
{
    /**
     * @var \Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities\AccessTokenEntityInterface
     */
    private $accessToken;
    public function __construct(string $name, ServerRequestInterface $request, #[\SensitiveParameter]
    AccessTokenEntityInterface $accessToken)
    {
        $this->accessToken = $accessToken;
        parent::__construct($name, $request);
    }
    /**
     * @codeCoverageIgnore
     */
    public function getAccessToken() : AccessTokenEntityInterface
    {
        return $this->accessToken;
    }
}
