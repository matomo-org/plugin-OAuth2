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

use GMP;
use function gmp_add;
use function gmp_cmp;
use function gmp_div_q;
use function gmp_init;
use function gmp_mod;
use function gmp_mul;
use function gmp_pow;
use function gmp_sub;
use const GMP_ROUND_MINUSINF;
final class GMPCalculator implements Calculator
{
    /**
     * @param mixed $value
     */
    public function baseConvert($value, int $base) : GMP
    {
        return gmp_init($value, $base);
    }
    /**
     * @param mixed $value
     */
    public function pow($value, int $exponent) : GMP
    {
        return gmp_pow($value, $exponent);
    }
    /**
     * @param mixed $value1
     * @param mixed $value2
     */
    public function compare($value1, $value2) : int
    {
        return gmp_cmp($value1, $value2);
    }
    /**
     * @param mixed $value1
     * @param mixed $value2
     */
    public function multiply($value1, $value2) : GMP
    {
        return gmp_mul($value1, $value2);
    }
    /**
     * @param mixed $value
     * @param mixed $base
     */
    public function div($value, $base) : GMP
    {
        return gmp_div_q($value, $base, GMP_ROUND_MINUSINF);
    }
    /**
     * @param mixed $value
     * @param mixed $base
     */
    public function mod($value, $base) : GMP
    {
        return gmp_mod($value, $base);
    }
    /**
     * @param mixed $value1
     * @param mixed $value2
     */
    public function add($value1, $value2) : GMP
    {
        return gmp_add($value1, $value2);
    }
    /**
     * @param mixed $value1
     * @param mixed $value2
     */
    public function sub($value1, $value2) : GMP
    {
        return gmp_sub($value1, $value2);
    }
}
