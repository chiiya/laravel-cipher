<?php

namespace Chiiya\LaravelCipher;

use ParagonIE\CipherSweet\Contract\RowTransformationInterface;
use ParagonIE\CipherSweet\Contract\TransformationInterface;

class Index
{
    use Makeable;

    /**
     * The database fields used for this index.
     *
     * @var string|string[]
     */
    public $field;

    /**
     * Unique name for the index.
     */
    public string $name;

    /**
     * Should the backend use fast hashing? For sensitive data, use slow hashing.
     */
    public bool $fastHash = false;

    /**
     * Custom hashing config for slow hashing (e.g. Argon2).
     */
    public array $hashConfig = [];

    /**
     * Number of bits used for the bloom filter.
     */
    public int $bits = 256;

    /**
     * Transformations to apply to the attribute value(s) before creating the index.
     *
     * @var TransformationInterface[]|RowTransformationInterface[]
     */
    public array $transformations;

    public function __construct($field, string $name)
    {
        $this->field = is_array($field) && count($field) === 1 ? $field[0] : $field;
        $this->name = $name;
    }

    /**
     * Indicate that the field should use fast hashing.
     *
     * @return $this
     */
    public function fastHash(bool $fastHash = true): self
    {
        $this->fastHash = $fastHash;

        return $this;
    }

    /**
     * Set the custom (slow) hashing config.
     *
     * @return $this
     */
    public function hashConfig(array $hashConfig): self
    {
        $this->hashConfig = $hashConfig;

        return $this;
    }

    /**
     * Set the amount of bits used for bloom filters.
     *
     * @return $this
     */
    public function bits(int $bits): self
    {
        $this->bits = $bits;

        return $this;
    }

    /**
     * Set the transformations used for creating the index.
     *
     * @param TransformationInterface[]|RowTransformationInterface[] $transformations
     * @return $this
     */
    public function transformations(array $transformations): self
    {
        $this->transformations = $transformations;

        return $this;
    }

    /**
     * Is the index a compound index?
     */
    public function isCompoundIndex(): bool
    {
        return is_array($this->field);
    }
}
