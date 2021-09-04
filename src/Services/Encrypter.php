<?php

namespace Chiiya\LaravelCipher\Services;

use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\Exception\CipherSweetException;
use ParagonIE\CipherSweet\Exception\CryptoOperationException;
use ParagonIE\CipherSweet\Exception\InvalidCiphertextException;
use SodiumException;

class Encrypter
{
    protected CipherSweet $engine;

    public function __construct(CipherSweet $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Encrypt value.
     *
     * @throws CipherSweetException
     * @throws CryptoOperationException
     * @throws SodiumException
     */
    public function encrypt(string $value, string $table, string $key, ?string $aad = null): ?string
    {
        $key = $this->engine->getFieldSymmetricKey($table, $key);

        if ($aad !== null) {
            return $this->engine->getBackend()->encrypt($value, $key, $aad);
        }

        return $this->engine->getBackend()->encrypt($value, $key);
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

    public function getEngine(): CipherSweet
    {
        return $this->engine;
    }
}
