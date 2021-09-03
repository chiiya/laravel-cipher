<?php

namespace Chiiya\LaravelCipher\Services;

use Chiiya\LaravelCipher\Contracts\Encryptable;
use Chiiya\LaravelCipher\Models\BlindIndex;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\Constants;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\Exception\ArrayKeyException;
use ParagonIE\CipherSweet\Exception\BlindIndexNotFoundException;
use ParagonIE\CipherSweet\Exception\CipherSweetException;
use ParagonIE\CipherSweet\Exception\CryptoOperationException;
use ParagonIE\CipherSweet\Exception\InvalidCiphertextException;
use SodiumException;

class CipherSweetService
{
    protected CipherSweet $engine;

    public function __construct(CipherSweet $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Encrypt value.
     *
     * @param mixed $value
     * @throws CipherSweetException
     * @throws CryptoOperationException
     * @throws SodiumException
     */
    public function encrypt($value, string $table, string $key, ?string $aad = null): ?string
    {
        $key = $this->engine->getFieldSymmetricKey($table, $key);

        if ($aad !== null) {
            return $this->engine->getBackend()->encrypt(serialize($value), $key, $aad);
        }

        return $this->engine->getBackend()->encrypt(serialize($value), $key);
    }

    /**
     * Decrypt value.
     *
     * @return mixed
     * @throws CipherSweetException
     * @throws CryptoOperationException
     * @throws InvalidCiphertextException
     * @throws SodiumException
     */
    public function decrypt(string $value, string $table, string $key, ?string $aad = null)
    {
        $key = $this->engine->getFieldSymmetricKey($table, $key);

        if ($aad !== null) {
            return unserialize($this->engine->getBackend()->decrypt($value, $key, $aad));
        }

        return unserialize($this->engine->getBackend()->decrypt($value, $key));
    }

    /**
     * Calculate the value for a given blind index using the given attribute $values.
     *
     * @return string|string[]
     * @throws ArrayKeyException
     * @throws CryptoOperationException
     * @throws SodiumException
     * @throws BlindIndexNotFoundException
     */
    public function getBlindIndex(Encryptable $encryptable, string $index, array $values)
    {
        $row = $this->buildEncryptedRow($encryptable);

        return $row->getBlindIndex($index, $values);
    }

    /**
     * Update all blind and compound indexes for the given $encryptable model.
     *
     * @throws CryptoOperationException
     * @throws SodiumException
     * @throws ArrayKeyException
     */
    public function syncIndexes(Encryptable $encryptable)
    {
        $row = $this->buildEncryptedRow($encryptable);
        $indexes = $row->getAllBlindIndexes($encryptable->attributesToArray());
        $indexes = collect($indexes)->map(fn (string $value, string $name) => BlindIndex::query()->newModelInstance([
            'name' => $name,
            'value' => $value,
            'indexable_type' => get_class($encryptable),
            'indexable_id' => $encryptable->getKey(),
        ]))->all();
        $encryptable->blindIndexes()->delete();
        $encryptable->blindIndexes()->insert($indexes);
    }

    public function getEngine(): CipherSweet
    {
        return $this->engine;
    }

    protected function buildEncryptedRow(Encryptable $encryptable): EncryptedRow
    {
        $row = new EncryptedRow($this->engine, $encryptable->getTable());

        foreach ($encryptable->getBlindIndexes() as $column => $indexes) {
            foreach ($indexes as $index) {
                $row->addBlindIndex($column, $index);
            }
        }

        foreach ($encryptable->getCompoundIndexes() as $index) {
            $row->addCompoundIndex($index);
        }

        foreach ($encryptable->encryptedTypes() as $key => $value) {
            $row->addField($key, $value);
        }

        return $row;
    }

    protected function guessType($value): string
    {
        if (is_bool($value)) {
            return Constants::TYPE_BOOLEAN;
        }

        if (is_float($value)) {
            return Constants::TYPE_FLOAT;
        }

        if (is_int($value)) {
            return Constants::TYPE_INT;
        }

        return Constants::TYPE_TEXT;
    }
}
