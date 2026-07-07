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
        // Create the 'sentinel_reports' table with the specified columns and relationships.
        Schema::create('sentinel_reports', function (Blueprint $table) {
            // Create an auto-incrementing primary key for the reports table.
            $table->id();
        
            // Store the ID of the user or entity that submitted the report, as well as the ID of the reported entity.
            $table->morphs('reporter');
            $table->morphs('reportable');
        
            // Store the reason for the report, which can be a predefined category or a custom reason.
            $table->string('reason');
            $table->text('description')->nullable();
        
            // Store the status of the report, which can be 'pending', 'in_progress', or 'resolved'.
            $table->string('status')->default('pending');
        
            // Store the ID of the user or entity assigned to handle the report, if applicable.
            $table->nullableMorphs('assigned_to');
            $table->nullableMorphs('resolved_by');
        
            // Store the timestamp when the report was resolved, if applicable.
            $table->timestamp('resolved_at')->nullable();
            
            // Store additional metadata related to the report in JSON format.
            $table->json('metadata')->nullable();
        
            // Add timestamps for when the report was created and last updated.
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
        // Drop the 'sentinel_reports' table if it exists.
        Schema::dropIfExists('sentinel_reports');
    }
}