<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT;

use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\CannotSignPayload;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\Ecdsa\ConversionFailed;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\InvalidKeyProvided;
use Matomo\Dependencies\Oauth2\Lcobucci\JWT\Signer\Key;
interface Signer
{
    /**
     * Returns the algorithm id
     *
     * @return non-empty-string
     */
    public function algorithmId() : string;
    /**
     * Creates a hash for the given payload
     *
     * @param non-empty-string $payload
     *
     * @return non-empty-string
     *
     * @throws CannotSignPayload  When payload signing fails.
     * @throws InvalidKeyProvided When issue key is invalid/incompatible.
     * @throws ConversionFailed   When signature could not be converted.
     */
    public function sign(string $payload, Key $key) : string;
    /**
     * Returns if the expected hash matches with the data and key
     *
     * @param non-empty-string $expected
     * @param non-empty-string $payload
     *
     * @throws InvalidKeyProvided When issue key is invalid/incompatible.
     * @throws ConversionFailed   When signature could not be converted.
     */
    public function verify(string $expected, string $payload, Key $key) : bool;
}
