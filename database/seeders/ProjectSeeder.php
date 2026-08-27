<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\ProjectDeliverable;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data berdasarkan INA AI Project Status 24 Agustus 2026
        $projects = [
            [
                'no' => 1,
                'client_or_rd' => 'Telkomsel',
                'kd_id' => '1',
                'key_deliverables' => '1 Mn downloads of Sacred Octagon games',
                'status' => 'Ongoing',
                'pic' => 'Rizky',
                'progress_update' => 'Game tersedia di IOS dan Android',
                'next_steps' => 'Meeting dengan tim "Kunci" untuk integrasi dashboard dan data games (confirm benefit yang didapat dari INaAI)',
                'due_date' => null,
                'deliverables' => [
                    ['code' => '1.1', 'name' => 'Technical Deliverables', 'category' => 'TECH', 'pic' => 'April'],
                    ['code' => '1.2', 'name' => 'Games', 'category' => 'TECH', 'pic' => 'Rizky', 'progress' => '1. Game tersedia di IOS dan Android', 'next' => '1. Sosialisasi Game SO dan School Pintar "Kunci" oleh tim kunci 15-16 Juli'],
                    ['code' => '1.3', 'name' => 'Integration & Coordination w Tsel', 'category' => 'TECH', 'pic' => 'April'],
                    ['code' => '1.4', 'name' => 'Campaign with Telkomsel', 'category' => 'COMMERCIAL', 'pic' => 'Billy', 'progress' => 'Update dan detailkan Planning GTM SO'],
                    ['code' => '1.5', 'name' => 'Social Media campaigns', 'category' => 'EKSPANSI', 'pic' => 'Jensri', 'progress' => 'Plan to achieve 1 Mn followers in Instagram and Tiktok'],
                    ['code' => '1.6', 'name' => 'On the ground activation', 'category' => 'EKSPANSI', 'pic' => 'Rere', 'progress' => 'Plan to go to schools, including incentive for Tsel sales'],
                    ['code' => '1.7', 'name' => 'Video contents', 'category' => 'EKSPANSI', 'pic' => 'Jensri', 'progress' => 'More videos to support Social Media campaigns'],
                    ['code' => '1.8', 'name' => 'Legal PKS', 'category' => 'LEGAL', 'pic' => 'Billy'],
                    ['code' => '1.9', 'name' => 'PKS 2 parties (Telkomsel & INaAI)', 'category' => 'LEGAL', 'pic' => 'Meyvi (Vania)'],
                    ['code' => '1.10', 'name' => 'PKS 3 parties (Telkomsel & INaAI & SpaceXAI)', 'category' => 'LEGAL', 'pic' => 'Meyvi (Vania)'],
                    ['code' => '1.11', 'name' => '[TECHNICAL] - Pencacahan ke denominasi hari', 'category' => 'TECH', 'pic' => 'Arthur'],
                ]
            ],
            [
                'no' => 2,
                'client_or_rd' => 'BPJS',
                'kd_id' => '2',
                'key_deliverables' => 'AI Personal Doctor',
                'status' => 'Ongoing',
                'pic' => 'Arthur',
                'progress_update' => '1. Persiapan video testimoni dan edukasi 2. Pembuatan akun WA Bisnis 3. Pembuatan mobile app 4. Persiapan sosialisasi di Bali 7 Agustus 2026',
                'next_steps' => '1. Tanda tangan MoU dan PKS 2. Pembuatan HAKI 3. Meeting dengan SpaceXAI 3 Agustus 2026 terkait confabulation',
                'due_date' => null,
                'deliverables' => [
                    ['code' => '2.1', 'name' => '[LEGAL] Request kajian dari BPJS Kesehatan', 'category' => 'LEGAL', 'pic' => 'Arthur', 'progress' => 'Kajian sudah dikirim'],
                    ['code' => '2.2', 'name' => '[LEGAL] Mou PKS BPJS', 'category' => 'LEGAL', 'pic' => 'Arthur', 'progress' => 'Online meet dengan DIRPATUHAL BPJS Kes'],
                    ['code' => '2.3', 'name' => '[LEGAL] xAI Order Form & ECA', 'category' => 'LEGAL', 'pic' => 'Charlie', 'progress' => 'SpaceXAI sent us latest ECA'],
                    ['code' => '2.4', 'name' => '[LEGAL] HAKI', 'category' => 'LEGAL', 'pic' => 'Arthur (Vania)', 'next' => 'Persiapan dokumen yang dibutuhkan untuk HAKI'],
                    ['code' => '2.5', 'name' => '[LEGAL] Hiring SME, DPO, PSE Registration', 'category' => 'LEGAL', 'next' => '1. Menyelesaikan pendaftaran PSE (dependent on DPO) 2. DPO Hiring Medical SME'],
                    ['code' => '2.6', 'name' => '[EKSPANSI] Komunikasi & Sosialisasi', 'category' => 'EKSPANSI', 'pic' => 'Carell (Henny)', 'progress' => '1. Pembuatan akun IG 2. Persiapan video testimoni pertama'],
                    ['code' => '2.7', 'name' => '[TECH] Testing Hasil AI', 'category' => 'TECH', 'pic' => 'Charlie', 'next' => 'Meeting dengan SpaceXAI terkait confab di 3 Agustus 2026'],
                    ['code' => '2.8', 'name' => '[TECH] Deployment Mobile JKN ke Play Store', 'category' => 'TECH', 'pic' => 'Arthur', 'progress' => 'Done', 'is_completed' => true],
                    ['code' => '2.9', 'name' => '[TECH] Backup Plan', 'category' => 'TECH', 'pic' => 'Arthur', 'progress' => 'Done', 'is_completed' => true],
                    ['code' => '2.10', 'name' => '[TECH] Kirim response AI yang tidak sesuai ke GROK', 'category' => 'TECH', 'pic' => 'Charlie', 'progress' => 'Done', 'is_completed' => true],
                    ['code' => '2.11', 'name' => '[TECH] Testing aplikasi Mobile JKN + Tara AI', 'category' => 'TECH', 'pic' => 'Hafidh', 'progress' => '1. Testing aplikasi 2. Check feedback AI'],
                    ['code' => '2.12', 'name' => '[COMMERCIAL]', 'category' => 'COMMERCIAL', 'pic' => 'Carell', 'progress' => 'Pembuatan timeline development'],
                    ['code' => '2.13', 'name' => '[LEGAL] Dataroom', 'category' => 'LEGAL', 'pic' => 'Arthur', 'progress' => 'Upload PKS + diagram ke dataroom', 'is_completed' => true],
                    ['code' => '2.14', 'name' => '[EKSPANSI] Sosialisasi di Makassar', 'category' => 'EKSPANSI', 'pic' => 'Arthur', 'next' => 'Setup meeting dengan Amartha untuk sosialisasi ibu2 di Makassar'],
                ]
            ],
            [
                'no' => 3,
                'client_or_rd' => 'Inhealth',
                'kd_id' => '3',
                'key_deliverables' => '',
                'status' => 'Ongoing',
                'pic' => 'Arthur',
                'progress_update' => 'NDA sudah dikirim',
                'next_steps' => '1. Review NDA oleh Inhealth 2. Pembahasan technical (integrasi)',
                'due_date' => null,
                'deliverables' => []
            ],
            [
                'no' => 4,
                'client_or_rd' => 'Jasindo',
                'kd_id' => '4',
                'key_deliverables' => '',
                'status' => 'Ongoing',
                'pic' => 'Arthur',
                'progress_update' => 'Meeting Tue 4 Aug 2026',
                'next_steps' => null,
                'due_date' => Carbon::parse('2026-08-04'),
                'deliverables' => []
            ],
            [
                'no' => 5,
                'client_or_rd' => 'AKR',
                'kd_id' => '5',
                'key_deliverables' => 'Phase 2 Surabaya',
                'status' => 'Kick-Off',
                'pic' => 'Rizky',
                'progress_update' => 'Kick off',
                'next_steps' => 'Sprint Start Tue 21 July',
                'due_date' => Carbon::parse('2026-07-14'),
                'deliverables' => []
            ],
            [
                'no' => 6,
                'client_or_rd' => 'Mayora',
                'kd_id' => '6',
                'key_deliverables' => 'Supermarket AI and Commodity Price Models',
                'status' => 'Ongoing',
                'pic' => 'April',
                'progress_update' => '',
                'next_steps' => null,
                'due_date' => null,
                'deliverables' => [
                    ['code' => '6.1', 'name' => 'Testing Environment', 'category' => 'TECH', 'next' => 'Development Platform & AI Model Procurement Hardware'],
                    ['code' => '6.2', 'name' => 'Model development', 'category' => 'TECH', 'next' => 'Create initial Insight'],
                    ['code' => '6.3', 'name' => 'Business Process Side', 'category' => 'COMMERCIAL', 'pic' => 'Licia & Clara', 'progress' => 'Business Process Side Management'],
                    ['code' => '6.4', 'name' => 'Cocoa prices modeling', 'category' => 'TECH', 'pic' => 'Jensri'],
                ]
            ],
            [
                'no' => 7,
                'client_or_rd' => 'PEPI',
                'kd_id' => '7',
                'key_deliverables' => 'Sales Territory Intelligence for Cement Distribution',
                'status' => 'Ongoing',
                'pic' => 'Charlie',
                'progress_update' => '',
                'next_steps' => null,
                'due_date' => null,
                'deliverables' => [
                    ['code' => '7.1', 'name' => 'Maintenance', 'category' => 'TECH', 'pic' => 'Angelita', 'progress' => 'Monthly Report Maintenance'],
                    ['code' => '7.2', 'name' => 'ERP dan Survey', 'category' => 'TECH', 'pic' => 'Angel + Johannes'],
                    ['code' => '7.3', 'name' => 'Create new proposal AI SAKA', 'category' => 'COMMERCIAL', 'pic' => 'Angel + Johannes', 'progress' => 'Pending'],
                ]
            ],
            [
                'no' => 8,
                'client_or_rd' => 'Textile',
                'kd_id' => '8',
                'key_deliverables' => 'Retail textile Analytics',
                'status' => 'Ongoing',
                'pic' => 'Jensri',
                'progress_update' => 'BAST requests Pak Vandi\'s Signature',
                'next_steps' => null,
                'due_date' => null,
                'deliverables' => [
                    ['code' => '8.1', 'name' => 'Modeling Stock Rebalancing and Forecast', 'category' => 'TECH'],
                    ['code' => '8.2', 'name' => 'Testing AI Results', 'category' => 'TECH'],
                    ['code' => '8.3', 'name' => 'Dashboard Development', 'category' => 'TECH'],
                ]
            ],
            [
                'no' => 9,
                'client_or_rd' => 'Kutai Energy',
                'kd_id' => '9',
                'key_deliverables' => 'Fleet Management AI',
                'status' => 'Ongoing',
                'pic' => 'Arthur',
                'progress_update' => 'Review PKS dari KE',
                'next_steps' => '1. Kirim PKS ke KE 2. PKS Agreement with Kutai Energi 3. Additional Hardware Installation & On-Site Trial',
                'due_date' => null,
                'deliverables' => [
                    ['code' => '9.1', 'name' => 'Equipment readiness', 'category' => 'TECH', 'pic' => 'Arthur'],
                    ['code' => '9.2', 'name' => 'PKC Development', 'category' => 'TECH', 'pic' => 'Sandro'],
                    ['code' => '9.3', 'name' => 'PKC Demo', 'category' => 'TECH', 'pic' => 'Duwi'],
                ]
            ],
        ];

        foreach ($projects as $projectData) {
            $deliverables = $projectData['deliverables'];
            unset($projectData['deliverables']);

            $project = Project::create($projectData);

            foreach ($deliverables as $deliverable) {
                ProjectDeliverable::create([
                    'project_id' => $project->id,
                    'code' => $deliverable['code'],
                    'deliverable_name' => $deliverable['name'],
                    'category' => $deliverable['category'] ?? 'OTHER',
                    'pic' => $deliverable['pic'] ?? null,
                    'progress_update' => $deliverable['progress'] ?? null,
                    'next_steps' => $deliverable['next'] ?? null,
                    'is_completed' => $deliverable['is_completed'] ?? false,
                    'completion_percentage' => ($deliverable['is_completed'] ?? false) ? 100 : 0,
                ]);
            }
        }
    }
}
