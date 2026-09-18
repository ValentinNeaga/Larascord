<?php

namespace Jakyeru\Larascord\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Jakyeru\Larascord\Models\DiscordAccount;

class DiscordAccountLinked
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public DiscordAccount $account, public Authenticatable $user)
    {
        //
    }
}
