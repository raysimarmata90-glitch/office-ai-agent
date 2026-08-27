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
        Schema::create('project_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('code')->nullable(); // e.g., "1.1", "2.1", "2.2.1"
            $table->text('deliverable_name');
            $table->enum('category', ['COMMERCIAL', 'TECH', 'EKSPANSI', 'LEGAL', 'OTHER'])->default('OTHER');
            $table->string('pic')->nullable();
            $table->text('progress_update')->nullable();
            $table->text('next_steps')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('completion_percentage')->default(0); // 0-100
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_deliverables');
    }
};
