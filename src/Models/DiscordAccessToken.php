<?php

namespace Jakyeru\Larascord\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordAccessToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'discord_account_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_in',
        'expires_at',
        'scope',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var string[]
     */
    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return config('larascord.database.access_tokens_table', 'larascord_access_tokens');
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
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_in' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the Discord account the access token belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(DiscordAccount::class, 'discord_account_id');
    }
}
