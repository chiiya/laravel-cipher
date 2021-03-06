<?php

namespace Chiiya\LaravelCipher;

use Chiiya\LaravelCipher\Commands\EncryptExistingDataCommand;
use Chiiya\LaravelCipher\Commands\InstallCommand;
use Chiiya\LaravelCipher\Services\Encrypter;
use ParagonIE\CipherSweet\Backend\BoringCrypto;
use ParagonIE\CipherSweet\Backend\FIPSCrypto;
use ParagonIE\CipherSweet\Backend\ModernCrypto;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\Contract\BackendInterface;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelCipherServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-cipher')
            ->hasConfigFile()
            ->hasMigration('create_blind_indexes_table')
            ->hasCommand(InstallCommand::class)
            ->hasCommand(EncryptExistingDataCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton(Encrypter::class, function () {
            $provider = new StringProvider(config('cipher.key'));
            $backend = $this->getBackend();

            return new Encrypter(new CipherSweet($provider, $backend));
        });
    }

    protected function getBackend(): BackendInterface
    {
        switch (config('cipher.backend')) {
            case 'nacl':
                return new ModernCrypto();
            case 'fips':
                return new FIPSCrypto();
            case 'brng':
            default:
                return new BoringCrypto();
        }
    }
}
