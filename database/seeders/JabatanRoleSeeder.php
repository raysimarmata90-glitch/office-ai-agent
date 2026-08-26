<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Daftar jabatan dari Docs/Mockup/list-role.md, dikelompokkan per departemen
 * sesuai judul bagian pada dokumen tersebut.
 *
 * Role "admin" dan "user" tetap dipakai untuk hak akses (lihat User::isAdmin())
 * dan sengaja tidak terikat departemen mana pun.
 */
class JabatanRoleSeeder extends Seeder
{
    public function run(): void
    {
        $dep = [];
        foreach ([
            'exec' => ['Eksekutif', '#1e2130'],
            'platform' => ['Platform', '#2c5cc5'],
            'ai' => ['AI', '#7e22ce'],
            'product' => ['Produk & Desain', '#047857'],
            'growth' => ['Pemasaran & Bisnis', '#b45309'],
        ] as $code => [$nama, $warna]) {
            $dep[$code] = Department::firstOrCreate(
                ['code' => $code],
                ['name' => $nama, 'color' => $warna, 'description' => 'Kelompok jabatan ' . $nama]
            )->id;
        }

        $jabatan = [
            // Level Eksekutif (C-Level)
            ['ceo', 'exec', 'Chief Executive Officer (CEO)', 'Menentukan visi utama, merumuskan strategi bisnis jangka panjang, dan menjadi wajah perusahaan di depan investor.'],
            ['cto', 'exec', 'Chief Technology Officer (CTO)', 'Mengarahkan visi teknologi, menentukan arsitektur sistem dasar, dan memastikan infrastruktur mampu diskalakan.'],
            ['coo', 'exec', 'Chief Operating Officer (COO)', 'Mengawasi operasional harian internal dan memastikan seluruh divisi berjalan selaras dengan tujuan bisnis.'],

            // Tim Rekayasa Teknologi — Departemen Platform
            ['tech-lead', 'platform', 'Technical Leader', 'Memandu tim secara teknis, mengambil keputusan terkait standar kode dan arsitektur, serta menjembatani komunikasi antara engineer dan manajemen produk.'],
            ['backend-engineer', 'platform', 'Backend Engineer', 'Merancang logika inti dari platform, mengelola struktur basis data, serta memastikan kinerja dan keamanan API agar sistem beroperasi dengan tangguh.'],
            ['fullstack-engineer', 'platform', 'Full Stack Engineer', 'Menangani sisi antarmuka sekaligus logika server, dari tampilan aplikasi hingga API dan basis datanya.'],
            ['frontend-engineer', 'platform', 'Frontend Engineer', 'Menerjemahkan desain visual menjadi antarmuka aplikasi yang interaktif dan responsif di sisi pengguna.'],
            ['devops-engineer', 'platform', 'DevOps / Cloud Engineer', 'Mengonfigurasi dan mengelola infrastruktur cloud, serta mengotomatisasi proses penyebaran kode aplikasi.'],

            // Tim Rekayasa Teknologi — Departemen AI
            ['ai-ml-engineer', 'ai', 'AI/ML Engineer', 'Merancang, melatih, dan mengimplementasikan model machine learning atau kecerdasan buatan agar dapat digunakan di dalam produk perusahaan.'],
            ['data-scientist', 'ai', 'Data Scientist', 'Melakukan pemodelan statistik, menganalisis pola data dalam skala besar, dan merancang algoritma prediktif.'],
            ['data-engineer', 'ai', 'Data Engineer', 'Membangun dan memelihara arsitektur pipeline data yang kuat untuk mendukung pemrosesan model AI dan kebutuhan analitik.'],
            ['ai-researcher', 'ai', 'AI Researcher / Prompt Engineer', 'Mengikuti perkembangan riset AI, bereksperimen dengan teknologi baru (seperti LLM), dan mengoptimalkan interaksi model AI agar memberikan output yang relevan.'],

            // Tim Produk & Desain
            ['product-manager', 'product', 'Product Manager (PM)', 'Bertanggung jawab atas peta jalan produk dan menjembatani kebutuhan bisnis dengan proses eksekusi teknis.'],
            ['business-analyst', 'product', 'Business Analyst', 'Menganalisis kebutuhan bisnis dan pasar, mengevaluasi proses yang ada, serta menerjemahkannya ke dalam spesifikasi fungsional agar pengembangan produk selaras dengan tujuan perusahaan.'],
            ['ui-ux-designer', 'product', 'UI/UX Designer', 'Merancang alur pengalaman pengguna dan tata letak visual aplikasi agar mudah dan nyaman digunakan.'],
            ['qa', 'product', 'Quality Assurance (QA)', 'Melakukan pengujian sistem secara menyeluruh untuk memastikan tidak ada celah teknis sebelum produk dirilis.'],

            // Tim Pemasaran & Bisnis
            ['growth-hacker', 'growth', 'Growth Hacker / Performance Marketer', 'Berfokus pada eksperimen pemasaran digital untuk mendapatkan dan mempertahankan pengguna secara cepat.'],
            ['data-analyst', 'growth', 'Data Analyst', 'Mengolah metrik dan data bisnis menjadi wawasan operasional untuk memandu strategi pengembangan ke depan.'],
            ['business-development', 'growth', 'Business Development', 'Mengidentifikasi prospek pasar baru, membangun kerja sama strategis, dan mendorong pertumbuhan pendapatan.'],
        ];

        foreach ($jabatan as [$name, $kelompok, $displayName, $description]) {
            Role::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $displayName,
                    'description' => $description,
                    'department_id' => $dep[$kelompok],
                ]
            );
        }
    }
}
