<?php

namespace Jakyeru\Larascord\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Jakyeru\Larascord\Models\DiscordAccount;

class DiscordAccountUnlinked
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public DiscordAccount $account)
    {
        //
    }
}
