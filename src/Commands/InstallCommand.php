<?php

namespace Chiiya\LaravelCipher\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;
use ParagonIE\ConstantTime\Hex;

class InstallCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cipher:install
                    {--show : Display the key instead of modifying files}
                    {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the commands necessary to prepare laravel-cipher for use';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $key = $this->generateRandomKey();

        if ($this->option('show')) {
            $this->line('<comment>'.$key.'</comment>');

            return self::SUCCESS;
        }

        if (! $this->setKeyInEnvironmentFile($key)) {
            return self::FAILURE;
        }

        $this->laravel['config']['cipher.key'] = $key;

        $this->info('Cipher key set successfully.');

        return self::SUCCESS;
    }

    /**
     * Generate a random key for the application.
     */
    protected function generateRandomKey(): string
    {
        return Hex::encode(random_bytes(32));
    }

    /**
     * Set the cipher key in the environment file.
     */
    protected function setKeyInEnvironmentFile(string $key): bool
    {
        $currentKey = $this->laravel['config']['cipher.key'];

        if (strlen($currentKey) !== 0 && (! $this->confirmToProceed())) {
            return false;
        }

        $this->writeNewEnvironmentFileWith($key);

        return true;
    }

    /**
     * Write a new environment file with the given key.
     */
    protected function writeNewEnvironmentFileWith(string $key): void
    {
        $content = file_get_contents($this->laravel->environmentFilePath());

        if (! Str::contains($content, 'CIPHER_KEY')) {
            file_put_contents(
                $this->laravel->environmentFilePath(),
                'CIPHER_KEY='.$key,
                FILE_APPEND
            );

            return;
        }

        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $this->keyReplacementPattern(),
            'CIPHER_KEY='.$key,
            $content
        ));
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     */
    protected function keyReplacementPattern(): string
    {
        $escaped = preg_quote('='.$this->laravel['config']['cipher.key'], '/');

        return "/^CIPHER_KEY{$escaped}/m";
    }
}
