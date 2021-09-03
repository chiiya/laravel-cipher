<?php

namespace Chiiya\LaravelCipher\Observers;

use Chiiya\LaravelCipher\Contracts\Encryptable;
use Chiiya\LaravelCipher\Jobs\SynchronizeIndexes;

class EncryptableObserver
{
    public function deleting(Encryptable $model): void
    {
        $model->indexes()->delete();
    }

    public function saved(Encryptable $model): void
    {
        SynchronizeIndexes::dispatch($model)->onQueue('cipher');
    }
}
