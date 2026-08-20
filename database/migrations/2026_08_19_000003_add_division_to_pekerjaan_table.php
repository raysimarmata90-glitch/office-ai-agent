<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pekerjaan', 'division')) {
            Schema::table('pekerjaan', function (Blueprint $table) {
                $table->string('division')->nullable()->after('name');
            });
        }

        DB::table('pekerjaan')
            ->join('users', 'pekerjaan.user_id', '=', 'users.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->update([
                'pekerjaan.division' => DB::raw('departments.name'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('pekerjaan', 'division')) {
            Schema::table('pekerjaan', function (Blueprint $table) {
                $table->dropColumn('division');
            });
        }
    }
};
