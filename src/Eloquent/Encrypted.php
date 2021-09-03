<?php

namespace Chiiya\LaravelCipher\Eloquent;

use Chiiya\LaravelCipher\Contracts\Encryptable;
use Chiiya\LaravelCipher\Services\CipherSweetService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Str;

class Encrypted implements CastsAttributes
{
    /**
     * @param Encryptable $model
     * @return mixed|null
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        $encrypter = resolve(CipherSweetService::class);

        // Compatibility with existing, not yet encrypted data
        if (! Str::startsWith($value, $encrypter->getEngine()->getBackend()->getPrefix())) {
            return $value;
        }

        return $encrypter->decrypt($value, $model->getTable(), $key, $model->getAadValue());
    }

    /**
     * @param Encryptable $model
     * @param mixed $value
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return resolve(CipherSweetService::class)->encrypt($value, $model->getTable(), $key, $model->getAadValue());
    }
}
