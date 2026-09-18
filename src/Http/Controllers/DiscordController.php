<?php

namespace Jakyeru\Larascord\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jakyeru\Larascord\Events\DiscordAccountUnlinked;
use Jakyeru\Larascord\Events\UserAuthenticated;
use Jakyeru\Larascord\Facades\Larascord;
use Jakyeru\Larascord\Http\Requests\CallbackRequest;
use Jakyeru\Larascord\Models\DiscordAccount;
use Jakyeru\Larascord\Services\DiscordService;
use Jakyeru\Larascord\Types\AccessToken;

class DiscordController extends Controller
{
    /**
     * Redirect the user to Discord's authorization page.
     */
    public function redirect(Request $request): RedirectResponse
    {
        return $this->start($request, Larascord::INTENT_LOGIN);
    }

    /**
     * Redirect an authenticated user to Discord in order to link their account.
     */
    public function link(Request $request): RedirectResponse
    {
        return $this->start($request, Larascord::INTENT_LINK);
    }

    /**
     * Start the Discord OAuth2 flow.
     */
    protected function start(Request $request, string $intent): RedirectResponse
    {
        $state = null;

        if (config('larascord.verify_state', true)) {
            $state = Larascord::generateState();

            $request->session()->put(Larascord::STATE_KEY, $state);
        }

        $request->session()->put(Larascord::INTENT_KEY, $intent);

        $request->session()->forget(Larascord::INTENDED_KEY);

        $intended = $this->returnUrl($request, $request->string('redirect_to')->toString());

        if (!$intended && $intent === Larascord::INTENT_LINK) {
            $intended = $this->returnUrl($request, url()->previous());
        }

        if ($intended) {
            $request->session()->put(Larascord::INTENDED_KEY, $intended);
        }

        return redirect()->away(Larascord::authorizationUrl($state));
    }

    /**
     * Handle the Discord OAuth2 callback.
     */
    public function callback(CallbackRequest $request): RedirectResponse | JsonResponse
    {
        if (!$this->hasValidState($request)) {
            return $this->throwError('invalid_state');
        }

        if (count(config('larascord.guilds', [])) && !Larascord::hasScope('guilds')) {
            return $this->throwError('missing_guilds_scope');
        }

        try {
            $accessToken = (new DiscordService())->getAccessTokenFromCode($request->string('code')->toString());
        } catch (Exception $e) {
            return $this->throwError('invalid_code', $e);
        }

        try {
            $discordUser = (new DiscordService())->getCurrentUser($accessToken);
            $discordUser->setAccessToken($accessToken);
        } catch (Exception $e) {
            return $this->throwError('authorization_failed', $e);
        }

        if (Larascord::hasScope('email') && empty($discordUser->email)) {
            return $this->throwError('missing_email');
        }

        if ($error = $this->verifyGuilds($accessToken)) {
            return $error;
        }

        if ($error = $this->verifyGuildRoles($accessToken)) {
            return $error;
        }

        $account = DiscordAccount::forDiscordId($discordUser->id);
        $guard = Auth::guard(config('larascord.guard', 'web'));
        $linking = $request->session()->pull(Larascord::INTENT_KEY) === Larascord::INTENT_LINK && $guard->check();

        if ($linking) {
            $user = $guard->user();

            if ($account && $account->user_id && $account->user_id != $user->getAuthIdentifier()) {
                return $this->throwError('account_already_linked');
            }
        } else {
            $user = $account?->user;

            if (!$user) {
                try {
                    $user = Larascord::resolveUser($discordUser);
                } catch (Exception $e) {
                    return $this->throwError('database_error', $e);
                }
            }

            if (!$user) {
                return $this->throwError('registration_disabled');
            }
        }

        $authenticated = $guard->check() && $guard->id() == $user->getAuthIdentifier();

        if ($authenticated) {
            $request->session()->put('auth.password_confirmed_at', time());
        }

        DB::beginTransaction();

        try {
            $account = Larascord::syncAccount($discordUser, $user);

            Larascord::storeAccessToken($account, $accessToken);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->throwError('database_error', $e);
        }

        DB::commit();

        if ($linking) {
            return $this->redirectWithSuccess('account_linked', $this->intendedUrl($request));
        }

        if (!$authenticated) {
            $guard->login($user, config('larascord.remember_me', false));

            $request->session()->regenerate();
        }

        UserAuthenticated::dispatch($account, $user);

        return redirect($this->intendedUrl($request));
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard(config('larascord.guard', 'web'))->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(config('larascord.redirect_logout', '/'));
    }

