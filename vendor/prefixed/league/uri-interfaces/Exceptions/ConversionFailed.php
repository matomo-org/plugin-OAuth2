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
namespace Matomo\Dependencies\OAuth2\League\Uri\Exceptions;

use Matomo\Dependencies\OAuth2\League\Uri\Idna\Error;
use Matomo\Dependencies\OAuth2\League\Uri\Idna\Result;
use Stringable;
final class ConversionFailed extends SyntaxError
{
    /**
     * @readonly
     * @var string
     */
    private $host;
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\League\Uri\Idna\Result
     */
    private $result;
    private function __construct(string $message, string $host, Result $result)
    {
        $this->host = $host;
        $this->result = $result;
        parent::__construct($message);
    }
    /**
     * @param \Stringable|string $host
     */
    public static function dueToIdnError($host, Result $result) : self
    {
        $reasons = array_map(function (Error $error) : string {
            return $error->description();
        }, $result->errors());
        return new self('Host `' . $host . '` is invalid: ' . implode('; ', $reasons) . '.', (string) $host, $result);
    }
    public function getHost() : string
    {
        return $this->host;
    }
    public function getResult() : Result
    {
        return $this->result;
    }
}
