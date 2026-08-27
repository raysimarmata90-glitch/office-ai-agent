<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('project_deliverable_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('current_step', [
                'select_client', 
                'select_deliverable', 
                'objective_as_is', 
                'timeline_validation', 
                'task_inquiry',
                'percentage_allocation',
                'completed'
            ])->default('select_client');
            $table->json('collected_data')->nullable(); // Store step-by-step data
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
