<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use MembuatData;
    use RefreshDatabase;

    public function test_pengguna_bisa_membuat_tugas(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->post('/tasks', $this->isianTugas(['judul' => 'Menyusun laporan', 'user_id' => $user->id]))
            ->assertRedirect(route('pekerjaan.index'));

        $this->assertDatabaseHas('tasks', [
            'judul' => 'Menyusun laporan',
            'user_id' => $user->id,
            'created_by' => $user->id,
        ]);
    }

    public function test_membuat_tugas_lewat_ajax_dibalas_json(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->postJson('/tasks', $this->isianTugas(['user_id' => $user->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'pesan', 'id']);
    }

    public function test_proyek_wajib_diisi(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->postJson('/tasks', $this->isianTugas(['project_id' => null, 'user_id' => $user->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('project_id');
    }

    public function test_selesai_tidak_boleh_mendahului_mulai(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->postJson('/tasks', $this->isianTugas([
                'user_id' => $user->id,
                'mulai' => now()->addWeek()->toDateString(),
                'selesai' => now()->toDateString(),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('selesai');
    }

    public function test_proyek_baru_dibuat_saat_nama_diisi(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)->postJson('/tasks', $this->isianTugas([
            'user_id' => $user->id,
            'project_id' => null,
            'project_baru' => 'Proyek Lahir dari Form',
        ]))->assertOk();

        $this->assertDatabaseHas('projects', ['nama' => 'Proyek Lahir dari Form']);
    }

    public function test_bukan_pembuat_tidak_boleh_mengubah_tugas(): void
    {
        $pemilik = $this->buatUser();
        $orangLain = $this->buatUser();
        $tugas = $this->buatTugas(['user_id' => $pemilik->id, 'created_by' => $pemilik->id]);

        $this->actingAs($orangLain)
            ->patch('/tasks/' . $tugas->id, $this->isianTugas(['user_id' => $pemilik->id]))
            ->assertForbidden();
    }

    /**
     * Regresi: form modal admin mengirim lewat fetch. Sebelum diperbaiki,
     * controller selalu mengalihkan ke halaman Pekerjaan Saya milik role user.
     */
    public function test_admin_mengubah_tugas_lewat_ajax_dibalas_json_bukan_redirect(): void
    {
        $admin = $this->buatAdmin();
        $pemilik = $this->buatUser();
        $tugas = $this->buatTugas(['user_id' => $pemilik->id, 'created_by' => $pemilik->id]);

        $balasan = $this->actingAs($admin)->patchJson('/tasks/' . $tugas->id, [
            'judul' => 'Judul yang diperbarui',
            'project_id' => $tugas->project_id,
            'status' => Task::STATUS_REVIEW,
            'prioritas' => 'Tinggi',
            'mulai' => now()->toDateString(),
            'selesai' => now()->addWeek()->toDateString(),
            'user_id' => $pemilik->id,
        ]);

        $balasan->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('tasks', [
            'id' => $tugas->id,
            'judul' => 'Judul yang diperbarui',
            'status' => Task::STATUS_REVIEW,
        ]);
    }

    public function test_status_done_mengisi_selesai_pada(): void
    {
        $user = $this->buatUser();
        $tugas = $this->buatTugas(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patchJson('/tasks/' . $tugas->id . '/status', ['status' => Task::STATUS_DONE])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => Task::STATUS_DONE]);

        $this->assertNotNull($tugas->fresh()->selesai_pada);
    }

    public function test_status_selain_done_mengosongkan_selesai_pada(): void
    {
        $user = $this->buatUser();
        $tugas = $this->buatTugas([
            'user_id' => $user->id,
            'status' => Task::STATUS_DONE,
            'selesai_pada' => now(),
        ]);

        $this->actingAs($user)
            ->patchJson('/tasks/' . $tugas->id . '/status', ['status' => Task::STATUS_IN_PROGRESS])
            ->assertOk();

        $this->assertNull($tugas->fresh()->selesai_pada);
    }

    public function test_status_tidak_dikenal_ditolak(): void
    {
        $user = $this->buatUser();
        $tugas = $this->buatTugas(['user_id' => $user->id]);

        $this->actingAs($user)
            ->patchJson('/tasks/' . $tugas->id . '/status', ['status' => 'entah'])
            ->assertStatus(422);
    }

    public function test_orang_lain_tidak_boleh_menggeser_status(): void
    {
        $tugas = $this->buatTugas();

        $this->actingAs($this->buatUser())
            ->patchJson('/tasks/' . $tugas->id . '/status', ['status' => Task::STATUS_DONE])
            ->assertForbidden();
    }

    public function test_admin_boleh_menggeser_status_tugas_orang_lain(): void
    {
        $tugas = $this->buatTugas();

        $this->actingAs($this->buatAdmin())
            ->patchJson('/tasks/' . $tugas->id . '/status', ['status' => Task::STATUS_DONE])
            ->assertOk();

        $this->assertSame(Task::STATUS_DONE, $tugas->fresh()->status);
    }

    public function test_detail_tugas_hanya_untuk_pemilik_reviewer_atau_admin(): void
    {
        $pemilik = $this->buatUser();
        $reviewer = $this->buatUser();
        $tugas = $this->buatTugas(['user_id' => $pemilik->id, 'reviewer_id' => $reviewer->id]);

        $this->actingAs($pemilik)->getJson('/tasks/' . $tugas->id)->assertOk();
        $this->actingAs($reviewer)->getJson('/tasks/' . $tugas->id)->assertOk();
        $this->actingAs($this->buatAdmin())->getJson('/tasks/' . $tugas->id)->assertOk();
        $this->actingAs($this->buatUser())->getJson('/tasks/' . $tugas->id)->assertForbidden();
    }

    public function test_detail_tugas_memuat_nilai_untuk_mengisi_ulang_form(): void
    {
        $pemilik = $this->buatUser();
        $tugas = $this->buatTugas(['user_id' => $pemilik->id]);

        $this->actingAs($pemilik)
            ->getJson('/tasks/' . $tugas->id)
            ->assertOk()
            ->assertJsonPath('form.project_id', $tugas->project_id)
            ->assertJsonPath('form.user_id', $pemilik->id)
            ->assertJsonStructure(['id', 'judul', 'status', 'evidences', 'form' => ['project_id', 'user_id', 'mulai', 'selesai']]);
    }

    public function test_pembuat_bisa_menghapus_tugasnya(): void
    {
        $user = $this->buatUser();
        $tugas = $this->buatTugas(['user_id' => $user->id, 'created_by' => $user->id]);

        $this->actingAs($user)
            ->deleteJson('/tasks/' . $tugas->id)
            ->assertOk();

        $this->assertDatabaseMissing('tasks', ['id' => $tugas->id]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $tugas = $this->buatTugas();

        $this->get('/tasks/' . $tugas->id)->assertRedirect(route('login'));
    }
}
