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
namespace Matomo\Dependencies\OAuth2\League\Uri\IPv6;

use Stringable;
use ValueError;
use function filter_var;
use function implode;
use function inet_pton;
use function str_split;
use function strtolower;
use function unpack;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
final class Converter
{
    /**
     * Significant 10 bits of IP to detect Zone ID regular expression pattern.
     *
     * @var string
     */
    private const HOST_ADDRESS_BLOCK = "\xfe\x80";
    public static function compressIp(string $ipAddress) : string
    {
        switch (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            case \false:
                throw new ValueError('The submitted IP is not a valid IPv6 address.');
            default:
                return strtolower((string) inet_ntop((string) inet_pton($ipAddress)));
        }
    }
    public static function expandIp(string $ipAddress) : string
    {
        if (\false === filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new ValueError('The submitted IP is not a valid IPv6 address.');
        }
        $hex = (array) unpack('H*hex', (string) inet_pton($ipAddress));
        return implode(':', str_split(strtolower($hex['hex'] ?? ''), 4));
    }
    /**
     * @param \Stringable|string|null $host
     */
    public static function compress($host) : ?string
    {
        $components = self::parse($host);
        if (null === $components['ipAddress']) {
            switch ($host) {
                case null:
                    return $host;
                default:
                    return (string) $host;
            }
        }
        $components['ipAddress'] = self::compressIp($components['ipAddress']);
        return self::build($components);
    }
    /**
     * @param \Stringable|string|null $host
     */
    public static function expand($host) : ?string
    {
        $components = self::parse($host);
        if (null === $components['ipAddress']) {
            switch ($host) {
                case null:
                    return $host;
                default:
                    return (string) $host;
            }
        }
        $components['ipAddress'] = self::expandIp($components['ipAddress']);
        return self::build($components);
    }
    public static function build(array $components) : string
    {
        $components['ipAddress'] = $components['ipAddress'] ?? null;
        $components['zoneIdentifier'] = $components['zoneIdentifier'] ?? null;
        if (null === $components['ipAddress']) {
            return '';
        }
        switch ($components['zoneIdentifier']) {
            case null:
                return '';
            default:
                return '%' . $components['zoneIdentifier'];
        }
    }
    /**
     * @return array{ipAddress:string|null, zoneIdentifier:string|null}
     * @param \Stringable|string|null $host
     */
    private static function parse($host) : array
    {
        if (null === $host) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }
        $host = (string) $host;
        if ('' === $host) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }
        if (strncmp($host, '[', strlen('[')) !== 0) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }
        if (substr_compare($host, ']', -strlen(']')) !== 0) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }
        [$ipv6, $zoneIdentifier] = explode('%', substr($host, 1, -1), 2) + [1 => null];
        if (\false === filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }
        switch (\true) {
            case null === $zoneIdentifier:
            case is_string($ipv6) && strncmp((string) inet_pton($ipv6), self::HOST_ADDRESS_BLOCK, strlen(self::HOST_ADDRESS_BLOCK)) === 0:
                return ['ipAddress' => $ipv6, 'zoneIdentifier' => $zoneIdentifier];
            default:
                return ['ipAddress' => null, 'zoneIdentifier' => null];
        }
    }
    /**
     * Tells whether the host is an IPv6.
     * @param \Stringable|string|null $host
     */
    public static function isIpv6($host) : bool
    {
        return null !== self::parse($host)['ipAddress'];
    }
    /**
     * @param \Stringable|string|null $host
     */
    public static function normalize($host) : ?string
    {
        if (null === $host || '' === $host) {
            return $host;
        }
        $host = (string) $host;
        $components = self::parse($host);
        if (null === $components['ipAddress']) {
            return strtolower($host);
        }
        $components['ipAddress'] = strtolower($components['ipAddress']);
        return self::build($components);
    }
}
