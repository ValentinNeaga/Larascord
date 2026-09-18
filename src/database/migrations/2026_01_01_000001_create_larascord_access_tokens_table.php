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
        Schema::create(config('larascord.database.access_tokens_table', 'larascord_access_tokens'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('discord_account_id')
                ->unique()
                ->constrained(config('larascord.database.accounts_table', 'larascord_accounts'))
                ->cascadeOnDelete();
            $table->longText('access_token');
            $table->longText('refresh_token');
            $table->string('token_type');
            $table->unsignedInteger('expires_in');
            $table->timestamp('expires_at');
            $table->string('scope');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('larascord.database.access_tokens_table', 'larascord_access_tokens'));
    }
};
