<?php

namespace Chiiya\LaravelCipher\Jobs;

use Chiiya\LaravelCipher\Eloquent\HasEncryptedAttributes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ParagonIE\CipherSweet\Exception\ArrayKeyException;
use ParagonIE\CipherSweet\Exception\CryptoOperationException;
use SodiumException;

class SynchronizeIndexes implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var Model|HasEncryptedAttributes */
    private Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @throws ArrayKeyException
     * @throws CryptoOperationException
     * @throws SodiumException
     */
    public function handle(): void
    {
        if ($this->isEncryptable($this->model)) {
            $this->model->syncIndexes();
        }
    }

    /**
     * Determine if the given model class is encryptable.
     */
    protected function isEncryptable(Model $model): bool
    {
        return in_array(HasEncryptedAttributes::class, class_uses_recursive($model));
    }
}
