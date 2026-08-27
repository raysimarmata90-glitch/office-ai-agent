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
        Schema::table('messages', function (Blueprint $table) {
            // Tambah kolom untuk menyimpan entities terstruktur
            $table->json('entities')->nullable()->after('metadata');
            $table->string('intent', 100)->nullable()->after('entities');
            $table->decimal('intent_confidence', 3, 2)->nullable()->after('intent');
        });
        
        // Tambah tabel untuk menyimpan extracted work activities
        Schema::create('work_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('project_name');
            
            // Objektif as-is
            $table->text('objective')->nullable();
            $table->json('objective_parsed')->nullable(); // {what, why, scope}
            
            // Harapan/Expectation
            $table->text('expectation')->nullable();
            $table->boolean('expectation_from_options')->default(false);
            
            // Deliverable/Hasil Kerja
            $table->text('deliverable')->nullable();
            $table->boolean('deliverable_from_options')->default(false);
            $table->json('deliverable_parsed')->nullable(); // {type, description, format}
            
            // Current Task
            $table->text('current_task')->nullable();
            $table->boolean('task_from_planning')->default(false);
            
            // Progress/Detail
            $table->text('progress_detail')->nullable();
            $table->integer('progress_percentage')->nullable();
            $table->boolean('is_complete')->default(false);
            
            // Estimation
            $table->text('estimation_text')->nullable();
            $table->json('estimation_duration')->nullable(); // {value, unit}
            
            // Priority
            $table->string('priority_level', 50)->nullable(); // Tinggi, Sedang, Rendah
            
            // Metadata
            $table->json('raw_answers')->nullable(); // Simpan semua jawaban mentah untuk audit
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['project_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['entities', 'intent', 'intent_confidence']);
        });
        
        Schema::dropIfExists('work_activities');
    }
};
