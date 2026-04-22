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
namespace Matomo\Dependencies\OAuth2\League\Uri\IPv4;

use function floor;
use function intval;
final class NativeCalculator implements Calculator
{
    /**
     * @param mixed $value
     */
    public function baseConvert($value, int $base) : int
    {
        return intval((string) $value, $base);
    }
    /**
     * @param mixed $value
     */
    public function pow($value, int $exponent)
    {
        return $value ** $exponent;
    }
    /**
     * @param mixed $value1
     * @param mixed $value2
     */
    public function compare($value1, $value2) : int
    {
        return $value1 <=> $value2;
    }
    /**
     * @param mixed $value1
     * @param mixed $value2
     */
    public function multiply($value1, $value2) : int
    {
        return $value1 * $value2;
    }
    /**
     * @param mixed $value
     * @param mixed $base
     */
    public function div($value, $base) : int
    {
        return (int) floor($value / $base);
    }
    /**
     * @param mixed $value
     * @param mixed $base
     */
    public function mod($value, $base) : int
    {
        return $value % $base;
    }
    /**
     * @param mixed $value1
     * @param mixed $value2
     */
    public function add($value1, $value2) : int
    {
        return $value1 + $value2;
    }
    /**
     * @param mixed $value1
     * @param mixed $value2
     */
    public function sub($value1, $value2) : int
    {
        return $value1 - $value2;
    }
}
