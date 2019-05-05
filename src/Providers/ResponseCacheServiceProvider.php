<?php

namespace Square1\ResponseCache\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\Repository;
use Square1\ResponseCache\Hasher;
use Illuminate\Routing\Route;

class ResponseCacheServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register()
    {
        $this->loadConfig();
        $this->registerCacheResponseBindings();
    }

    protected function registerCacheResponseBindings()
    {
        $this->app->when(ResponseCacheRepository::class)
            ->needs(Repository::class)
            ->give(function () {
                return resolve(Repository::class);
            });

        $this->app->when(ResponseCacheRepository::class)
            ->needs(Hasher::class)
            ->give(function () {
                return new Hasher();
            });
    }

    protected function loadConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/response-cache.php', 'response-cache'
        );
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot()
    {
        //
    }
}
