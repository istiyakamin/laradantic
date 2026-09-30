<?php

declare(strict_types=1);

namespace LaraDantic\Laravel;

use Illuminate\Support\ServiceProvider;
use LaraDantic\AI\AIManager;
use LaraDantic\Providers\ProviderManager;

class LaraDanticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/laradantic.php', 'laradantic');

        $this->app->singleton('laradantic', fn () => new LaraDanticManager);

        $this->app->singleton(ProviderManager::class, fn ($app) => new ProviderManager($app->make('config')));

        $this->app->singleton('laradantic.ai', fn ($app) => new AIManager($app->make(ProviderManager::class)));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/laradantic.php' => config_path('laradantic.php'),
            ], 'laradantic-config');
        }
    }
}
