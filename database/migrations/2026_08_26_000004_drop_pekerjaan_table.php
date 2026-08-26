<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `pekerjaan` adalah penyimpanan lama sebelum ada modul Project/Task.
 * Seluruh datanya sudah pindah ke `projects` dan `tasks`, dan tidak ada lagi
 * kode yang membacanya, jadi tabelnya dilepas supaya tidak menjadi sumber data
 * kedua yang menyesatkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pekerjaan');
    }

    public function down(): void
    {
        Schema::create('pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('division')->nullable();
            $table->string('nama_projek');
            $table->text('pekerjaan');
            $table->string('status')->default('on going');
            $table->string('kategori')->default('Medium');
            $table->timestamps();
        });
    }
};
