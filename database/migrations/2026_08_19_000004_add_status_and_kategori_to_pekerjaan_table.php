<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pekerjaan', 'status')) {
            Schema::table('pekerjaan', function (Blueprint $table) {
                $table->enum('status', ['on going', 'completed'])
                    ->default('on going')
                    ->after('pekerjaan');
            });
        }
        
        if (!Schema::hasColumn('pekerjaan', 'kategori')) {
            Schema::table('pekerjaan', function (Blueprint $table) {
                $table->enum('kategori', ['Highest', 'High', 'Medium', 'Low', 'Lowest'])
                    ->default('Medium')
                    ->after(Schema::hasColumn('pekerjaan', 'status') ? 'status' : 'pekerjaan');
            });
        }
    }

    public function down(): void
    {
        Schema::table('pekerjaan', function (Blueprint $table) {
            if (Schema::hasColumn('pekerjaan', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('pekerjaan', 'kategori')) {
                $table->dropColumn('kategori');
            }
        });
    }
};
