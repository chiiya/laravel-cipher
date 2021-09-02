<?php

namespace Chiiya\LaravelCipher\Commands;

use Chiiya\LaravelCipher\Eloquent\HasEncryptedAttributes;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class EncryptExistingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cipher:encrypt
                    {--model=* : Class names of the models to be encrypted}
                    {--chunk=500 : The number of models to retrieve per chunk of models to be encrypted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt existing data in database';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $models = $this->models();

        if ($models->isEmpty()) {
            $this->info('No encryptable models found.');

            return self::FAILURE;
        }

        $models->each(function (string $model) {
            $instance = new $model;
            $chunkSize = $this->option('chunk');

            $total = $this->isEncryptable($model)
                ? $instance->encryptAll($chunkSize)
                : 0;

            if ($total === 0) {
                $this->info("No encryptable [$model] records found.");
            }
        });

        return self::SUCCESS;
    }

    /**
     * Determine the models that should be encrypted.
     */
    protected function models(): Collection
    {
        if (! empty($models = $this->option('model'))) {
            return collect($models);
        }

        return collect((new Finder)->in(app_path('Models'))->files())
            ->map(fn (SplFileInfo $model) => $this->laravel->getNamespace().str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($model->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR)
                ))
            ->filter(fn (string $model) => $this->isEncryptable($model))
            ->values();
    }

    /**
     * Determine if the given model class is encryptable.
     */
    protected function isEncryptable(string $model): bool
    {
        return in_array(HasEncryptedAttributes::class, class_uses_recursive($model));
    }
}
