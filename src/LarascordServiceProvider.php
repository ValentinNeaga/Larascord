<?php

namespace Jakyeru\Larascord;

use Illuminate\Support\ServiceProvider;

class LarascordServiceProvider extends ServiceProvider
{
    /*
     * The current version of Larascord.
     *
     * @var string
     */
    const VERSION = '8.0.0';

    /*
     * Register the application services.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerConfiguration();
        }
    }

    /*
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerConfiguration();
        }

        $this->registerRoutes();
    }

    /*
     * Register the package commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            Console\Commands\InstallCommand::class,
            Console\Commands\PublishCommand::class,
        ]);
    }

    /*
     * Register the package configuration.
     */
    protected function registerConfiguration(): void
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('larascord.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/database/migrations/' => database_path('migrations'),
        ], 'migrations');
    }

    /*
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/larascord.php');
    }
}
