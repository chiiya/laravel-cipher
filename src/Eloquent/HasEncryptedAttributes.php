<?php

namespace Chiiya\LaravelCipher\Eloquent;

use Chiiya\LaravelCipher\EncryptedRow;
use Chiiya\LaravelCipher\Events\ModelsEncrypted;
use Chiiya\LaravelCipher\Fields\BaseField;
use Chiiya\LaravelCipher\Index;
use Chiiya\LaravelCipher\Jobs\SynchronizeIndexes;
use Chiiya\LaravelCipher\Models\BlindIndex;
use Chiiya\LaravelCipher\Services\Encrypter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use ParagonIE\CipherSweet\BlindIndex as CipherSweetBlindIndex;
use ParagonIE\CipherSweet\CompoundIndex;
use ParagonIE\CipherSweet\Exception\ArrayKeyException;
use ParagonIE\CipherSweet\Exception\CipherSweetException;
use ParagonIE\CipherSweet\Exception\CryptoOperationException;
use ParagonIE\CipherSweet\Exception\InvalidCiphertextException;
use SodiumException;

/**
 * @method static Builder|static whereBlind(string $index, $value)
 * @mixin Model
 */
trait HasEncryptedAttributes
{
    /** @var BaseField[]|null */
    protected ?array $encryptedFields = null;

    /**
     * HasEncryptedAttributes boot logic.
     */
    public static function bootHasEncryptedAttributes(): void
    {
        static::saving(static function ($model) {
            $model->encrypt($model->getDirty());
        });

        static::deleting(static function ($model) {
            $model->blindIndexes()->delete();
        });

        static::retrieved(static function ($model) {
            $model->decrypt($model->attributes);
        });

        static::saved(static function ($model) {
            $model->decrypt($model->attributes);
            SynchronizeIndexes::dispatch($model)->onQueue('cipher');
        });
    }

    /**
     * Polymorphic One-To-Many: One encryptable entity has many blind indexes.
     */
    public function blindIndexes(): MorphMany
    {
        return $this->morphMany(
            config('cipher.index_model', BlindIndex::class),
            'indexable'
        );
    }

    /**
     * Get the additional, authenticated data to associate with an encrypted record.
     */
    public function getAadValue(): ?string
    {
        return null;
    }

    /**
     * Encrypt model attributes.
     *
     * @return $this
     * @throws CipherSweetException
     * @throws CryptoOperationException
     * @throws SodiumException
     */
    public function encrypt(array $attributes = null): self
    {
        /** @var Encrypter $service */
        $service = resolve(Encrypter::class);
        $fields = $this->prepareEncryptedFields($attributes ?: $this->attributesToArray());

        foreach ($fields as $field => $value) {
            $this->{$field} = $service->encrypt(
                $value,
                $this->getTable(),
                $field,
                $this->getAadValue()
            );
        }

        return $this;
    }

    /**
     * Decrypt model attributes.
     *
     * @return $this
     * @throws CipherSweetException
     * @throws CryptoOperationException
     * @throws InvalidCiphertextException
     * @throws SodiumException
     */
    public function decrypt(array $attributes = null): self
    {
        /** @var Encrypter $encrypter */
        $encrypter = resolve(Encrypter::class);
        $attributes = $attributes ?: $this->attributesToArray();

        foreach ($this->getEncryptedFields() as $field) {
            $name = $field->getName();
            if (isset($attributes[$name]) && Str::startsWith($attributes[$name], $encrypter->getEngine()->getBackend()->getPrefix())) {
                $this->{$name} = $field->unserialize($encrypter->decrypt(
                    $attributes[$name],
                    $this->getTable(),
                    $name,
                    $this->getAadValue()
                ));
            }
        }

        return $this;
    }

