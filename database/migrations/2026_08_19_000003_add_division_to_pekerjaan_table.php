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

        // Update existing records with department names (SQLite compatible)
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'name')) {
            // Get all pekerjaan with their department names
            $pekerjaans = DB::table('pekerjaan')
                ->join('users', 'pekerjaan.user_id', '=', 'users.id')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
                ->whereNotNull('departments.name')
                ->select('pekerjaan.id', 'departments.name as dept_name')
                ->get();
            
            // Update each record individually
            foreach ($pekerjaans as $pekerjaan) {
                DB::table('pekerjaan')
                    ->where('id', $pekerjaan->id)
                    ->update(['division' => $pekerjaan->dept_name]);
            }
        }
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
