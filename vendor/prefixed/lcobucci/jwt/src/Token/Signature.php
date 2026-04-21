<?php

declare (strict_types=1);
namespace Matomo\Dependencies\Oauth2\Lcobucci\JWT\Token;

final class Signature
{
    /**
     * @var non-empty-string
     * @readonly
     */
    private $hash;
    /**
     * @var non-empty-string
     * @readonly
     */
    private $encoded;
    /**
     * @param non-empty-string $hash
     * @param non-empty-string $encoded
     */
    public function __construct(string $hash, string $encoded)
    {
        $this->hash = $hash;
        $this->encoded = $encoded;
    }
    /** @return non-empty-string */
    public function hash() : string
    {
        return $this->hash;
    }
    /**
     * Returns the encoded version of the signature
     *
     * @return non-empty-string
     */
    public function toString() : string
    {
        return $this->encoded;
    }
}
