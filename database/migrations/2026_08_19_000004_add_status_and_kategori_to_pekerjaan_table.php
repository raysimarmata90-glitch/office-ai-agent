<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->enum('status', ['on going', 'completed'])
                ->default('on going')
                ->after('pekerjaan');
            $table->enum('kategori', ['Highest', 'High', 'Medium', 'Low', 'Lowest'])
                ->default('Medium')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->dropColumn(['status', 'kategori']);
        });
    }
};
