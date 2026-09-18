<?php

namespace Jakyeru\Larascord\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Jakyeru\Larascord\Events\DiscordAccountCreated;
use Jakyeru\Larascord\Events\DiscordAccountLinked;
use Jakyeru\Larascord\Events\UserAuthenticated;
use Jakyeru\Larascord\Models\DiscordAccount;
use Jakyeru\Larascord\Tests\Fixtures\User;
use Jakyeru\Larascord\Tests\TestCase;
use Orchestra\Testbench\Attributes\WithConfig;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function discordUser(array $overrides = []): array
    {
        return array_merge([
            'id' => '81384788765712384',
            'username' => 'jakye',
            'global_name' => 'Jakye',
            'discriminator' => '0',
            'avatar' => 'a363a84e969bcbe1353eb2fdfb2e50e6',
            'email' => 'jakye@example.com',
            'verified' => true,
            'locale' => 'en-US',
            'mfa_enabled' => true,
        ], $overrides);
    }

    protected function fakeDiscord(array $user = []): void
    {
        Http::fake([
            'discord.com/api/oauth2/token' => Http::response([
                'access_token' => 'access-token',
                'token_type' => 'Bearer',
                'expires_in' => 604800,
                'refresh_token' => 'refresh-token',
                'scope' => 'identify email',
            ]),
            'discord.com/api/users/@me' => Http::response($this->discordUser($user)),
        ]);
    }

    public function test_it_creates_a_user_and_logs_them_in()
    {
        Event::fake([DiscordAccountCreated::class, DiscordAccountLinked::class, UserAuthenticated::class]);

        $this->fakeDiscord();

        $response = $this->get('/larascord/callback?code=valid-code');

        $response->assertRedirect('/');

        $user = User::first();

        $this->assertNotNull($user);
        $this->assertSame('Jakye', $user->name);
        $this->assertSame('jakye@example.com', $user->email);
        $this->assertAuthenticatedAs($user);

        $account = DiscordAccount::first();

        $this->assertSame('81384788765712384', $account->discord_id);
        $this->assertSame($user->getKey(), $account->user_id);
        $this->assertSame('access-token', $account->accessToken->access_token);

        Event::assertDispatched(DiscordAccountCreated::class);
        Event::assertDispatched(DiscordAccountLinked::class);
        Event::assertDispatched(UserAuthenticated::class);
    }

    public function test_it_links_an_existing_user_by_email()
    {
        $user = User::create([
            'name' => 'Someone Else',
            'email' => 'jakye@example.com',
            'password' => 'secret',
        ]);

        $this->fakeDiscord();

        $this->get('/larascord/callback?code=valid-code')->assertRedirect('/');

        $this->assertSame(1, User::count());
        $this->assertSame('Someone Else', $user->fresh()->name);
        $this->assertSame($user->getKey(), DiscordAccount::first()->user_id);
        $this->assertAuthenticatedAs($user);
    }

    #[WithConfig('larascord.users.link_by_email', false)]
    public function test_it_does_not_link_by_email_when_disabled()
    {
        User::create([
            'name' => 'Someone Else',
            'email' => 'jakye@example.com',
            'password' => 'secret',
        ]);

        $this->fakeDiscord();

        $this->get('/larascord/callback?code=valid-code');

        $this->assertGuest();
        $this->assertSame(1, User::count());
    }

    #[WithConfig('larascord.users.create_missing', false)]
    public function test_it_refuses_to_register_a_user_when_registration_is_disabled()
    {
        $this->fakeDiscord();

        $response = $this->get('/larascord/callback?code=valid-code');

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'There is no account matching your Discord account.');

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function test_it_logs_a_returning_user_in_without_creating_a_second_user()
    {
        Http::fake([
            'discord.com/api/oauth2/token' => Http::response([
                'access_token' => 'access-token',
                'token_type' => 'Bearer',
                'expires_in' => 604800,
                'refresh_token' => 'refresh-token',
                'scope' => 'identify email',
            ]),
            'discord.com/api/users/@me' => Http::sequence()
                ->push($this->discordUser())
                ->push($this->discordUser(['global_name' => 'Jakye Renamed'])),
        ]);

        $this->get('/larascord/callback?code=valid-code');

        $this->post('/larascord/logout');

        $this->get('/larascord/callback?code=valid-code');

        $this->assertSame(1, User::count());
        $this->assertSame(1, DiscordAccount::count());
        $this->assertSame('Jakye Renamed', DiscordAccount::first()->global_name);
        $this->assertSame('Jakye', User::first()->name);
    }

    public function test_it_uses_a_custom_user_resolver()
    {
        \Jakyeru\Larascord\Facades\Larascord::resolveUsersUsing(function ($discordUser) {
            return User::create([
                'name' => 'Resolved ' . $discordUser->username,
                'email' => $discordUser->id . '@discord.local',
                'password' => 'secret',
            ]);
        });

        $this->fakeDiscord();

        $this->get('/larascord/callback?code=valid-code');

        $this->assertSame('Resolved jakye', User::first()->name);
        $this->assertSame('81384788765712384@discord.local', User::first()->email);
    }

    #[WithConfig('larascord.verify_state', true)]
    public function test_it_verifies_the_state()
    {
        $this->fakeDiscord();

        $response = $this->get('/larascord/callback?code=valid-code&state=forged');

        $response->assertSessionHas('error', 'The login attempt has expired. Please try again.');
        $this->assertGuest();

        $this->get('/larascord/redirect');

        $response = $this->get('/larascord/callback?code=valid-code&state=' . session('larascord.state'));

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_it_regenerates_the_session_on_login()
    {
        $this->fakeDiscord();

        $this->startSession();

        $before = session()->getId();

        $this->get('/larascord/callback?code=valid-code');

        $this->assertNotSame($before, session()->getId());
    }

    public function test_it_logs_the_user_out()
    {
        $this->fakeDiscord();

        $this->get('/larascord/callback?code=valid-code');

        $this->assertAuthenticated();

        $this->post('/larascord/logout')->assertRedirect('/');

        $this->assertGuest();
    }
}
