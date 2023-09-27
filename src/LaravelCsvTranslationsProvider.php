<?php

namespace Novadaemon\LaravelCsvTranslations;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Novadaemon\LaravelCsvTranslations\Console\Commands\ImportTranslationsCommand;

class LaravelCsvTranslationsProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportTranslationsCommand::class,
            ]);
        }
    }
}
