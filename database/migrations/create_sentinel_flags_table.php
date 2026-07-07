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
        // Create the 'sentinel_flags' table with the specified columns and relationships.
        Schema::create('sentinel_flags', function (Blueprint $table) {
            // Create an auto-incrementing primary key for the flags table.
            $table->id();
        
            // Store the ID of the user or entity that is flagged, as well as the ID of the user or entity that issued the flag.
            $table->morphs('flaggable');
            $table->nullableMorphs('actor');
        
            // Store the key and value of the flag, which can be used to categorize or describe the flag.
            $table->string('key');
            $table->string('value')->nullable();
        
            // Store the timestamp when the flag expires, if applicable.
            $table->timestamp('expires_at')->nullable();
        
            // Store additional metadata related to the flag in JSON format.
            $table->json('metadata')->nullable();
        
            // Add timestamps for when the flag was created and last updated.
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
        // Drop the 'sentinel_flags' table if it exists.
        Schema::dropIfExists('sentinel_flags');
    }
}