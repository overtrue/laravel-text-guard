<?php

namespace Tests;

use Overtrue\TextGuard\TextGuardServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Load package service provider.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [TextGuardServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Load text-guard configuration for testing
        $config = require __DIR__.'/../config/text-guard.php';
        $app['config']->set('text-guard', $config);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Load text-guard configuration for testing
        $config = require __DIR__.'/../config/text-guard.php';
        $this->app['config']->set('text-guard', $config);

        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadMigrationsFrom(dirname(__DIR__).'/migrations');
    }
}
