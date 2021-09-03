<?php

namespace Chiiya\LaravelCipher\Jobs;

use Chiiya\LaravelCipher\Contracts\Encryptable;
use Chiiya\LaravelCipher\Services\CipherSweetService;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(CipherSweetService $service)
    {
        if ($this->model instanceof Encryptable) {
            $service->syncIndexes($this->model);
        }
    }
}
