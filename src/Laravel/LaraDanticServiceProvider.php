<?php

declare(strict_types=1);

namespace LaraDantic\Laravel;

use Illuminate\Support\ServiceProvider;

class LaraDanticServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/laradantic.php', 'laradantic');

        $this->app->singleton('laradantic', fn () => new LaraDanticManager);
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