    /**
     * Sync all blind indexes for the model.
     *
     * @throws CryptoOperationException
     * @throws SodiumException
     * @throws ArrayKeyException
     */
    public function syncIndexes(): void
    {
        $attributes = $this->attributesToArray();
        $fields = [];

        foreach ($this->getEncryptedFields() as $name => $field) {
            if (array_key_exists($name, $attributes)) {
                $fields[$name] = $field->serialize($attributes[$name]);
            }
        }

        $indexes = collect($this->toEncryptedRow()->getAllBlindIndexes($fields))
            ->map(fn (string $value, string $name) => [
                'name' => $name,
                'value' => $value,
                'indexable_type' => $this->getMorphClass(),
                'indexable_id' => $this->getKey(),
            ])
            ->all();
        $this->blindIndexes()->delete();
        $this->blindIndexes()->insert($indexes);
    }

    public function toEncryptedRow(): EncryptedRow
    {
        /** @var Encrypter $service */
        $service = resolve(Encrypter::class);

        $row = new EncryptedRow($service->getEngine(), $this->getTable());

        foreach ($this->indexes() as $index) {
            if ($index->isCompoundIndex()) {
                $compoundIndex = new CompoundIndex(
                    $index->name,
                    $index->field,
                    $index->bits,
                    $index->fastHash,
                    $index->hashConfig
                );

                foreach ($index->transformations as $transformation) {
                    $compoundIndex->addRowTransform($transformation);
                }

                $row->addCompoundIndex($compoundIndex);
            } else {
                $row->addBlindIndex($index->field, new CipherSweetBlindIndex(
                    $index->name,
                    $index->transformations,
                    $index->bits,
                    $index->fastHash,
                    $index->hashConfig
                ));
            }
        }

        $row->setFields($this->getEncryptedFields());

        return $row;
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
                $models->each->encryptExisting();

                $total += $models->count();

                event(new ModelsEncrypted(static::class, $total));
            });

        return $total;
    }

    /**
     * Encrypt existing, not yet encrypted values on the current model.
     *
     * @throws CipherSweetException
     * @throws CryptoOperationException
     * @throws SodiumException
     */
    public function encryptExisting(): void
    {
        $service = resolve(Encrypter::class);

        // Filter out already encrypted attributes
        $attributes = collect($this->attributesToArray())->filter(
            fn ($value) => ! (is_string($value) && Str::startsWith($value, $service->getEngine()->getBackend()->getPrefix()))
        );

        $this->encrypt($attributes->all());

        if ($this->isDirty()) {
            $this->saveQuietly();
            SynchronizeIndexes::dispatch($this)->onQueue('cipher');
        }
    }

    /**
     * Add a `where` scope for a specific blind index. For compounds indexes, pass an array of values.
     *
     * @param string|array<string,mixed> $value
     */
    public function scopeWhereBlind(Builder $query, string $index, $value): Builder
    {
        return $query->whereHas('blindIndexes', function (Builder $builder) use ($index, $value) {
            $column = collect($this->indexes())->first(fn (Index $idx) => $idx->name === $index)->field;
            $attributes = is_string($column) ? [$column => $value] : $value;
            $row = $this->toEncryptedRow();

            return $builder
                ->where('name', '=', $index)
                ->where('value', '=', $row->getBlindIndex($index, $attributes));
        });
    }

    protected function prepareEncryptedFields(array $attributes = []): array
    {
        $fields = [];

        foreach ($this->getEncryptedFields() as $name => $field) {
            if (array_key_exists($name, $attributes) && ($attributes[$name] !== null || ! $field->isNullable())) {
                $fields[$name] = $field->serialize($attributes[$name]);
            }
        }

        return $fields;
    }

    /**
     * Define the encrypted fields for the model.
     *
     * @return BaseField[]
     */
    abstract public function encryptedFields(): array;

    /**
     * Define the blind and compound indexes for the model.
     *
     * @return Index[]
     */
    abstract public function indexes(): array;

    /**
     * @return BaseField[]
     */
    public function getEncryptedFields(): array
    {
        if ($this->encryptedFields === null) {
            foreach ($this->encryptedFields() as $field) {
                $this->encryptedFields[$field->getName()] = $field;
            }
        }

        return $this->encryptedFields;
    }
}
