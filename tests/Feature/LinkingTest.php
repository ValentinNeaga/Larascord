<?php

namespace Jakyeru\Larascord\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Jakyeru\Larascord\Models\DiscordAccount;
use Jakyeru\Larascord\Tests\Fixtures\User;
use Jakyeru\Larascord\Tests\TestCase;

class LinkingTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeDiscord(string $discordId = '81384788765712384'): void
    {
        Http::fake([
            'discord.com/api/oauth2/token/revoke' => Http::response([]),
            'discord.com/api/oauth2/token' => Http::response([
                'access_token' => 'access-token',
                'token_type' => 'Bearer',
                'expires_in' => 604800,
                'refresh_token' => 'refresh-token',
                'scope' => 'identify email',
            ]),
            'discord.com/api/users/@me' => Http::response([
                'id' => $discordId,
                'username' => 'jakye',
                'global_name' => 'Jakye',
                'discriminator' => '0',
                'avatar' => null,
                'email' => 'jakye@example.com',
                'verified' => true,
                'locale' => 'en-US',
                'mfa_enabled' => true,
            ]),
        ]);
    }

    protected function user(string $email = 'user@example.com'): User
    {
        return User::create([
            'name' => 'Existing User',
            'email' => $email,
            'password' => 'secret',
        ]);
    }

    public function test_an_authenticated_user_can_link_a_discord_account()
    {
        $user = $this->user();

        $this->fakeDiscord();

        $this->actingAs($user)->get('/larascord/link')->assertRedirectContains('discord.com/oauth2/authorize');

        $response = $this->actingAs($user)->get('/larascord/callback?code=valid-code');

        $response->assertSessionHas('success', 'Your Discord account has been linked.');

        $this->assertSame(1, User::count());
        $this->assertTrue($user->fresh()->hasDiscordAccount());
        $this->assertSame('81384788765712384', $user->fresh()->discordAccount->discord_id);
    }

    public function test_a_discord_account_cannot_be_linked_to_two_users()
    {
        $first = $this->user('first@example.com');
        $second = $this->user('second@example.com');

        $this->fakeDiscord();

        $this->actingAs($first)->get('/larascord/callback?code=valid-code');

        $response = $this->actingAs($second)->get('/larascord/callback?code=valid-code');

        $response->assertSessionHas('error', 'This Discord account is already linked to another user.');

        $this->assertFalse($second->fresh()->hasDiscordAccount());
    }

    public function test_an_authenticated_user_can_unlink_their_discord_account()
    {
        $user = $this->user();

        $this->fakeDiscord();

        $this->actingAs($user)->get('/larascord/callback?code=valid-code');

        $this->assertSame(1, DiscordAccount::count());

        $response = $this->actingAs($user)->delete('/larascord/unlink');

        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'Your Discord account has been unlinked.');

        $this->assertSame(0, DiscordAccount::count());
        $this->assertSame(1, User::count());
        $this->assertFalse($user->fresh()->hasDiscordAccount());
    }

    public function test_deleting_a_user_deletes_the_discord_account()
    {
        $user = $this->user();

        $this->fakeDiscord();

        $this->actingAs($user)->get('/larascord/callback?code=valid-code');

        $user->delete();

        $this->assertSame(0, DiscordAccount::count());
    }
}
