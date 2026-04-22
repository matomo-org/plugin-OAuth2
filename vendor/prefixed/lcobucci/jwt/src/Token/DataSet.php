<?php

declare (strict_types=1);
namespace Matomo\Dependencies\OAuth2\Lcobucci\JWT\Token;

use function array_key_exists;
final class DataSet
{
    /**
     * @var array<non-empty-string, mixed>
     * @readonly
     */
    private $data;
    /**
     * @readonly
     * @var string
     */
    private $encoded;
    /** @param array<non-empty-string, mixed> $data */
    public function __construct(array $data, string $encoded)
    {
        $this->data = $data;
        $this->encoded = $encoded;
    }
    /** @param non-empty-string $name
     * @param mixed $default
     * @return mixed */
    public function get(string $name, $default = null)
    {
        return $this->data[$name] ?? $default;
    }
    /** @param non-empty-string $name */
    public function has(string $name) : bool
    {
        return array_key_exists($name, $this->data);
    }
    /** @return array<non-empty-string, mixed> */
    public function all() : array
    {
        return $this->data;
    }
    public function toString() : string
    {
        return $this->encoded;
    }
}
