<?php

namespace Jakyeru\Larascord;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Jakyeru\Larascord\Contracts\ResolvesUsers;
use Jakyeru\Larascord\Events\DiscordAccountCreated;
use Jakyeru\Larascord\Events\DiscordAccountLinked;
use Jakyeru\Larascord\Events\DiscordAccountUpdated;
use Jakyeru\Larascord\Models\DiscordAccount;
use Jakyeru\Larascord\Types\AccessToken;
use Jakyeru\Larascord\Types\User;

class Larascord
{
    /**
     * The session key holding the OAuth2 state.
     */
    public const STATE_KEY = 'larascord.state';

    /**
     * The session key holding the URL the user should be sent back to.
     */
    public const INTENDED_KEY = 'larascord.intended';

    /**
     * The callback used to resolve the application user.
     */
    protected ?Closure $userResolver = null;

    /**
     * Get the Discord authorization URL.
     */
    public function authorizationUrl(?string $state = null, array $parameters = []): string
    {
        return 'https://discord.com/oauth2/authorize?' . http_build_query(array_merge([
            'client_id' => config('larascord.client_id'),
            'redirect_uri' => config('larascord.redirect_uri'),
            'response_type' => 'code',
            'scope' => $this->scopes(),
            'prompt' => config('larascord.prompt', 'none'),
            'state' => $state,
        ], $parameters), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Get the configured scopes as a space delimited string.
     */
    public function scopes(): string
    {
        $scopes = config('larascord.scopes', ['identify']);

        return implode(' ', is_array($scopes) ? $scopes : explode(' ', $scopes));
    }

    /**
     * Determine whether the given scope is configured.
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, explode(' ', $this->scopes()));
    }

    /**
     * Generate a new OAuth2 state.
     */
    public function generateState(): string
    {
        return Str::random(40);
    }

    /**
     * Register a callback used to resolve the application user.
     */
    public function resolveUsersUsing(Closure $callback): static
    {
        $this->userResolver = $callback;

        return $this;
    }

    /**
     * Resolve the application user the given Discord user should be linked to.
     */
    public function resolveUser(User $discordUser): ?Authenticatable
    {
        if ($this->userResolver) {
            return call_user_func($this->userResolver, $discordUser);
        }

        return app(ResolvesUsers::class)->resolve($discordUser);
    }

    /**
     * Create or update the Discord account belonging to the given Discord user.
     */
    public function syncAccount(User $discordUser, ?Authenticatable $user = null): DiscordAccount
    {
        $account = DiscordAccount::forDiscordId($discordUser->id);
        $attributes = $discordUser->toArray();
        $previousUserId = $account?->user_id;

        if ($user) {
            $attributes['user_id'] = $user->getAuthIdentifier();
        }

        if ($account) {
            $account->fill($attributes)->save();

            DiscordAccountUpdated::dispatch($account);
        } else {
            $account = DiscordAccount::create($attributes);

            DiscordAccountCreated::dispatch($account);
        }

        if ($user && $previousUserId != $user->getAuthIdentifier()) {
            DiscordAccountLinked::dispatch($account, $user);
        }

        return $account;
    }

    /**
     * Store the access token belonging to the given Discord account.
     */
    public function storeAccessToken(DiscordAccount $account, AccessToken $accessToken): void
    {
        $account->accessToken()->updateOrCreate([], $accessToken->toArray());
    }

    /**
     * Get the Larascord version.
     */
    public function version(): string
    {
        return LarascordServiceProvider::VERSION;
    }
}
