<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class AdminKanbanFeedTest extends TestCase
{
    use MembuatData;
    use RefreshDatabase;

    private function feed(array $param = [])
    {
        return $this->getJson('/admin/kanban?' . http_build_query(array_merge([
            'status' => Task::STATUS_TO_DO,
            'offset' => 0,
        ], $param)));
    }

    public function test_hanya_admin_yang_boleh_mengaksesnya(): void
    {
        $this->actingAs($this->buatUser())->get('/admin/kanban?status=to_do')->assertForbidden();
    }

    public function test_status_tidak_dikenal_menghasilkan_404(): void
    {
        $this->actingAs($this->buatAdmin());

        $this->feed(['status' => 'entah'])->assertNotFound();
    }

    public function test_mengirim_kartu_beserta_total_dan_penanda_habis(): void
    {
        $admin = $this->buatAdmin();
        $proyek = $this->buatProyek();
        $this->buatTugas(['project_id' => $proyek->id, 'judul' => 'Tugas satu']);
        $this->buatTugas(['project_id' => $proyek->id, 'judul' => 'Tugas dua']);

        $balasan = $this->actingAs($admin)->feed();

        $balasan->assertOk()
            ->assertJson(['jumlah' => 2, 'total' => 2, 'habis' => true]);
        $this->assertStringContainsString('Tugas satu', $balasan->json('html'));
    }

    public function test_kolom_kosong_tetap_dibalas_dengan_habis(): void
    {
        $this->actingAs($this->buatAdmin())
            ->feed(['status' => Task::STATUS_BLOCKED])
            ->assertOk()
            ->assertJson(['jumlah' => 0, 'total' => 0, 'habis' => true, 'html' => '']);
    }

    public function test_dimuat_bertahap_sebanyak_batas_per_muat(): void
    {
        $admin = $this->buatAdmin();
        $proyek = $this->buatProyek();
        $batas = AdminDashboardController::KANBAN_PER_MUAT;

        for ($i = 0; $i < $batas + 5; $i++) {
            $this->buatTugas(['project_id' => $proyek->id, 'judul' => 'Tugas ke-' . $i]);
        }

        $this->actingAs($admin)->feed()
            ->assertOk()
            ->assertJson(['jumlah' => $batas, 'total' => $batas + 5, 'habis' => false]);

        $this->actingAs($admin)->feed(['offset' => $batas])
            ->assertOk()
            ->assertJson(['jumlah' => 5, 'total' => $batas + 5, 'habis' => true]);
    }

    public function test_muatan_kedua_tidak_mengulang_kartu_yang_sama(): void
    {
        $admin = $this->buatAdmin();
        $proyek = $this->buatProyek();
        $batas = AdminDashboardController::KANBAN_PER_MUAT;

        for ($i = 0; $i < $batas + 3; $i++) {
            $this->buatTugas(['project_id' => $proyek->id, 'judul' => 'Kartu ' . $i]);
        }

        $ambilId = function (string $html) {
            preg_match_all('/data-id="(\d+)"/', $html, $c);

            return $c[1];
        };

        $satu = $ambilId($this->actingAs($admin)->feed()->json('html'));
        $dua = $ambilId($this->actingAs($admin)->feed(['offset' => $batas])->json('html'));

        $this->assertCount($batas, $satu);
        $this->assertCount(3, $dua);
        $this->assertEmpty(array_intersect($satu, $dua), 'Kartu yang sama termuat dua kali.');
    }

    public function test_menyaring_berdasarkan_kata_kunci(): void
    {
        $admin = $this->buatAdmin();
        $proyek = $this->buatProyek(['nama' => 'Migrasi Data']);
        $this->buatTugas(['project_id' => $proyek->id, 'judul' => 'Integrasi API pembayaran']);
        $this->buatTugas(['project_id' => $proyek->id, 'judul' => 'Menulis dokumentasi']);

        $this->actingAs($admin)->feed(['q' => 'integrasi'])
            ->assertOk()
            ->assertJson(['total' => 1]);

        // Kata kunci juga mencari sampai ke nama proyek.
        $this->actingAs($admin)->feed(['q' => 'migrasi'])
            ->assertOk()
            ->assertJson(['total' => 2]);
    }

    public function test_menyaring_berdasarkan_proyek_prioritas_dan_pemilik(): void
    {
        $admin = $this->buatAdmin();
        $andi = $this->buatUser(['name' => 'Andi Pratama']);
        $proyekA = $this->buatProyek(['nama' => 'Proyek A']);
        $proyekB = $this->buatProyek(['nama' => 'Proyek B']);

        $this->buatTugas(['project_id' => $proyekA->id, 'user_id' => $andi->id, 'prioritas' => 'Tinggi']);
        $this->buatTugas(['project_id' => $proyekB->id, 'prioritas' => 'Rendah']);

        $this->actingAs($admin)->feed(['proyek' => 'Proyek A'])->assertJson(['total' => 1]);
        $this->actingAs($admin)->feed(['prioritas' => 'Rendah'])->assertJson(['total' => 1]);
        $this->actingAs($admin)->feed(['user' => 'Andi Pratama'])->assertJson(['total' => 1]);
        $this->actingAs($admin)->feed(['proyek' => 'Proyek A', 'prioritas' => 'Rendah'])->assertJson(['total' => 0]);
    }

    public function test_rentang_tanggal_hanya_memuat_jadwal_yang_beririsan(): void
    {
        $admin = $this->buatAdmin();
        $proyek = $this->buatProyek();

        $this->buatTugas([
            'project_id' => $proyek->id,
            'judul' => 'Di dalam rentang',
            'mulai' => '2026-03-05',
            'selesai' => '2026-03-10',
        ]);
        $this->buatTugas([
            'project_id' => $proyek->id,
            'judul' => 'Di luar rentang',
            'mulai' => '2026-06-01',
            'selesai' => '2026-06-10',
        ]);

        $this->actingAs($admin)
            ->feed(['dari' => '2026-03-01', 'sampai' => '2026-03-31'])
            ->assertOk()
            ->assertJson(['total' => 1]);
    }

    public function test_tugas_tanpa_tanggal_tidak_ikut_tersaring_rentang(): void
    {
        $admin = $this->buatAdmin();
        $proyek = $this->buatProyek();

        $this->buatTugas([
            'project_id' => $proyek->id,
            'judul' => 'Tanpa jadwal',
            'mulai' => null,
            'selesai' => null,
        ]);

        $this->actingAs($admin)
            ->feed(['dari' => '2026-03-01', 'sampai' => '2026-03-31'])
            ->assertOk()
            ->assertJson(['total' => 1]);
    }

    public function test_kartu_memuat_tombol_aksi_dan_bisa_diseret(): void
    {
        $admin = $this->buatAdmin();
        $tugas = $this->buatTugas();

        $html = $this->actingAs($admin)->feed()->json('html');

        $this->assertStringContainsString('draggable="true"', $html);
        $this->assertStringContainsString('data-lihat="' . $tugas->id . '"', $html);
        $this->assertStringContainsString('data-ubah="' . $tugas->id . '"', $html);
        $this->assertStringContainsString('data-hapus="' . $tugas->id . '"', $html);
    }
}
