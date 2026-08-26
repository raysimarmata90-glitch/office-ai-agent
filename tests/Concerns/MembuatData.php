<?php

namespace Tests\Concerns;

use App\Models\Department;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;

/**
 * Pembuat data secukupnya untuk feature test. Sengaja tidak memakai factory
 * penuh supaya isinya terbaca langsung dari test yang memakainya.
 */
trait MembuatData
{
    protected function role(string $nama): Role
    {
        return Role::firstOrCreate(
            ['name' => $nama],
            ['display_name' => ucfirst($nama)]
        );
    }

    protected function departemen(string $nama = 'Platform', string $kode = 'plat'): Department
    {
        return Department::firstOrCreate(['code' => $kode], ['name' => $nama]);
    }

    protected function buatUser(array $atribut = [], string $role = 'user'): User
    {
        return User::factory()->create(array_merge([
            'role_id' => $this->role($role)->id,
        ], $atribut));
    }

    protected function buatAdmin(array $atribut = []): User
    {
        return $this->buatUser($atribut, 'admin');
    }

    protected function buatProyek(array $atribut = []): Project
    {
        return Project::create(array_merge([
            'nama' => 'Proyek Uji',
            'mulai' => now()->subMonth()->toDateString(),
            'selesai' => now()->addMonth()->toDateString(),
        ], $atribut));
    }

    protected function buatTugas(array $atribut = []): Task
    {
        $pemilik = $atribut['user_id'] ?? $this->buatUser()->id;
        $proyek = $atribut['project_id'] ?? $this->buatProyek()->id;

        return Task::create(array_merge([
            'judul' => 'Tugas Uji',
            'project_id' => $proyek,
            'user_id' => $pemilik,
            'created_by' => $pemilik,
            'status' => Task::STATUS_TO_DO,
            'prioritas' => 'Sedang',
            'mulai' => now()->toDateString(),
            'selesai' => now()->addWeek()->toDateString(),
        ], $atribut));
    }

    /** Isian minimal yang lolos validasi form tugas. */
    protected function isianTugas(array $ganti = []): array
    {
        return array_merge([
            'judul' => 'Tugas dari form',
            'project_id' => $this->buatProyek()->id,
            'status' => Task::STATUS_TO_DO,
            'prioritas' => 'Sedang',
            'mulai' => now()->toDateString(),
            'selesai' => now()->addWeek()->toDateString(),
            'user_id' => $this->buatUser()->id,
        ], $ganti);
    }
}
