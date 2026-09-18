<?php

namespace Jakyeru\Larascord;

use Illuminate\Support\ServiceProvider;
use Jakyeru\Larascord\Contracts\ResolvesUsers;
use Jakyeru\Larascord\Resolvers\UserResolver;

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
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'larascord');

        $this->app->singleton(Larascord::class);
        $this->app->bind(ResolvesUsers::class, UserResolver::class);
    }

    /*
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishing();
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
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('larascord.php'),
        ], ['larascord-config', 'config']);

        $this->publishesMigrations([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], ['larascord-migrations', 'migrations']);
    }

    /*
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (!config('larascord.routes.enabled', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/routes/larascord.php');
    }
}
