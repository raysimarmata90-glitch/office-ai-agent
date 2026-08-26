<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatData;
use Tests\TestCase;

class HalamanAwalTest extends TestCase
{
    use MembuatData;
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_daftar(): void
    {
        $this->get('/')->assertRedirect(route('register'));
    }

    public function test_pengguna_biasa_diarahkan_ke_dashboard(): void
    {
        $this->actingAs($this->buatUser())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_diarahkan_ke_dashboard_admin(): void
    {
        $this->actingAs($this->buatAdmin())
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }
}
