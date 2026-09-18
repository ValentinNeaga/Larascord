<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return config('larascord.database.connection');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('larascord.database.accounts_table', 'larascord_accounts'), function (Blueprint $table) {
            $table->id();

            $userModel = config('larascord.users.model');

            if (config('larascord.database.foreign_keys', true) && class_exists($userModel)) {
                $table->foreignIdFor($userModel)
                    ->nullable()
                    ->constrained()
                    ->cascadeOnDelete();
            } else {
                $table->string('user_id')->nullable()->index();
            }

            $table->string('discord_id')->unique();
            $table->string('username');
            $table->string('global_name')->nullable();
            $table->string('discriminator')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('avatar')->nullable();
            $table->boolean('verified')->nullable();
            $table->string('banner')->nullable();
            $table->string('banner_color')->nullable();
            $table->string('accent_color')->nullable();
            $table->string('locale')->nullable();
            $table->boolean('mfa_enabled')->nullable();
            $table->unsignedInteger('premium_type')->nullable();
            $table->unsignedBigInteger('public_flags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larascord.database.accounts_table', 'larascord_accounts'));
    }
};
