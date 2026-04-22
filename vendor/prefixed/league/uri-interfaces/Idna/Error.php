<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\OAuth2\League\Uri\Idna;

class Error
{
    public const NONE = 0;
    public const EMPTY_LABEL = 1;
    public const LABEL_TOO_LONG = 2;
    public const DOMAIN_NAME_TOO_LONG = 4;
    public const LEADING_HYPHEN = 8;
    public const TRAILING_HYPHEN = 0x10;
    public const HYPHEN_3_4 = 0x20;
    public const LEADING_COMBINING_MARK = 0x40;
    public const DISALLOWED = 0x80;
    public const PUNYCODE = 0x100;
    public const LABEL_HAS_DOT = 0x200;
    public const INVALID_ACE_LABEL = 0x400;
    public const BIDI = 0x800;
    public const CONTEXTJ = 0x1000;
    public const CONTEXTO_PUNCTUATION = 0x2000;
    public const CONTEXTO_DIGITS = 0x4000;
    public function description() : string
    {
        switch ($this) {
            case self::NONE:
                return 'No error has occurred';
            case self::EMPTY_LABEL:
                return 'a non-final domain name label (or the whole domain name) is empty';
            case self::LABEL_TOO_LONG:
                return 'a domain name label is longer than 63 bytes';
            case self::DOMAIN_NAME_TOO_LONG:
                return 'a domain name is longer than 255 bytes in its storage form';
            case self::LEADING_HYPHEN:
                return 'a label starts with a hyphen-minus ("-")';
            case self::TRAILING_HYPHEN:
                return 'a label ends with a hyphen-minus ("-")';
            case self::HYPHEN_3_4:
                return 'a label contains hyphen-minus ("-") in the third and fourth positions';
            case self::LEADING_COMBINING_MARK:
                return 'a label starts with a combining mark';
            case self::DISALLOWED:
                return 'a label or domain name contains disallowed characters';
            case self::PUNYCODE:
                return 'a label starts with "xn--" but does not contain valid Punycode';
            case self::LABEL_HAS_DOT:
                return 'a label contains a dot=full stop';
            case self::INVALID_ACE_LABEL:
                return 'An ACE label does not contain a valid label string';
            case self::BIDI:
                return 'a label does not meet the IDNA BiDi requirements (for right-to-left characters)';
            case self::CONTEXTJ:
                return 'a label does not meet the IDNA CONTEXTJ requirements';
            case self::CONTEXTO_DIGITS:
                return 'a label does not meet the IDNA CONTEXTO requirements for digits';
            case self::CONTEXTO_PUNCTUATION:
                return 'a label does not meet the IDNA CONTEXTO requirements for punctuation characters. Some punctuation characters "Would otherwise have been DISALLOWED" but are allowed in certain contexts';
        }
    }
    public static function filterByErrorBytes(int $errors) : array
    {
        return array_values(array_filter(self::cases(), function (self $error) use ($errors) : bool {
            return 0 !== ($error->value & $errors);
        }));
    }
}
