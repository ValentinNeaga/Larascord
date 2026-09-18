<?php

namespace Jakyeru\Larascord\Models;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Jakyeru\Larascord\Services\DiscordService;
use Jakyeru\Larascord\Types\AccessToken;
use Jakyeru\Larascord\Types\GuildMember;

class DiscordAccount extends Model
{
    /**
     * The Discord CDN base URL.
     */
    protected string $cdn = 'https://cdn.discordapp.com';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'discord_id',
        'username',
        'global_name',
        'discriminator',
        'email',
        'avatar',
        'verified',
        'banner',
        'banner_color',
        'accent_color',
        'locale',
        'mfa_enabled',
        'premium_type',
        'public_flags',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('larascord.database.accounts_table', 'larascord_accounts');
    }

    /**
     * Get the database connection for the model.
     */
    public function getConnectionName(): ?string
    {
        return config('larascord.database.connection') ?: parent::getConnectionName();
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'mfa_enabled' => 'boolean',
            'premium_type' => 'integer',
            'public_flags' => 'integer',
        ];
    }

    /**
     * Get the user the Discord account is linked to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('larascord.users.model'));
    }

    /**
     * Get the access token relationship.
     */
    public function accessToken(): HasOne
    {
        return $this->hasOne(DiscordAccessToken::class, 'discord_account_id');
    }

    /**
     * Get the Discord account linked to the given user.
     */
    public static function forUser(Authenticatable $user): ?static
    {
        return static::query()->where('user_id', $user->getAuthIdentifier())->first();
    }

    /**
     * Get the Discord account matching the given Discord ID.
     */
    public static function forDiscordId(string $discordId): ?static
    {
        return static::query()->where('discord_id', $discordId)->first();
    }

    /**
     * Get the account's tag.
     */
    public function getTagAttribute(): string
    {
        if ($this->discriminator && $this->discriminator != 0) {
            return $this->username . '#' . $this->discriminator;
        }

        if (!$this->global_name || $this->username === $this->global_name) {
            return $this->username;
        }

        return $this->username . ' (' . $this->global_name . ')';
    }

    /**
     * Get the account's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->global_name ?: $this->username;
    }

    /**
     * Get the account's avatar url.
     */
    public function getAvatar(array $options = []): string
    {
        $extension = $options['extension'] ?? 'png';
        $size = $options['size'] ?? 128;
        $color = $options['color'] ?? 0;

        if ($this->avatar) {
            return $this->cdn . '/avatars/' . $this->discord_id . '/' . $this->avatar . '.' . $extension . ($size ? '?size=' . $size : '');
        }

        return $this->cdn . '/embed/avatars/' . $color . '.png';
    }

    /**
     * Get the account's access token, refreshing it if it has expired.
     */
    public function getAccessToken(): ?AccessToken
    {
        $accessToken = $this->accessToken()->first();

        if (!$accessToken) {
            return null;
        }

        if ($accessToken->expires_at->isPast()) {
            return $this->refreshAccessToken();
        }

        return new AccessToken($accessToken);
    }

    /**
     * Refresh the account's access token.
     */
    public function refreshAccessToken(): ?AccessToken
    {
        $accessToken = $this->accessToken()->first();

        if (!$accessToken) {
            return null;
        }

        try {
            $response = (new DiscordService())->refreshAccessToken($accessToken->refresh_token);
        } catch (RequestException) {
            return null;
        }

        $accessToken->update($response->toArray());

        return $response;
    }

    /**
     * Get the account's guilds.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function getGuilds(bool $withCounts = false): Collection
    {
        return collect((new DiscordService())->getCurrentUserGuilds($this->requireAccessToken(), $withCounts));
    }

    /**
     * Get the account's guild member object for the given guild.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function getGuildMember(string $guildId): GuildMember
    {
        return (new DiscordService())->getGuildMember($this->requireAccessToken(), $guildId);
    }

    /**
     * Add the account to the given guild.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function joinGuild(string $guildId, array $options = []): GuildMember
    {
        return (new DiscordService())->joinGuild($this->requireAccessToken(), $this->discord_id, $guildId, $options);
    }

    /**
     * Get the account's connections.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function getConnections(): Collection
    {
        return collect((new DiscordService())->getCurrentUserConnections($this->requireAccessToken()));
    }

    /**
     * Revoke the access token and delete the account.
     *
     * @throws RequestException
     */
    public function revoke(): bool
    {
        $accessToken = $this->accessToken()->first();

        if ($accessToken) {
            (new DiscordService())->revokeAccessToken($accessToken->refresh_token);
        }

        return (bool) $this->delete();
    }

    /**
     * Get a valid access token or fail.
     *
     * @throws Exception
     */
    protected function requireAccessToken(): AccessToken
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new Exception('The access token is invalid.');
        }

        return $accessToken;
    }
}
