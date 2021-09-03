<?php

namespace Chiiya\LaravelCipher\Eloquent;

use Chiiya\LaravelCipher\Events\ModelsEncrypted;
use Chiiya\LaravelCipher\Jobs\SynchronizeIndexes;
use Chiiya\LaravelCipher\Models\BlindIndex;
use Chiiya\LaravelCipher\Observers\EncryptableObserver;
use Chiiya\LaravelCipher\Services\CipherSweetService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ParagonIE\CipherSweet\BlindIndex as CipherSweetBlindIndex;
use ParagonIE\CipherSweet\CompoundIndex;
use ParagonIE\CipherSweet\Contract\TransformationInterface;

/**
 * @method static Builder|static whereBlind(string $index, $value)
 */
trait HasEncryptedAttributes
{
    /**
     * @var array<string, array<string|array, string|array, ?bool, ?int>>
     */
    protected static array $indexes = [];

    /**
     * @var array<string, array<string, CipherSweetBlindIndex>>
     */
    protected static array $blindIndexes = [];

    /**
     * @var array<string, CompoundIndex>
     */
    protected static array $compoundIndexes = [];

    /**
     * @var array<string,string|array<string>>
     */
    protected static array $indexColumns = [];

    /**
     * HasEncryptedAttributes boot logic.
     */
    public static function bootHasEncryptedAttributes(): void
    {
        static::observe(new EncryptableObserver());
        static::configureIndexes();
    }

    /**
     * Polymorphic One-To-Many: One encryptable entity has many blind indexes.
     */
    public function indexes(): MorphMany
    {
        return $this->morphMany(
            config('cipher.index_model', BlindIndex::class),
            'indexable'
        );
    }

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

                event(new ModelsEncrypted(static::class, $total));
            });

        return $total;
    }

    /**
     * Encrypt existing, not yet encrypted values on the current model.
     */
    public function encrypt(): void
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
            SynchronizeIndexes::dispatch($this);
            $encrypted++;
        }

        if ($encrypted > 0) {
            $this->save();
        }
    }

    public function getBlindIndexes(): array
    {
        return static::$blindIndexes;
    }

    public function getCompoundIndexes(): array
    {
        return static::$compoundIndexes;
    }

    /**
     * Add a `where` scope for a specific blind index. For compounds indexes, pass an array of values.
     *
     * @param string|array<string,mixed> $value
     */
    public function scopeWhereBlind(Builder $query, string $index, $value): Builder
    {
        return $query->whereHas('indexes', function (Builder $builder) use ($index, $value) {
            /** @var CipherSweetService $service */
            $service = resolve(CipherSweetService::class);
            $column = static::$indexColumns[$index];
            $attributes = is_string($column) ? [$column => $value] : $value;

            return $builder
                ->where('name', '=', $index)
                ->where('value', '=', $service->getBlindIndex($this, $index, $attributes));
        });
    }

    /**
     * Configures blind indexes.
     */
    protected static function configureIndexes(): void
    {
        foreach (static::$indexes as $name => $configuration) {
            $configuration = Arr::wrap($configuration);
            $column = $configuration[0];
            $transformations = isset($configuration[1]) ? static::convertTransformations(Arr::wrap($configuration[1])) : [];
            $isSlow = $configuration[2] ?? false;
            $bits = $configuration[3] ?? 256;

            if (is_array($column)) {
                $compoundIndex = new CompoundIndex($name, $column, $bits, ! $isSlow);
                foreach ($transformations as $transformation) {
                    $compoundIndex->addRowTransform($transformation);
                }
                static::$compoundIndexes[$name] = $compoundIndex;
            } else {
                static::$blindIndexes[$column][$name] = new CipherSweetBlindIndex($name, $transformations, $bits, ! $isSlow);
            }

            static::$indexColumns[$name] = $column;
        }
    }

    /**
     * @return array<TransformationInterface>
     */
    protected static function convertTransformations(array $transformations): array
    {
        return array_map(function ($transformation) {
            return app($transformation);
        }, $transformations);
    }
}
