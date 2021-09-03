<?php

namespace Chiiya\LaravelCipher\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Chiiya\LaravelCipher\Models\BlindIndex.
 *
 * @property int $id
 * @property string $name
 * @property string $value
 * @property string|null $indexable_type
 * @property int|null $indexable_id
 * @method static \Illuminate\Database\Eloquent\Builder|BlindIndex newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BlindIndex newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BlindIndex query()
 */
class BlindIndex extends Model
{
    /**
     * Database table name.
     *
     * @var string
     */
    protected $table = 'blind_indexes';

    /**
     * Attributes that are mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'indexable_type',
        'indexable_id',
        'name',
        'value',
    ];

    /**
     * Polymorphic One-To-Many: One blind index belongs to one indexable entity.
     */
    public function indexable(): MorphTo
    {
        return $this->morphTo();
    }
}
