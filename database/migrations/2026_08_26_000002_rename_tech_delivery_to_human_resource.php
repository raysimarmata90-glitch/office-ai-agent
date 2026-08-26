<?php

use App\Models\Department;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Departemen "Tech Delivery" berganti nama menjadi "Human Resource",
 * lengkap dengan jabatan-jabatan yang bernaung di bawahnya.
 */
return new class extends Migration
{
    /** Jabatan Human Resource: [name, display_name, description]. */
    private const JABATAN = [
        ['hr-manager', 'HR Manager', 'Memimpin fungsi sumber daya manusia, dari perencanaan tenaga kerja sampai kebijakan ketenagakerjaan.'],
        ['talent-acquisition', 'Talent Acquisition', 'Mencari, menyaring, dan merekrut kandidat yang sesuai dengan kebutuhan tiap tim.'],
        ['people-operations', 'People Operations', 'Menjalankan operasional harian kepegawaian: onboarding, administrasi, dan pengalaman kerja karyawan.'],
        ['learning-development', 'Learning & Development', 'Menyusun program pelatihan dan pengembangan kompetensi karyawan.'],
        ['payroll-benefit', 'Payroll & Benefit', 'Mengelola penggajian, tunjangan, serta kepatuhan administrasinya.'],
    ];

    public function up(): void
    {
        $dep = Department::where('code', 'td')->orWhere('name', 'Tech Delivery')->first();

        if (! $dep) {
            $dep = Department::firstOrCreate(
                ['code' => 'hr'],
                ['name' => 'Human Resource', 'color' => '#0e7490']
            );
        } else {
            $dep->update([
                'name' => 'Human Resource',
                'code' => 'hr',
                'description' => 'Pengelolaan sumber daya manusia dan pengalaman kerja karyawan.',
                'color' => '#0e7490',
            ]);
        }

        foreach (self::JABATAN as [$name, $displayName, $description]) {
            Role::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'description' => $description,
                    'department_id' => $dep->id,
                ]
            );
        }
    }

    public function down(): void
    {
        Role::whereIn('name', array_column(self::JABATAN, 0))->delete();

        Department::where('code', 'hr')->update([
            'name' => 'Tech Delivery',
            'code' => 'td',
        ]);
    }
};
