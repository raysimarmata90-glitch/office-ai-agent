<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProjectTaskSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::where('name', 'user')->first();
        $departments = Department::all();

        $anggota = [
            ['Sari Wulandari', 'sari@inaai.id', 'platform'],
            ['Andi Pratama', 'andi@inaai.id', 'ai'],
            ['Rina Kusuma', 'rina@inaai.id', 'ba'],
            ['Dimas Prakoso', 'dimas@inaai.id', 'td'],
            ['Maya Anggraini', 'maya@inaai.id', 'platform'],
            ['Bagus Setiawan', 'bagus@inaai.id', 'ai'],
            ['Nadia Safitri', 'nadia@inaai.id', 'ba'],
            ['Reza Fadillah', 'reza@inaai.id', 'td'],
        ];

        $users = collect();
        foreach ($anggota as [$nama, $email, $kodeDept]) {
            $dept = $departments->firstWhere('code', $kodeDept) ?? $departments->first();
            $users->push(User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $nama,
                    'password' => Hash::make('password123'),
                    'role_id' => $userRole->id,
                    'department_id' => $dept->id,
                    'is_active' => true,
                ]
            ));
        }

        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->first();

        // progres: [nama, total tugas, persen selesai, berisiko]
        // Warna tidak diisi di sini — model Project yang membagikannya dari palet
        // supaya tiap proyek pasti berbeda.
        $blueprint = [
            ['Migrasi ERP', 12, 100, false],
            ['Aplikasi Mobile v2', 10, 90, true],
            ['Integrasi Pembayaran', 10, 80, true],
            ['Portal Karyawan', 8, 50, false],
            ['Data Warehouse', 9, 33, false],
            ['Website Korporat', 6, 17, false],
        ];

        $judulPool = [
            'Analisis kebutuhan', 'Desain arsitektur', 'Setup environment', 'Implementasi modul inti',
            'Integrasi API', 'Unit testing', 'Perbaikan bug', 'Optimasi query', 'Dokumentasi teknis',
            'User acceptance test', 'Deployment staging', 'Review keamanan', 'Migrasi data',
            'Penyusunan laporan', 'Konfigurasi CI/CD',
        ];

        $prioritas = ['Tinggi', 'Sedang', 'Rendah'];
        $mulaiProyek = now()->startOfMonth()->subMonths(1);

        foreach ($blueprint as $i => [$nama, $total, $persen, $berisiko]) {
            $project = Project::create([
                'nama' => $nama,
                'deskripsi' => 'Inisiatif ' . $nama . ' untuk mendukung target operasional perusahaan.',
                'berisiko' => $berisiko,
                'mulai' => (clone $mulaiProyek)->addWeeks($i * 2),
                'selesai' => (clone $mulaiProyek)->addWeeks($i * 2 + 14),
                'created_by' => $admin?->id,
            ]);

            $selesaiCount = (int) round($total * $persen / 100);
            $sisa = $total - $selesaiCount;

            $inProgress = (int) ceil($sisa * 0.5);
            $review = (int) ceil(($sisa - $inProgress) * 0.5);
            $blocked = $sisa - $inProgress - $review > 0 ? 1 : 0;
            $toDo = max(0, $sisa - $inProgress - $review - $blocked);

            $statusUrutan = array_merge(
                array_fill(0, $selesaiCount, Task::STATUS_DONE),
                array_fill(0, $inProgress, Task::STATUS_IN_PROGRESS),
                array_fill(0, $review, Task::STATUS_REVIEW),
                array_fill(0, $blocked, Task::STATUS_BLOCKED),
                array_fill(0, $toDo, Task::STATUS_TO_DO),
            );

            foreach ($statusUrutan as $idx => $status) {
                $pemilik = $users[($i * 3 + $idx) % $users->count()];
                $reviewer = $users[($i * 3 + $idx + 4) % $users->count()];
                $mulai = (clone $project->mulai)->addDays($idx * 4);
                // Tanggal dibuat selalu di masa lalu: tugas yang jadwalnya masih
                // ke depan tetap tercatat dibuat sebelum hari ini.
                $dibuat = min((clone $mulai)->subDays(2), now()->subHours($i * 24 + $idx + 1));

                Task::create([
                    'judul' => $judulPool[$idx % count($judulPool)] . ' — ' . $nama,
                    'deskripsi' => 'Pengerjaan ' . mb_strtolower($judulPool[$idx % count($judulPool)]) . ' pada proyek ' . $nama . '.',
                    'project_id' => $project->id,
                    'user_id' => $pemilik->id,
                    'reviewer_id' => $reviewer->id === $pemilik->id ? null : $reviewer->id,
                    'created_by' => $admin?->id,
                    'status' => $status,
                    'prioritas' => $prioritas[$idx % 3],
                    'mulai' => $mulai,
                    'selesai' => (clone $mulai)->addDays(7 + ($idx % 3) * 5),
                    'selesai_pada' => $status === Task::STATUS_DONE ? (clone $mulai)->addDays(6) : null,
                    'created_at' => $dibuat,
                    'updated_at' => (clone $dibuat)->addHours(6),
                ]);
            }
        }
    }
}
