<?php

namespace Jakyeru\Larascord\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jakyeru\Larascord\Traits\InteractsWithDiscord;

class User extends Authenticatable
{
    use InteractsWithDiscord;

    protected $table = 'users';

    protected $guarded = [];
}
