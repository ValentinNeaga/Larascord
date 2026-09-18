<?php

namespace Jakyeru\Larascord\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Jakyeru\Larascord\Models\DiscordAccount;
use Jakyeru\Larascord\Tests\Fixtures\User;
use Jakyeru\Larascord\Tests\TestCase;
use Orchestra\Testbench\Attributes\WithConfig;

class ReturningUserTest extends TestCase
{
    use RefreshDatabase;

    protected function discordUser(array $overrides = []): array
    {
        return array_merge([
            'id' => '81384788765712384',
            'username' => 'jakye',
            'global_name' => 'Jakye',
            'discriminator' => '0',
            'avatar' => 'abc',
            'email' => 'jakye@example.com',
            'verified' => true,
            'locale' => 'en-US',
            'mfa_enabled' => true,
        ], $overrides);
    }

    protected function fakeDiscord(array $overrides = []): void
    {
        Http::fake([
            'discord.com/api/oauth2/token' => Http::response([
                'access_token' => 'at', 'token_type' => 'Bearer', 'expires_in' => 604800,
                'refresh_token' => 'rt', 'scope' => 'identify email',
            ]),
            'discord.com/api/users/@me' => Http::response($this->discordUser($overrides)),
        ]);
    }

    protected function signedInUser(): User
    {
        $this->fakeDiscord();

        $this->get('/larascord/callback?code=valid-code');

        return User::first();
    }

    #[WithConfig('larascord.routes.login_alias', true, defer: false)]
    public function test_an_authenticated_user_visiting_login_is_not_sent_back_to_login()
    {
        Http::fake([
            'discord.com/api/oauth2/token' => Http::response([
                'access_token' => 'at', 'token_type' => 'Bearer', 'expires_in' => 604800,
                'refresh_token' => 'rt', 'scope' => 'identify email',
            ]),
            'discord.com/api/users/@me' => Http::sequence()
                ->push($this->discordUser())
                ->push($this->discordUser(['global_name' => 'Jakye Renamed'])),
        ]);

        $this->get('/larascord/callback?code=valid-code');

        $user = User::first();

        $this->actingAs($user)->get('/login')->assertRedirectContains('discord.com/oauth2/authorize');

        $response = $this->actingAs($user)->get('/larascord/callback?code=valid-code');

        $response->assertRedirect('/');

        $this->assertSame('Jakye Renamed', DiscordAccount::first()->global_name);
        $this->assertAuthenticatedAs($user);
    }

    public function test_an_authenticated_user_visiting_the_redirect_route_is_not_sent_back_to_it()
    {
        $user = $this->signedInUser();

        $this->fakeDiscord();

        $this->actingAs($user)->get('/larascord/redirect');

        $response = $this->actingAs($user)->get('/larascord/callback?code=valid-code');

        $response->assertRedirect('/');
    }

    public function test_linking_returns_to_the_page_the_user_started_from()
    {
        $user = $this->signedInUser();

        $this->fakeDiscord();

        $this->actingAs($user)
            ->withSession(['_previous' => ['url' => 'http://localhost/settings']])
            ->get('/larascord/link');

        $response = $this->actingAs($user)->get('/larascord/callback?code=valid-code');

        $response->assertRedirect('http://localhost/settings');
        $response->assertSessionHas('success', 'Your Discord account has been linked.');
    }

    public function test_the_intended_url_is_never_a_larascord_route()
    {
        $this->fakeDiscord();

        $this->withSession(['url.intended' => 'http://localhost/larascord/link'])
            ->get('/larascord/redirect');

        $this->get('/larascord/callback?code=valid-code')->assertRedirect('/');
    }

    public function test_it_returns_to_the_url_the_auth_middleware_stored()
    {
        $this->fakeDiscord();

        $this->withSession(['url.intended' => 'http://localhost/billing'])
            ->get('/larascord/redirect');

        $this->get('/larascord/callback?code=valid-code')->assertRedirect('http://localhost/billing');
    }

    public function test_it_honours_the_redirect_to_parameter()
    {
        $this->fakeDiscord();

        $this->get('/larascord/redirect?redirect_to=/pricing');

        $this->get('/larascord/callback?code=valid-code')->assertRedirect('/pricing');
    }

    public function test_it_refuses_to_redirect_to_another_host()
    {
        $this->fakeDiscord();

        $this->get('/larascord/redirect?redirect_to=https://evil.example.com/steal');

        $this->get('/larascord/callback?code=valid-code')->assertRedirect('/');
    }

    public function test_it_refuses_a_protocol_relative_redirect()
    {
        $this->fakeDiscord();

        $this->get('/larascord/redirect?redirect_to=//evil.example.com/steal');

        $this->get('/larascord/callback?code=valid-code')->assertRedirect('/');
    }

    #[WithConfig('larascord.routes.login_alias', true, defer: false)]
    public function test_the_flow_settles_instead_of_looping()
    {
        $user = $this->signedInUser();

        $this->fakeDiscord();

        $visited = [];
        $url = '/login';

        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($user)->get($url);

            if (!$response->isRedirect()) {
                break;
            }

            $url = $response->headers->get('Location');

            if (str_contains($url, 'discord.com')) {
                $url = '/larascord/callback?code=valid-code';
            }

            $visited[] = $url;
        }

        $this->assertSame(['/larascord/callback?code=valid-code', 'http://localhost'], $visited);
    }

    public function test_linking_never_returns_to_a_larascord_route()
    {
        $user = $this->signedInUser();

        $this->fakeDiscord();

        $this->actingAs($user)
            ->withSession(['_previous' => ['url' => 'http://localhost/larascord/link']])
            ->get('/larascord/link');

        $this->actingAs($user)->get('/larascord/callback?code=valid-code')->assertRedirect('/');
    }
}
