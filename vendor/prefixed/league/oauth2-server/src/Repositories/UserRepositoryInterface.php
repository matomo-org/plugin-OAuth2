<?php

/**
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */
declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\OAuth2\Server\Repositories;

use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\ClientEntityInterface;
use Matomo\Dependencies\Oauth2\League\OAuth2\Server\Entities\UserEntityInterface;
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Get a user entity.
     */
    public function getUserEntityByUserCredentials(string $username, string $password, string $grantType, ClientEntityInterface $clientEntity) : ?UserEntityInterface;
}
