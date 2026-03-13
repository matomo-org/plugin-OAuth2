<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\League\Uri\Exceptions;

use InvalidArgumentException;
use Matomo\Dependencies\Oauth2\League\Uri\Contracts\UriException;
class SyntaxError extends InvalidArgumentException implements UriException
{
}
