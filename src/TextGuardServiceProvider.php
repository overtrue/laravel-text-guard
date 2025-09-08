<?php

namespace Overtrue\TextGuard;

use Illuminate\Support\ServiceProvider;

class TextGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/text-guard.php', 'text-guard');

        $this->app->singleton(TextGuardManager::class, function ($app) {
            return new TextGuardManager(config('text-guard'));
        });

        // Register TextGuard Facade
        $this->app->alias(TextGuardManager::class, TextGuard::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/text-guard.php' => config_path('text-guard.php'),
        ], 'text-guard-config');
    }
}
