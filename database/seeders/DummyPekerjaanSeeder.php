<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Pekerjaan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyPekerjaanSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::where('name', 'user')->firstOrFail();

        $staff = [
            ['name' => 'Andi Pratama', 'email' => 'andi.pratama@office.test', 'department' => 'ai', 'project' => 'Deteksi Fraud Bank Mandiri', 'work' => 'Menyempurnakan model klasifikasi fraud menggunakan Random Forest.', 'category' => 'High'],
            ['name' => 'Siti Rahma', 'email' => 'siti.rahma@office.test', 'department' => 'ai', 'project' => 'Segmentasi Nasabah Bank DKI', 'work' => 'Menyiapkan fitur transaksi untuk model clustering nasabah.', 'category' => 'Medium'],
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@office.test', 'department' => 'ai', 'project' => 'Anotasi Gambar Mayora', 'work' => 'Melakukan anotasi bounding box pada objek manusia.', 'category' => 'High'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi.lestari@office.test', 'department' => 'platform', 'project' => 'Migrasi Infrastruktur Cloud', 'work' => 'Menyiapkan konfigurasi deployment untuk lingkungan staging.', 'category' => 'Medium'],
            ['name' => 'Eko Wijaya', 'email' => 'eko.wijaya@office.test', 'department' => 'platform', 'project' => 'Monitoring Layanan Internal', 'work' => 'Membuat dashboard monitoring dan aturan notifikasi layanan.', 'category' => 'Low'],
            ['name' => 'Fajar Hidayat', 'email' => 'fajar.hidayat@office.test', 'department' => 'platform', 'project' => 'Otomatisasi CI/CD', 'work' => 'Mengembangkan pipeline build dan deployment otomatis.', 'category' => 'High'],
            ['name' => 'Gita Permata', 'email' => 'gita.permata@office.test', 'department' => 'ba', 'project' => 'Analisis Kebutuhan Nasabah', 'work' => 'Mengumpulkan dan mendokumentasikan kebutuhan dari stakeholder.', 'category' => 'Medium'],
            ['name' => 'Hendra Saputra', 'email' => 'hendra.saputra@office.test', 'department' => 'ba', 'project' => 'Dashboard Kinerja Cabang', 'work' => 'Menganalisis indikator kinerja dan menyusun kebutuhan dashboard.', 'category' => 'Low'],
            ['name' => 'Intan Maharani', 'email' => 'intan.maharani@office.test', 'department' => 'td', 'project' => 'Implementasi Sistem Pembayaran', 'work' => 'Mengatur rencana sprint dan memantau progres implementasi.', 'category' => 'Highest'],
            ['name' => 'Joko Kurniawan', 'email' => 'joko.kurniawan@office.test', 'department' => 'td', 'project' => 'Peningkatan Aplikasi Mobile', 'work' => 'Mengkoordinasikan pengujian fitur dan penyelesaian temuan QA.', 'category' => 'High'],
        ];

        foreach ($staff as $person) {
            $department = Department::where('code', $person['department'])->firstOrFail();

            $user = User::updateOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $userRole->id,
                    'department_id' => $department->id,
                    'phone' => null,
                    'bio' => 'Dummy staff untuk data pekerjaan.',
                    'is_active' => true,
                ]
            );

            Pekerjaan::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'nama_projek' => $person['project'],
                ],
                [
                    'name' => $user->name,
                    'division' => $department->name,
                    'pekerjaan' => $person['work'],
                    'status' => 'on going',
                    'kategori' => $person['category'],
                ]
            );
        }
    }
}
