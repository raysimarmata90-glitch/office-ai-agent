<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class AdminPercakapanTest extends TestCase
{
    use MembuatData;
    use RefreshDatabase;

    private function buatPercakapan(int $jumlah, array $atribut = []): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            Conversation::create(array_merge([
                'user_id' => $this->buatUser()->id,
                'department_id' => $this->departemen()->id,
                'title' => 'Percakapan ' . $i,
                'status' => 'active',
            ], $atribut));
        }
    }

    public function test_hanya_admin_yang_boleh_membukanya(): void
    {
        $this->actingAs($this->buatUser())->get('/admin/conversations')->assertForbidden();
    }

    public function test_dipaginasi_sepuluh_baris_per_halaman(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPercakapan(12);

        $halaman1 = $this->actingAs($admin)->get('/admin/conversations');
        $halaman1->assertOk()->assertSee('1–10 dari 12');

        $halaman2 = $this->actingAs($admin)->get('/admin/conversations?page=2');
        $halaman2->assertOk()->assertSee('11–12 dari 12');
    }

    public function test_jumlah_baris_bisa_diubah(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPercakapan(12);

        $this->actingAs($admin)
            ->get('/admin/conversations?per_page=25')
            ->assertOk()
            ->assertSee('1–12 dari 12');
    }

    public function test_jumlah_baris_di_luar_pilihan_dikembalikan_ke_default(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPercakapan(12);

        // Tanpa penjagaan ini, ?per_page=100000 bisa dipakai menarik semua baris.
        $this->actingAs($admin)
            ->get('/admin/conversations?per_page=100000')
            ->assertOk()
            ->assertSee('1–10 dari 12');
    }

    public function test_menyaring_berdasarkan_status(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPercakapan(3, ['status' => 'active']);
        $this->buatPercakapan(2, ['status' => 'completed']);

        $this->actingAs($admin)
            ->get('/admin/conversations?status=completed')
            ->assertOk()
            ->assertSee('1–2 dari 2');
    }

    public function test_menyaring_berdasarkan_departemen(): void
    {
        $admin = $this->buatAdmin();
        $dep = $this->departemen('Riset', 'riset');

        $this->buatPercakapan(2);
        $this->buatPercakapan(1, ['department_id' => $dep->id]);

        $this->actingAs($admin)
            ->get('/admin/conversations?departemen=' . $dep->id)
            ->assertOk()
            ->assertSee('1–1 dari 1');
    }

    public function test_pencarian_menjangkau_judul_dan_pengguna(): void
    {
        $admin = $this->buatAdmin();
        $sari = $this->buatUser(['name' => 'Sari Wulandari', 'email' => 'sari@inaai.test']);

        $dep = $this->departemen()->id;
        Conversation::create(['user_id' => $sari->id, 'department_id' => $dep, 'title' => 'Migrasi ERP', 'status' => 'active']);
        Conversation::create(['user_id' => $this->buatUser()->id, 'department_id' => $dep, 'title' => 'Portal Karyawan', 'status' => 'active']);

        $this->actingAs($admin)->get('/admin/conversations?q=migrasi')->assertOk()->assertSee('1–1 dari 1');
        $this->actingAs($admin)->get('/admin/conversations?q=sari')->assertOk()->assertSee('1–1 dari 1');
        $this->actingAs($admin)->get('/admin/conversations?q=tidakada')->assertOk()->assertSee('0 dari 0');
    }

    public function test_saringan_ikut_terbawa_saat_pindah_halaman(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPercakapan(12, ['status' => 'active']);
        $this->buatPercakapan(3, ['status' => 'completed']);

        $this->actingAs($admin)
            ->get('/admin/conversations?status=active&page=2')
            ->assertOk()
            ->assertSee('11–12 dari 12');
    }

    public function test_ringkasan_menghitung_seluruh_percakapan_bukan_satu_halaman(): void
    {
        $admin = $this->buatAdmin();
        $this->buatPercakapan(12);
        $percakapan = Conversation::first();
        Message::create([
            'conversation_id' => $percakapan->id,
            'sender_type' => 'user',
            'content' => 'halo',
            'step_number' => 1,
        ]);

        $balasan = $this->actingAs($admin)->get('/admin/conversations')->assertOk();

        $balasan->assertViewHas('ringkas', fn ($r) => $r['total'] === 12 && $r['pesan'] === 1);
    }
}
