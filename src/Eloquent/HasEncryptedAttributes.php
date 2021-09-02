<?php

namespace Chiiya\LaravelCipher\Eloquent;

use Chiiya\LaravelCipher\Services\CipherSweetService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @mixin Model
 */
trait HasEncryptedAttributes
{
    public function getAadValue(): ?string
    {
        return null;
    }

    /**
     * Encrypt all existing, not yet encrypted models in the database.
     */
    public function encryptAll(int $chunkSize = 500): int
    {
        $total = 0;

        static::query()
            ->when(in_array(SoftDeletes::class, class_uses_recursive(get_class($this))), function ($query) {
                $query->withTrashed();
            })->chunkById($chunkSize, function ($models) use (&$total) {
                $models->each->encrypt();

                $total += $models->count();
            });

        return $total;
    }

    /**
     * Encrypt existing, not yet encrypted values on the current model.
     */
    public function encrypt()
    {
        $casts = $this->getCasts();
        $attributes = $this->getAttributes();
        $encrypted = 0;
        $encrypter = resolve(CipherSweetService::class);

        foreach ($casts as $key => $value) {
            if (
                ! array_key_exists($key, $this->getAttributes())
                || $attributes[$key] === null
                || ! $this->isClassCastable($key)
                || ! $this->resolveCasterClass($key) instanceof Encrypted
                || Str::startsWith($attributes[$key], $encrypter->getEngine()->getBackend()->getPrefix())
            ) {
                continue;
            }

            $value = $attributes[$key];
            $this->attributes[$key] = $encrypter->encrypt($value, $this->getTable(), $key, $this->getAadValue());
            $encrypted++;
        }

        if ($encrypted > 0) {
            $this->save();
        }
    }
}