    /**
     * Unlink the authenticated user's Discord account.
     */
    public function unlink(Request $request): RedirectResponse | JsonResponse
    {
        $user = Auth::guard(config('larascord.guard', 'web'))->user();
        $account = DiscordAccount::forUser($user);

        if (!$account) {
            return $this->throwError('missing_discord_account');
        }

        try {
            $account->revoke();
        } catch (Exception $e) {
            return $this->throwError('revoke_token_failed', $e);
        }

        DiscordAccountUnlinked::dispatch($account);

        return $this->redirectWithSuccess('account_unlinked', config('larascord.redirect_unlink', '/'));
    }

    /**
     * Verify the OAuth2 state returned by Discord.
     */
    protected function hasValidState(Request $request): bool
    {
        if (!config('larascord.verify_state', true)) {
            return true;
        }

        $state = $request->session()->pull(Larascord::STATE_KEY);

        return $state && hash_equals($state, $request->string('state')->toString());
    }

    /**
     * Verify that the user is a member of the configured guilds.
     */
    protected function verifyGuilds(AccessToken $accessToken): RedirectResponse | JsonResponse | null
    {
        if (!count(config('larascord.guilds', []))) {
            return null;
        }

        try {
            $guilds = (new DiscordService())->getCurrentUserGuilds($accessToken);
        } catch (Exception $e) {
            return $this->throwError('authorization_failed_guilds', $e);
        }

        if (!(new DiscordService())->isUserInGuilds($guilds)) {
            return $this->throwError('not_member_guild_only');
        }

        return null;
    }

    /**
     * Verify that the user has the configured roles.
     */
    protected function verifyGuildRoles(AccessToken $accessToken): RedirectResponse | JsonResponse | null
    {
        if (!count(config('larascord.guild_roles', []))) {
            return null;
        }

        if (!$accessToken->hasScopes(['guilds', 'guilds.members.read'])) {
            return $this->throwError('missing_guilds_members_read_scope');
        }

        foreach (config('larascord.guild_roles') as $guildId => $roles) {
            try {
                $guildMember = (new DiscordService())->getGuildMember($accessToken, $guildId);
            } catch (Exception $e) {
                return $this->throwError('not_member_guild_only', $e);
            }

            if (!(new DiscordService())->hasRoleInGuild($guildMember, $roles)) {
                return $this->throwError('missing_role');
            }
        }

        return null;
    }

    /**
     * Handle the throwing of an error.
     */
    protected function throwError(string $key, ?Exception $exception = null): RedirectResponse | JsonResponse
    {
        if (app()->hasDebugModeEnabled()) {
            return response()->json([
                'larascord_message' => config('larascord.error_messages.' . $key),
                'message' => $exception?->getMessage(),
                'code' => $exception?->getCode(),
            ]);
        }

        $message = config('larascord.error_messages.' . $key . '.message', 'An error occurred while trying to log you in.');

        return redirect(config('larascord.error_messages.' . $key . '.redirect') ?: '/')->with('error', $message);
    }

    /**
     * Redirect the user with a success message.
     */
    protected function redirectWithSuccess(string $key, string $fallback): RedirectResponse
    {
        $message = config('larascord.success_messages.' . $key . '.message');
        $redirect = config('larascord.success_messages.' . $key . '.redirect') ?: $fallback;

        return redirect($redirect)->with('success', $message);
    }

    /**
     * Get the URL the user should be sent to once they are back from Discord.
     */
    protected function intendedUrl(Request $request): string
    {
        return $this->returnUrl($request, $request->session()->pull(Larascord::INTENDED_KEY))
            ?? $this->returnUrl($request, $request->session()->pull('url.intended'))
            ?? config('larascord.redirect_login', '/');
    }

    /**
     * Make sure the given URL is safe to send the user back to.
     */
    protected function returnUrl(Request $request, ?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host && $host !== $request->getHost()) {
            return null;
        }

        return $this->startsTheFlow($url) ? null : $url;
    }

    /**
     * Determine whether the given URL sends the user straight back to Discord.
     */
    protected function startsTheFlow(string $url): bool
    {
        $path = trim(parse_url($url, PHP_URL_PATH) ?: '', '/');
        $prefix = trim(config('larascord.routes.prefix', 'larascord'), '/');

        if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
            return true;
        }

        return (bool) config('larascord.routes.login_alias', false) && $path === 'login';
    }
}
