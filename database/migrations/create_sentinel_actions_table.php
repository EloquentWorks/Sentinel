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
        // Create the 'sentinel_actions' table with the specified columns and relationships.
        Schema::create('sentinel_actions', function (Blueprint $table) {
            // Create an auto-incrementing primary key for the actions table.
            $table->id();
        
            // Store the ID of the user or entity that is the target of the action, as well as the ID of the user or entity that performed the action.
            $table->nullableMorphs('actor');
            $table->morphs('target');
        
            // Store the type of action performed, which can be used to categorize or describe the action.
            $table->string('type');
            $table->text('reason')->nullable();
        
            // Store additional metadata related to the action in JSON format.
            $table->json('metadata')->nullable();
        
            // Add timestamps for when the action was created and last updated.
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
        // Drop the 'sentinel_actions' table if it exists.
        Schema::dropIfExists('sentinel_actions');
    }
}