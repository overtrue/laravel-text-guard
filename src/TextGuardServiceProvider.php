<?php

namespace Overtrue\TextGuard;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Overtrue\TextGuard\Http\Middleware\MergeFilteredInput;
use Overtrue\TextGuard\Support\MergeFilteredFields;

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

        if (! Request::hasMacro('mergeFiltered')) {
            Request::macro('mergeFiltered', function (array $fieldPresets) {
                /** @var Request $this */
                $payload = MergeFilteredFields::buildMergePayload($this->all(), $fieldPresets);
                if ($payload !== []) {
                    $this->merge($payload);
                }

                return $this;
            });
        }

        $this->app->booted(function () {
            if ($this->app->bound(Router::class)) {
                /** @var Router $router */
                $router = $this->app->make(Router::class);
                $router->aliasMiddleware('text-guard.merge', MergeFilteredInput::class);
            }
        });
    }
}
