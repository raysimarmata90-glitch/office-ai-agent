<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->integer('no')->nullable();
            $table->string('client_or_rd'); // Client or R&D name
            $table->string('kd_id')->nullable(); // KD-ID
            $table->text('key_deliverables')->nullable();
            $table->enum('status', ['Ongoing', 'Proposal', 'Kick-Off', 'Closed', 'Pending'])->default('Ongoing');
            $table->string('pic')->nullable(); // Person in Charge
            $table->text('progress_update')->nullable();
            $table->text('next_steps')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_blocked')->default(false); // Auto-block jika melewati deadline
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
