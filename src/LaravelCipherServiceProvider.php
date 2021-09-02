<?php

namespace Chiiya\LaravelCipher;

use Chiiya\LaravelCipher\Commands\EncryptExistingDataCommand;
use Chiiya\LaravelCipher\Commands\InstallCommand;
use Chiiya\LaravelCipher\Services\CipherSweetService;
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
//            ->hasMigration('create_laravelcipher_table')
            ->hasCommand(InstallCommand::class)
            ->hasCommand(EncryptExistingDataCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton(CipherSweetService::class, function () {
            $provider = new StringProvider(config('cipher.key'));
            $backend = $this->getBackend();

            return new CipherSweetService(new CipherSweet($provider, $backend));
        });
    }

    protected function getBackend(): BackendInterface
    {
        switch (config('cipher.backend')) {
            case 'modern':
                return new ModernCrypto();
            case 'fips':
                return new FIPSCrypto();
            case 'boring':
            default:
                return new BoringCrypto();
        }
    }
}
