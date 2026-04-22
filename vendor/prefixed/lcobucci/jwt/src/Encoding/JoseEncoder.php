<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Encoding;

use JsonException;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Decoder;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\Encoder;
use Matomo\Dependencies\OAuth2\Lcobucci\JWT\SodiumBase64Polyfill;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
/**
 * A utilitarian class that encodes and decodes data according to JOSE specifications
 */
final class JoseEncoder implements Encoder, Decoder
{
    /**
     * @param mixed $data
     */
    public function jsonEncode($data) : string
    {
        try {
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw CannotEncodeContent::jsonIssues($exception);
        }
    }
    /**
     * @return mixed
     */
    public function jsonDecode(string $json)
    {
        try {
            return json_decode($json, \true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw CannotDecodeContent::jsonIssues($exception);
        }
    }
    public function base64UrlEncode(string $data) : string
    {
        return SodiumBase64Polyfill::bin2base64($data, SodiumBase64Polyfill::SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }
    public function base64UrlDecode(string $data) : string
    {
        return SodiumBase64Polyfill::base642bin($data, SodiumBase64Polyfill::SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }
}
