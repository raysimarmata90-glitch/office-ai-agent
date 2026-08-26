<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskEvidence;
use App\Support\BatasUnggah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class EvidenceUnggahTest extends TestCase
{
    use MembuatData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_membuat_tugas_dengan_evidence_menyimpan_berkasnya(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->postJson('/tasks', $this->isianTugas([
            'user_id' => $user->id,
            'evidence' => [UploadedFile::fake()->create('laporan.pdf', 40, 'application/pdf')],
        ]))->assertOk();

        $evidence = TaskEvidence::firstOrFail();

        $this->assertSame('laporan.pdf', $evidence->nama_file);
        $this->assertSame($user->id, $evidence->uploaded_by);
        Storage::disk('public')->assertExists($evidence->path);
    }

    public function test_beberapa_berkas_sekaligus_ikut_tersimpan(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->postJson('/tasks', $this->isianTugas([
            'user_id' => $user->id,
            'evidence' => [
                UploadedFile::fake()->create('satu.pdf', 10, 'application/pdf'),
                UploadedFile::fake()->image('dua.png'),
            ],
        ]))->assertOk();

        $this->assertSame(2, TaskEvidence::count());
    }

    public function test_format_yang_tidak_didukung_ditolak(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->postJson('/tasks', $this->isianTugas([
            'user_id' => $user->id,
            'evidence' => [UploadedFile::fake()->create('jahat.exe', 10)],
        ]))->assertStatus(422)->assertJsonValidationErrors('evidence.0');

        $this->assertSame(0, TaskEvidence::count());
    }

    public function test_berkas_melebihi_batas_ukuran_ditolak(): void
    {
        $user = $this->buatUser();
        $terlaluBesar = BatasUnggah::maksKb() + 512;

        $this->actingAs($user)->postJson('/tasks', $this->isianTugas([
            'user_id' => $user->id,
            'evidence' => [UploadedFile::fake()->create('besar.pdf', $terlaluBesar, 'application/pdf')],
        ]))->assertStatus(422)->assertJsonValidationErrors('evidence.0');

        $this->assertSame(0, TaskEvidence::count());
    }

    public function test_mengubah_tugas_bisa_menambah_evidence_baru(): void
    {
        $admin = $this->buatAdmin();
        $tugas = $this->buatTugas();

        $this->actingAs($admin)->patchJson('/tasks/' . $tugas->id, [
            'judul' => $tugas->judul,
            'project_id' => $tugas->project_id,
            'status' => Task::STATUS_REVIEW,
            'prioritas' => 'Sedang',
            'mulai' => now()->toDateString(),
            'selesai' => now()->addWeek()->toDateString(),
            'user_id' => $tugas->user_id,
            'evidence' => [UploadedFile::fake()->create('tambahan.md', 5, 'text/markdown')],
        ])->assertOk();

        $this->assertDatabaseHas('task_evidences', [
            'task_id' => $tugas->id,
            'nama_file' => 'tambahan.md',
        ]);
    }

    public function test_admin_bisa_assign_tugas_beserta_evidence(): void
    {
        $admin = $this->buatAdmin();
        $pegawai = $this->buatUser();
        $proyek = $this->buatProyek();

        $this->actingAs($admin)->postJson('/admin/tugas/assign', [
            'judul' => 'Tugas dari admin',
            'project_id' => $proyek->id,
            'user_id' => $pegawai->id,
            'status' => Task::STATUS_TO_DO,
            'prioritas' => 'Sedang',
            'mulai' => now()->toDateString(),
            'selesai' => now()->addWeek()->toDateString(),
            'evidence' => [UploadedFile::fake()->create('brief.pdf', 12, 'application/pdf')],
        ])->assertOk()->assertJson(['success' => true]);

        $tugas = Task::where('judul', 'Tugas dari admin')->firstOrFail();

        $this->assertDatabaseHas('task_evidences', [
            'task_id' => $tugas->id,
            'nama_file' => 'brief.pdf',
            'uploaded_by' => $admin->id,
        ]);
    }

    public function test_assign_menolak_format_evidence_yang_tidak_didukung(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)->postJson('/admin/tugas/assign', [
            'judul' => 'Tugas dengan berkas aneh',
            'project_id' => $this->buatProyek()->id,
            'user_id' => $this->buatUser()->id,
            'status' => Task::STATUS_TO_DO,
            'prioritas' => 'Sedang',
            'mulai' => now()->toDateString(),
            'selesai' => now()->addWeek()->toDateString(),
            'evidence' => [UploadedFile::fake()->create('jahat.exe', 10)],
        ])->assertStatus(422)->assertJsonValidationErrors('evidence.0');

        $this->assertSame(0, Task::where('judul', 'Tugas dengan berkas aneh')->count());
    }

    public function test_menghapus_evidence_ikut_menghapus_berkasnya(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->postJson('/tasks', $this->isianTugas([
            'user_id' => $user->id,
            'evidence' => [UploadedFile::fake()->create('hapus-saya.pdf', 8, 'application/pdf')],
        ]))->assertOk();

        $evidence = TaskEvidence::firstOrFail();
        $path = $evidence->path;

        $this->actingAs($user)
            ->deleteJson('/evidence/' . $evidence->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('task_evidences', ['id' => $evidence->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_menghapus_tugas_ikut_membersihkan_evidence(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->postJson('/tasks', $this->isianTugas([
            'user_id' => $user->id,
            'evidence' => [UploadedFile::fake()->create('ikut-terhapus.pdf', 8, 'application/pdf')],
        ]))->assertOk();

        $tugas = Task::firstOrFail();
        $evidence = TaskEvidence::firstOrFail();

        $this->actingAs($user)->deleteJson('/tasks/' . $tugas->id)->assertOk();

        $this->assertDatabaseMissing('task_evidences', ['id' => $evidence->id]);
        Storage::disk('public')->assertMissing($evidence->path);
    }
}
