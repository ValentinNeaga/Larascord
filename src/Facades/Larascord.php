<?php

namespace Jakyeru\Larascord\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string authorizationUrl(?string $state = null, array $parameters = [])
 * @method static string scopes()
 * @method static bool hasScope(string $scope)
 * @method static string generateState()
 * @method static \Jakyeru\Larascord\Larascord resolveUsersUsing(\Closure $callback)
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null resolveUser(\Jakyeru\Larascord\Types\User $discordUser)
 * @method static \Jakyeru\Larascord\Models\DiscordAccount syncAccount(\Jakyeru\Larascord\Types\User $discordUser, ?\Illuminate\Contracts\Auth\Authenticatable $user = null)
 * @method static void storeAccessToken(\Jakyeru\Larascord\Models\DiscordAccount $account, \Jakyeru\Larascord\Types\AccessToken $accessToken)
 * @method static string version()
 *
 * @see \Jakyeru\Larascord\Larascord
 */
class Larascord extends Facade
{
    /**
     * The session key holding the OAuth2 state.
     */
    public const STATE_KEY = \Jakyeru\Larascord\Larascord::STATE_KEY;

    /**
     * The session key holding the URL the user should be sent back to.
     */
    public const INTENDED_KEY = \Jakyeru\Larascord\Larascord::INTENDED_KEY;

    /**
     * The session key holding the reason the flow was started.
     */
    public const INTENT_KEY = \Jakyeru\Larascord\Larascord::INTENT_KEY;

    /**
     * The flow was started to log the visitor in.
     */
    public const INTENT_LOGIN = \Jakyeru\Larascord\Larascord::INTENT_LOGIN;

    /**
     * The flow was started to link an account to the authenticated user.
     */
    public const INTENT_LINK = \Jakyeru\Larascord\Larascord::INTENT_LINK;

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Jakyeru\Larascord\Larascord::class;
    }
}
