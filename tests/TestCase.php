<?php

namespace Chiiya\LaravelCipher\Tests;

use Chiiya\LaravelCipher\LaravelCipherServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelCipherServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        $migration = include __DIR__.'/../database/migrations/create_laravelcipher_table.php.stub';
        $migration->up();
        */
    }
}
