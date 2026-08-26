<?php

use App\Models\Department;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Departemen "Human Resource" berganti menjadi "Tim Operasional & Legal",
 * dengan susunan jabatan yang lebih ringkas.
 */
return new class extends Migration
{
    /** Jabatan baru: [name, display_name, description]. */
    private const JABATAN = [
        ['legal-counsel', 'Legal Counsel / Corporate Legal', 'Meninjau dan menyusun kontrak kerja sama, memastikan kepatuhan hukum perusahaan (peraturan pemerintah, ketenagakerjaan), serta mengurus perizinan dan perlindungan kekayaan intelektual (HAKI).'],
        ['people-operations', 'HR / People Operations', 'Mengelola rekrutmen, administrasi karyawan, pengembangan budaya perusahaan, dan evaluasi kinerja.'],
        ['finance-accounting', 'Finance & Accounting', 'Mengelola arus kas, pembukuan keuangan, penggajian, dan pelaporan pajak perusahaan.'],
    ];

    /** Jabatan lama yang tidak dipakai lagi. */
    private const DIBUANG = ['hr-manager', 'talent-acquisition', 'learning-development', 'payroll-benefit'];

    public function up(): void
    {
        $dep = Department::where('code', 'hr')->orWhere('name', 'Human Resource')->first();

        if (! $dep) {
            $dep = Department::firstOrCreate(
                ['code' => 'ops-legal'],
                ['name' => 'Tim Operasional & Legal', 'color' => '#0e7490']
            );
        } else {
            $dep->update([
                'name' => 'Tim Operasional & Legal',
                'code' => 'ops-legal',
                'description' => 'Operasional perusahaan, kepegawaian, keuangan, dan kepatuhan hukum.',
            ]);
        }

        // Pengguna yang terlanjur memakai jabatan lama dipindahkan ke People Operations.
        $ganti = Role::updateOrCreate(
            ['name' => 'people-operations'],
            [
                'display_name' => 'HR / People Operations',
                'description' => self::JABATAN[1][2],
                'department_id' => $dep->id,
            ]
        );

        \App\Models\User::whereIn('role_id', Role::whereIn('name', self::DIBUANG)->pluck('id'))
            ->update(['role_id' => $ganti->id]);

        Role::whereIn('name', self::DIBUANG)->delete();

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
        Role::whereIn('name', ['legal-counsel', 'finance-accounting'])->delete();

        Department::where('code', 'ops-legal')->update([
            'name' => 'Human Resource',
            'code' => 'hr',
        ]);
    }
};
