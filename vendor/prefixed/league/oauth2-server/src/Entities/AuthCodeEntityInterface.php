<?php

/**
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\League\OAuth2\Server\Entities;

interface AuthCodeEntityInterface extends TokenInterface
{
    public function getRedirectUri() : ?string;
    public function setRedirectUri(string $uri) : void;
}
