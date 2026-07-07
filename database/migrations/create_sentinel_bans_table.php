<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void Returns nothing.
     */
    public function up(): void
    {
        // Create the 'sentinel_bans' table with the specified columns and relationships.
        Schema::create('sentinel_bans', function (Blueprint $table) {
            // Create an auto-incrementing primary key for the bans table.
            $table->id();
        
            // Store the ID of the user or entity that is banned, as well as the ID of the user or entity that issued the ban.
            $table->morphs('bannable');
            $table->nullableMorphs('actor');
        
            // Store the scope of the ban, which can be 'global', 'local', or a custom scope.
            $table->string('scope')->default('global');
            $table->text('reason')->nullable();
        
            // Store the timestamps for when the ban starts, expires, and is revoked.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
        
            // Store additional metadata related to the ban in JSON format.
            $table->json('metadata')->nullable();
        
            // Add timestamps for when the ban was created and last updated.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void Returns nothing.
     */
    public function down(): void
    {
        // Drop the 'sentinel_bans' table if it exists.
        Schema::dropIfExists('sentinel_bans');
    }
}