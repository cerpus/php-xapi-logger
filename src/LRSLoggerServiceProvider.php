<?php

namespace Cerpus\xAPI;

use Illuminate\Support\ServiceProvider;

class LRSLoggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->bind(Logger::class, function () {
            return new Logger(config('auth.lrs.key'), config('auth.lrs.secret'), config('auth.lrs.server'));
        });

        $this->app->alias(Logger::class, 'lrs-logger');
    }
}
