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
        // Create the 'sentinel_warnings' table with the specified columns and relationships.
        Schema::create('sentinel_warnings', function (Blueprint $table) {
            // Create an auto-incrementing primary key for the warnings table.
            $table->id();
        
            // Store the ID of the user or entity that is warned, as well as the ID of the user or entity that issued the warning.
            $table->morphs('warnable');
            $table->nullableMorphs('actor');
        
            // Store the severity level of the warning, which can be 'low', 'medium', or 'high'.
            $table->string('severity')->default('low');
            $table->text('reason');
        
            // Store the timestamp when the warning was acknowledged, if applicable.
            $table->timestamp('acknowledged_at')->nullable();
        
            // Store additional metadata related to the warning in JSON format.
            $table->json('metadata')->nullable();
        
            // Add timestamps for when the warning was created and last updated.
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
        // Drop the 'sentinel_warnings' table if it exists.
        Schema::dropIfExists('sentinel_warnings');
    }
}