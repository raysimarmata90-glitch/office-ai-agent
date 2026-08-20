<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->integer('step_number'); // urutan pertanyaan
            $table->text('question_text'); // template pertanyaan dari AI
            $table->text('system_prompt')->nullable(); // prompt untuk AI
            $table->json('expected_format')->nullable(); // format jawaban yang diharapkan
            $table->boolean('is_required')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_templates');
    }
};
