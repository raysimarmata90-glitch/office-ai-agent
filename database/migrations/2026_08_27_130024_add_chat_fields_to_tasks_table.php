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
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('objektif')->nullable()->after('deskripsi');
            $table->text('harapan')->nullable()->after('objektif');
            $table->text('deliverable')->nullable()->after('harapan');
            $table->text('detail')->nullable()->after('deliverable');
            $table->text('progress_text')->nullable()->after('detail');
            $table->text('estimasi')->nullable()->after('progress_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['objektif', 'harapan', 'deliverable', 'detail', 'progress_text', 'estimasi']);
        });
    }
};
