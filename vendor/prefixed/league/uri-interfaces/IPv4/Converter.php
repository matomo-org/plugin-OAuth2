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

use Matomo\Dependencies\OAuth2\League\Uri\Exceptions\MissingFeature;
use Matomo\Dependencies\OAuth2\League\Uri\FeatureDetection;
use Stringable;
use function array_pop;
use function count;
use function explode;
use function extension_loaded;
use function hexdec;
use function long2ip;
use function ltrim;
use function preg_match;
use function str_ends_with;
use function substr;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
final class Converter
{
    /**
     * @readonly
     * @var \Matomo\Dependencies\OAuth2\League\Uri\IPv4\Calculator
     */
    private $calculator;
    private const REGEXP_IPV4_HOST = '/
        (?(DEFINE) # . is missing as it is used to separate labels
            (?<hexadecimal>0x[[:xdigit:]]*)
            (?<octal>0[0-7]*)
            (?<decimal>\\d+)
            (?<ipv4_part>(?:(?&hexadecimal)|(?&octal)|(?&decimal))*)
        )
        ^(?:(?&ipv4_part)\\.){0,3}(?&ipv4_part)\\.?$
    /x';
    private const REGEXP_IPV4_NUMBER_PER_BASE = ['/^0x(?<number>[[:xdigit:]]*)$/' => 16, '/^0(?<number>[0-7]*)$/' => 8, '/^(?<number>\\d+)$/' => 10];
    private const IPV6_6TO4_PREFIX = '2002:';
    private const IPV4_MAPPED_PREFIX = '::ffff:';
    /**
     * @readonly
     * @var mixed
     */
    private $maxIPv4Number;
    public function __construct(Calculator $calculator)
    {
        $this->calculator = $calculator;
        $this->maxIPv4Number = $calculator->sub($calculator->pow(2, 32), 1);
    }
    /**
     * Returns an instance using a GMP calculator.
     */
    public static function fromGMP() : self
    {
        return new self(new GMPCalculator());
    }
    /**
     * Returns an instance using a Bcmath calculator.
     */
    public static function fromBCMath() : self
    {
        return new self(new BCMathCalculator());
    }
    /**
     * Returns an instance using a PHP native calculator (requires 64bits PHP).
     */
    public static function fromNative() : self
    {
        return new self(new NativeCalculator());
    }
    /**
     * Returns an instance using a detected calculator depending on the PHP environment.
     *
     * @throws MissingFeature If no Calculator implementing object can be used on the platform
     *
     * @codeCoverageIgnore
     */
    public static function fromEnvironment() : self
    {
        FeatureDetection::supportsIPv4Conversion();
        switch (\true) {
            case extension_loaded('gmp'):
                return self::fromGMP();
            case extension_loaded('bcmath'):
                return self::fromBCMath();
            default:
                return self::fromNative();
        }
    }
    /**
     * @param \Stringable|string|null $host
     */
    public function isIpv4($host) : bool
    {
        if (null === $host) {
            return \false;
        }
        if (null !== $this->toDecimal($host)) {
            return \true;
        }
        $host = (string) $host;
        if (\false === filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return \false;
        }
        $ipAddress = strtolower((string) inet_ntop((string) inet_pton($host)));
        if (strncmp($ipAddress, self::IPV4_MAPPED_PREFIX, strlen(self::IPV4_MAPPED_PREFIX)) === 0) {
            return \false !== filter_var(substr($ipAddress, 7), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        }
        if (strncmp($ipAddress, self::IPV6_6TO4_PREFIX, strlen(self::IPV6_6TO4_PREFIX)) !== 0) {
            return \false;
        }
        $hexParts = explode(':', substr($ipAddress, 5, 9));
        if (count($hexParts) < 2) {
            return \false;
        }
        $ipAddress = long2ip((int) hexdec($hexParts[0]) * 65536 + (int) hexdec($hexParts[1]));
        return '' !== '' . $ipAddress;
    }
    /**
     * @param \Stringable|string|null $host
     */
    public function toIPv6Using6to4($host) : ?string
    {
        $host = $this->toDecimal($host);
        if (null === $host) {
            return null;
        }
        /** @var array<string> $parts */
        $parts = array_map(function (string $part) : string {
            return sprintf('%02x', $part);
        }, explode('.', $host));
        return '[' . self::IPV6_6TO4_PREFIX . $parts[0] . $parts[1] . ':' . $parts[2] . $parts[3] . '::]';
    }
    /**
     * @param \Stringable|string|null $host
     */
    public function toIPv6UsingMapping($host) : ?string
    {
        $host = $this->toDecimal($host);
        if (null === $host) {
            return null;
        }
        return '[' . self::IPV4_MAPPED_PREFIX . $host . ']';
    }
    /**
     * @param \Stringable|string|null $host
     */
    public function toOctal($host) : ?string
    {
        $host = $this->toDecimal($host);
        switch (null) {
            case $host:
                return null;
            default:
                return implode('.', array_map(function ($value) {
                    return str_pad(decoct((int) $value), 4, '0', \STR_PAD_LEFT);
                }, explode('.', $host)));
        }
    }
    /**
     * @param \Stringable|string|null $host
     */
    public function toHexadecimal($host) : ?string
    {
        $host = $this->toDecimal($host);
        switch (null) {
            case $host:
                return null;
            default:
                return '0x' . implode('', array_map(function ($value) {
                    return dechex((int) $value);
                }, explode('.', $host)));
        }
    }
    /**
     * Tries to convert a IPv4 hexadecimal or a IPv4 octal notation into a IPv4 dot-decimal notation if possible
     * otherwise returns null.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     * @param \Stringable|string|null $host
     */
    public function toDecimal($host) : ?string
    {
        $host = (string) $host;
        if (strncmp($host, '[', strlen('[')) === 0 && substr_compare($host, ']', -strlen(']')) === 0) {
            $host = substr($host, 1, -1);
            if (\false === filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return null;
            }
            $ipAddress = strtolower((string) inet_ntop((string) inet_pton($host)));
            if (strncmp($ipAddress, self::IPV4_MAPPED_PREFIX, strlen(self::IPV4_MAPPED_PREFIX)) === 0) {
                return substr($ipAddress, 7);
            }
            if (strncmp($ipAddress, self::IPV6_6TO4_PREFIX, strlen(self::IPV6_6TO4_PREFIX)) !== 0) {
                return null;
            }
            $hexParts = explode(':', substr($ipAddress, 5, 9));
            switch (\true) {
                case count($hexParts) < 2:
                    return null;
                default:
                    return long2ip((int) hexdec($hexParts[0]) * 65536 + (int) hexdec($hexParts[1]));
            }
        }
        if (1 !== preg_match(self::REGEXP_IPV4_HOST, $host)) {
            return null;
        }
        if (substr_compare($host, '.', -strlen('.')) === 0) {
            $host = substr($host, 0, -1);
        }
        $numbers = [];
        foreach (explode('.', $host) as $label) {
            $number = $this->labelToNumber($label);
            if (null === $number) {
                return null;
            }
            $numbers[] = $number;
        }
        $ipv4 = array_pop($numbers);
        $max = $this->calculator->pow(256, 6 - count($numbers));
        if ($this->calculator->compare($ipv4, $max) > 0) {
            return null;
        }
        foreach ($numbers as $offset => $number) {
            if ($this->calculator->compare($number, 255) > 0) {
                return null;
            }
            $ipv4 = $this->calculator->add($ipv4, $this->calculator->multiply($number, $this->calculator->pow(256, 3 - $offset)));
        }
        return $this->long2Ip($ipv4);
    }
    /**
     * Converts a domain label into a IPv4 integer part.
     *
     * @see https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @return mixed returns null if it cannot correctly convert the label
     */
    private function labelToNumber(string $label)
    {
        foreach (self::REGEXP_IPV4_NUMBER_PER_BASE as $regexp => $base) {
            if (1 !== preg_match($regexp, $label, $matches)) {
                continue;
            }
            $number = ltrim($matches['number'], '0');
            if ('' === $number) {
                return 0;
            }
            $number = $this->calculator->baseConvert($number, $base);
            if (0 <= $this->calculator->compare($number, 0) && 0 >= $this->calculator->compare($number, $this->maxIPv4Number)) {
                return $number;
            }
        }
        return null;
    }
    /**
     * Generates the dot-decimal notation for IPv4.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     *
     * @param mixed $ipAddress the number representation of the IPV4address
     */
    private function long2Ip($ipAddress) : string
    {
        $output = '';
        for ($offset = 0; $offset < 4; $offset++) {
            $output = $this->calculator->mod($ipAddress, 256) . $output;
            if ($offset < 3) {
                $output = '.' . $output;
            }
            $ipAddress = $this->calculator->div($ipAddress, 256);
        }
        return $output;
    }
}
