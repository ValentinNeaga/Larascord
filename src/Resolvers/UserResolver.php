<?php

namespace Jakyeru\Larascord\Resolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Jakyeru\Larascord\Contracts\ResolvesUsers;
use Jakyeru\Larascord\Types\User;

class UserResolver implements ResolvesUsers
{
    /**
     * Resolve the application user the given Discord user should be linked to.
     */
    public function resolve(User $discordUser): ?Authenticatable
    {
        $existing = $this->findByEmail($discordUser);

        if ($existing) {
            return $existing;
        }

        if (!config('larascord.users.create_missing', true)) {
            return null;
        }

        return $this->create($discordUser);
    }

    /**
     * Find an existing user matching the Discord user's e-mail address.
     */
    protected function findByEmail(User $discordUser): ?Authenticatable
    {
        if (!config('larascord.users.link_by_email', true) || !$discordUser->email) {
            return null;
        }

        $model = $this->model();

        return $model::query()
            ->where(config('larascord.users.email_column', 'email'), $discordUser->email)
            ->first();
    }

    /**
     * Create a new user from the given Discord user.
     */
    protected function create(User $discordUser): Authenticatable
    {
        $model = $this->model();
        $user = new $model();

        $user->forceFill($this->attributes($discordUser, $user));
        $user->save();

        return $user;
    }

    /**
     * Build the attributes of the user that is about to be created.
     */
    protected function attributes(User $discordUser, $user): array
    {
        $attributes = [];

        foreach (config('larascord.users.attributes', []) as $column => $attribute) {
            $attributes[$column] = $discordUser->{$attribute} ?? null;
        }

        if (config('larascord.users.fill_random_password', true)
            && !array_key_exists('password', $attributes)
            && Schema::connection($user->getConnectionName())->hasColumn($user->getTable(), 'password')) {
            $attributes['password'] = Hash::make(Str::random(64));
        }

        return $attributes;
    }

    /**
     * Get the configured user model.
     */
    protected function model(): string
    {
        return config('larascord.users.model');
    }
}
