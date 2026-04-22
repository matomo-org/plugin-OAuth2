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
namespace Matomo\Dependencies\OAuth2\League\Uri;

use ValueError;
class UriScheme
{
    public const About = 'about';
    public const Acap = 'acap';
    public const Bitcoin = 'bitcoin';
    public const Geo = 'geo';
    public const Blob = 'blob';
    public const Afp = 'afp';
    public const Data = 'data';
    public const Dict = 'dict';
    public const Dns = 'dns';
    public const File = 'file';
    public const Ftp = 'ftp';
    public const Git = 'git';
    public const Gopher = 'gopher';
    public const Http = 'http';
    public const Https = 'https';
    public const Imap = 'imap';
    public const Imaps = 'imaps';
    public const Ipp = 'ipp';
    public const Ipps = 'ipps';
    public const Irc = 'irc';
    public const Ircs = 'ircs';
    public const Javascript = 'javascript';
    public const Ldap = 'ldap';
    public const Ldaps = 'ldaps';
    public const Magnet = 'magnet';
    public const Mailto = 'mailto';
    public const Mms = 'mms';
    public const Msrp = 'msrp';
    public const Msrps = 'msrps';
    public const Mtqp = 'mtqp';
    public const News = 'news';
    public const Nfs = 'nfs';
    public const Nntp = 'nntp';
    public const Nntps = 'nntps';
    public const Pkcs11 = 'pkcs11';
    public const Pop = 'pop';
    public const Prospero = 'prospero';
    public const Redis = 'redis';
    public const Rsync = 'rsync';
    public const Rtsp = 'rtsp';
    public const Rtsps = 'rtsps';
    public const Rtspu = 'rtspu';
    public const Sftp = 'sftp';
    public const Wss = 'wss';
    public const Ws = 'ws';
    public const Sip = 'sip';
    public const Sips = 'sips';
    public const Smb = 'smb';
    public const Smtp = 'smtp';
    public const Snmp = 'snmp';
    public const Ssh = 'ssh';
    public const Steam = 'steam';
    public const Svn = 'svn';
    public const Tel = 'tel';
    public const Telnet = 'telnet';
    public const Tn3270 = 'tn3270';
    public const Urn = 'urn';
    public const Ventrilo = 'ventrilo';
    public const Vnc = 'vnc';
    public const Wais = 'wais';
    public const Xmpp = 'xmpp';
    public function port() : ?int
    {
        switch ($this) {
            case self::Acap:
                return 674;
            case self::Afp:
                return 548;
            case self::Dict:
                return 2628;
            case self::Dns:
                return 53;
            case self::Ftp:
                return 21;
            case self::Http:
            case self::Ws:
                return 80;
            case self::Https:
            case self::Wss:
                return 443;
            case self::Git:
                return 9418;
            case self::Gopher:
                return 70;
            case self::Imap:
                return 143;
            case self::Imaps:
                return 993;
            case self::Ipp:
            case self::Ipps:
                return 631;
            case self::Irc:
                return 194;
            case self::Ircs:
                return 6697;
            case self::Ldap:
                return 389;
            case self::Ldaps:
                return 636;
            case self::Mms:
                return 1755;
            case self::Msrp:
            case self::Msrps:
                return 2855;
            case self::Mtqp:
                return 1038;
            case self::Nfs:
                return 111;
            case self::Nntp:
                return 119;
            case self::Nntps:
                return 563;
            case self::Pop:
                return 110;
            case self::Prospero:
                return 1525;
            case self::Redis:
                return 6379;
            case self::Rsync:
                return 873;
            case self::Rtsp:
                return 554;
            case self::Rtsps:
                return 322;
            case self::Rtspu:
                return 5005;
            case self::Sftp:
            case self::Ssh:
                return 22;
            case self::Smb:
                return 445;
            case self::Smtp:
                return 25;
            case self::Snmp:
                return 161;
            case self::Svn:
                return 3690;
            case self::Telnet:
            case self::Tn3270:
                return 23;
            case self::Ventrilo:
                return 3784;
            case self::Vnc:
                return 5900;
            case self::Wais:
                return 210;
            case self::Xmpp:
                return 80;
            default:
                return null;
        }
    }
    public function type() : SchemeType
    {
        switch ($this) {
            case self::Urn:
            case self::About:
            case self::Bitcoin:
            case self::Blob:
            case self::Data:
            case self::Geo:
            case self::Javascript:
            case self::Magnet:
            case self::Mailto:
            case self::Pkcs11:
            case self::Sip:
            case self::Sips:
            case self::Tel:
                return SchemeType::Opaque;
            case self::File:
                return SchemeType::Hierarchical;
            case self::News:
                return SchemeType::Unknown;
            default:
                switch (\true) {
                    case null !== $this->port():
                        return SchemeType::Hierarchical;
                    default:
                        return SchemeType::Unknown;
                }
        }
    }
    public function isWhatWgSpecial() : bool
    {
        switch ($this) {
            case self::Ftp:
            case self::Http:
            case self::Https:
            case self::Ws:
            case self::Wss:
                return \true;
            default:
                return \false;
        }
    }
    /**
     * @return list<self>
     */
    public static function fromPort(?int $port) : array
    {
        null === $port || 0 <= $port || throw new ValueError('The submitted port cannot be negative.');
        static $reverse = [];
        if ([] === $reverse) {
            foreach (self::cases() as $case) {
                $defaultPort = $case->port();
                if (null === $defaultPort) {
                    continue;
                }
                $reverse[$defaultPort] = $reverse[$defaultPort] ?? [];
                $reverse[$defaultPort][] = $case;
            }
        }
        return $reverse[$port] ?? [];
    }
}
