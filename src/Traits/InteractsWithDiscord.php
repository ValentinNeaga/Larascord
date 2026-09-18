<?php

namespace Jakyeru\Larascord\Traits;

use Exception;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Jakyeru\Larascord\Models\DiscordAccount;
use Jakyeru\Larascord\Types\AccessToken;
use Jakyeru\Larascord\Types\GuildMember;

trait InteractsWithDiscord
{
    /**
     * Get the user's Discord account relationship.
     */
    public function discordAccount(): HasOne
    {
        return $this->hasOne(DiscordAccount::class, 'user_id');
    }

    /**
     * Determine whether the user has linked a Discord account.
     */
    public function hasDiscordAccount(): bool
    {
        return $this->discordAccount()->exists();
    }

    /**
     * Get the user's Discord tag.
     */
    public function getDiscordTag(): ?string
    {
        return $this->discordAccount?->tag;
    }

    /**
     * Get the user's Discord avatar url.
     */
    public function getDiscordAvatar(array $options = []): ?string
    {
        return $this->discordAccount?->getAvatar($options);
    }

    /**
     * Get the user's Discord access token.
     */
    public function getDiscordAccessToken(): ?AccessToken
    {
        return $this->discordAccount?->getAccessToken();
    }

    /**
     * Get the user's Discord guilds.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function getDiscordGuilds(bool $withCounts = false): Collection
    {
        return $this->requireDiscordAccount()->getGuilds($withCounts);
    }

    /**
     * Get the user's guild member object for the given guild.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function getDiscordGuildMember(string $guildId): GuildMember
    {
        return $this->requireDiscordAccount()->getGuildMember($guildId);
    }

    /**
     * Add the user to the given guild.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function joinDiscordGuild(string $guildId, array $options = []): GuildMember
    {
        return $this->requireDiscordAccount()->joinGuild($guildId, $options);
    }

    /**
     * Get the user's Discord connections.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function getDiscordConnections(): Collection
    {
        return $this->requireDiscordAccount()->getConnections();
    }

    /**
     * Revoke the access token and unlink the user's Discord account.
     *
     * @throws RequestException
     */
    public function unlinkDiscordAccount(): bool
    {
        return (bool) $this->discordAccount?->revoke();
    }

    /**
     * Get the user's Discord account or fail.
     *
     * @throws Exception
     */
    protected function requireDiscordAccount(): DiscordAccount
    {
        $account = $this->discordAccount;

        if (!$account) {
            throw new Exception('The user does not have a Discord account linked.');
        }

        return $account;
    }
}
