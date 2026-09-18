<?php

namespace Jakyeru\Larascord\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Jakyeru\Larascord\Models\DiscordAccount;
use Jakyeru\Larascord\Tests\Fixtures\User;
use Jakyeru\Larascord\Tests\TestCase;

class DiscordAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function account(array $overrides = []): DiscordAccount
    {
        $user = User::create([
            'name' => 'Jakye',
            'email' => ($overrides['discord_id'] ?? '81384788765712384') . '@example.com',
            'password' => 'secret',
        ]);

        return DiscordAccount::create(array_merge([
            'user_id' => $user->getKey(),
            'discord_id' => '81384788765712384',
            'username' => 'jakye',
            'global_name' => 'Jakye',
            'discriminator' => '0',
            'avatar' => 'a363a84e969bcbe1353eb2fdfb2e50e6',
            'email' => 'jakye@example.com',
        ], $overrides));
    }

    public function test_larascord_owns_its_own_tables_only()
    {
        $this->assertTrue(Schema::hasTable('larascord_accounts'));
        $this->assertTrue(Schema::hasTable('larascord_access_tokens'));

        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'email', 'password', 'remember_token', 'created_at', 'updated_at'],
            Schema::getColumnListing('users'),
        );
    }

    public function test_it_resolves_the_tag()
    {
        $this->assertSame('jakye', $this->account(['global_name' => 'jakye'])->tag);
    }

    public function test_it_resolves_the_tag_of_a_renamed_account()
    {
        $this->assertSame('jakye (Jakye)', $this->account()->tag);
    }

    public function test_it_resolves_the_tag_of_a_legacy_account()
    {
        $this->assertSame('jakye#0001', $this->account(['discriminator' => '0001'])->tag);
    }

    public function test_it_resolves_the_display_name()
    {
        $this->assertSame('Jakye', $this->account()->display_name);
        $this->assertSame('jakye', $this->account(['global_name' => null, 'discord_id' => '1'])->display_name);
    }

    public function test_it_builds_the_avatar_url()
    {
        $account = $this->account();

        $this->assertSame(
            'https://cdn.discordapp.com/avatars/81384788765712384/a363a84e969bcbe1353eb2fdfb2e50e6.png?size=128',
            $account->getAvatar(),
        );

        $this->assertSame(
            'https://cdn.discordapp.com/avatars/81384788765712384/a363a84e969bcbe1353eb2fdfb2e50e6.webp?size=64',
            $account->getAvatar(['extension' => 'webp', 'size' => 64]),
        );
    }

    public function test_it_falls_back_to_the_default_avatar()
    {
        $this->assertSame(
            'https://cdn.discordapp.com/embed/avatars/2.png',
            $this->account(['avatar' => null])->getAvatar(['color' => 2]),
        );
    }

    public function test_the_access_token_is_encrypted_at_rest()
    {
        $account = $this->account();

        $account->accessToken()->create([
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_type' => 'Bearer',
            'expires_in' => 604800,
            'expires_at' => now()->addWeek(),
            'scope' => 'identify email',
        ]);

        $stored = $account->accessToken()->toBase()->first();

        $this->assertNotSame('access-token', $stored->access_token);
        $this->assertSame('access-token', $account->accessToken->access_token);
    }

    public function test_it_refreshes_an_expired_access_token()
    {
        Http::fake([
            'discord.com/api/oauth2/token' => Http::response([
                'access_token' => 'new-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 604800,
                'refresh_token' => 'new-refresh-token',
                'scope' => 'identify email',
            ]),
        ]);

        $account = $this->account();

        $account->accessToken()->create([
            'access_token' => 'expired-access-token',
            'refresh_token' => 'refresh-token',
            'token_type' => 'Bearer',
            'expires_in' => 604800,
            'expires_at' => now()->subDay(),
            'scope' => 'identify email',
        ]);

        $this->assertSame('new-access-token', $account->getAccessToken()->access_token);
        $this->assertSame('new-access-token', $account->accessToken()->first()->access_token);
    }

    public function test_it_is_reachable_without_the_trait()
    {
        $account = $this->account();

        $this->assertTrue($account->is(DiscordAccount::forUser($account->user)));
        $this->assertTrue($account->is(DiscordAccount::forDiscordId('81384788765712384')));
    }

    public function test_the_trait_proxies_to_the_account()
    {
        $account = $this->account();
        $user = $account->user;

        $this->assertTrue($user->hasDiscordAccount());
        $this->assertSame('jakye (Jakye)', $user->getDiscordTag());
        $this->assertSame($account->getAvatar(), $user->getDiscordAvatar());
    }

    public function test_the_table_names_are_configurable()
    {
        $this->assertSame('larascord_accounts', (new DiscordAccount())->getTable());

        config()->set('larascord.database.accounts_table', 'discord_profiles');

        $this->assertSame('discord_profiles', (new DiscordAccount())->getTable());
    }
}
