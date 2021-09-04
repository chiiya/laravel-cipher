<?php

namespace Chiiya\LaravelCipher\Fields;

use Chiiya\LaravelCipher\Makeable;

abstract class BaseField
{
    use Makeable;

    /**
     * The database column name of the field.
     */
    protected string $name;

    /**
     * Indicates if the field is nullable.
     */
    protected bool $nullable = false;

    /**
     * Encrypted database value.
     */
    protected string $encrypted;

    /**
     * Decrypted database value.
     *
     * @var mixed|null
     */
    protected $decrypted = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Indicate that the field should be nullable.
     *
     * @return $this
     */
    public function nullable(bool $nullable = true): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Get the database column name of the field.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Should null values not be encrypted?
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    abstract public function serialize($value): string;
    abstract public function unserialize($value);
}
