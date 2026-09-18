<?php

namespace Jakyeru\Larascord\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Jakyeru\Larascord\Types\User;

interface ResolvesUsers
{
    /**
     * Resolve the application user the given Discord user should be linked to.
     */
    public function resolve(User $discordUser): ?Authenticatable;
}
