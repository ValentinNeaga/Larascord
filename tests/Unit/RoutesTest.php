<?php

namespace Jakyeru\Larascord\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Jakyeru\Larascord\Tests\TestCase;
use Orchestra\Testbench\Attributes\WithConfig;

class RoutesTest extends TestCase
{
    public function test_redirect_route_sends_the_user_to_discord()
    {
        $response = $this->get('/larascord/redirect');

        $response->assertStatus(302);

        $response->assertRedirect('https://discord.com/oauth2/authorize?client_id=0000000000000000&redirect_uri=http%3A%2F%2Flocalhost%3A8000%2Flarascord%2Fcallback&response_type=code&scope=identify%20email&prompt=none');
    }

    public function test_callback_route_requires_a_code()
    {
        $response = $this->get('/larascord/callback');

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'code' => 'The code field is required.',
        ]);
    }

    #[WithConfig('larascord.routes.login_alias', true, defer: false)]
    public function test_authenticated_routes_are_protected()
    {
        $this->get('/larascord/link')->assertRedirect('/login');

        $this->delete('/larascord/unlink')->assertRedirect('/login');
    }

    public function test_the_login_alias_is_not_registered_by_default()
    {
        $this->assertFalse(Route::has('login'));
    }

    #[WithConfig('larascord.routes.login_alias', true, defer: false)]
    public function test_the_login_alias_can_be_enabled()
    {
        $this->assertTrue(Route::has('login'));

        $this->get('/login')->assertRedirectContains('discord.com/oauth2/authorize');
    }

    public function test_larascord_does_not_register_routes_outside_of_its_own_namespace()
    {
        $names = collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->reject(fn (string $name) => str_starts_with($name, 'larascord.'))
            ->values();

        $this->assertEmpty($names->all());
    }

    #[WithConfig('larascord.routes.enabled', false, defer: false)]
    public function test_routes_can_be_disabled()
    {
        $this->assertFalse(Route::has('larascord.callback'));
    }

    #[WithConfig('larascord.routes.prefix', 'auth/discord', defer: false)]
    public function test_the_prefix_is_configurable()
    {
        $this->assertSame('auth/discord/callback', Route::getRoutes()->getByName('larascord.callback')->uri());
    }
}
