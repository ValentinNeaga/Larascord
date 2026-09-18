<?php

namespace Jakyeru\Larascord\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jakyeru\Larascord\Facades\Larascord;
use Jakyeru\Larascord\LarascordServiceProvider;
use Jakyeru\Larascord\Tests\Fixtures\User;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LarascordServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Larascord' => Larascord::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.debug', false);
        $app['config']->set('app.url', 'http://localhost:8000');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('auth.providers.users.model', User::class);

        $app['config']->set('larascord.client_id', '0000000000000000');
        $app['config']->set('larascord.client_secret', 'ZFe6qUaYL4PSJ5vH4Tm8gtJaX9xmeqJp');
        $app['config']->set('larascord.redirect_uri', 'http://localhost:8000/larascord/callback');
        $app['config']->set('larascord.scopes', ['identify', 'email']);
        $app['config']->set('larascord.users.model', User::class);
        $app['config']->set('larascord.verify_state', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        $this->loadMigrationsFrom(__DIR__ . '/../src/database/migrations');
    }
}
