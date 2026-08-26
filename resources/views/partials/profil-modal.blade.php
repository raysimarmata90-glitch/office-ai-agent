{{-- Modal profil pengguna: data diri + foto, ganti password, dan daftar sesi aktif. --}}
@php($akuProfil = auth()->user())
@php($depProfil = \App\Http\Controllers\ProfilController::pilihanDepartemen($akuProfil))
@php($depBolehProfil = $depProfil->pluck('id'))
@php($roleProfil = \App\Models\Role::orderBy('display_name')
    ->when(! $akuProfil->isAdmin(), fn ($q) => $q->whereNotIn('name', \App\Http\Controllers\ProfilController::ROLE_TERBATAS))
    ->get(['id', 'name', 'display_name', 'department_id'])
    ->filter(fn ($r) => $r->department_id === null || $depBolehProfil->contains($r->department_id)))

<x-modal id="profilModal" lebar="620px" judul="Profil Saya"
         desc="Perbarui data diri, ganti password, dan tinjau perangkat yang sedang masuk."
         :ikon="'<svg width=\'15\' height=\'15\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2\'/><circle cx=\'12\' cy=\'7\' r=\'4\'/></svg>'">

<div class="pf-tabs" role="tablist">
<button type="button" class="pf-tab on" data-pf-tab="data" role="tab">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
Data Diri
</button>
<button type="button" class="pf-tab" data-pf-tab="sandi" role="tab">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
Password
</button>
<button type="button" class="pf-tab" data-pf-tab="sesi" role="tab">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
Sesi Aktif
</button>
</div>

{{-- Tab: data diri --}}
<form id="profilForm" class="modal-form" data-pf-panel="data" enctype="multipart/form-data">
@csrf
<div class="modal-body">
<div class="pf-foto">
<div class="pf-av" id="pfAv"></div>
<div style="flex:1;min-width:0">
<div class="pf-nama" id="pfNama"></div>
<div class="pf-meta" id="pfMeta"></div>
<div class="pf-aksi">
<button type="button" class="btn btn-sm" id="pfPilihFoto">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5M12 3v12"/></svg>
Ganti Foto
</button>
<button type="button" class="btn btn-sm" id="pfHapusFoto">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
Hapus Foto
</button>
</div>
<input type="file" name="foto" id="pfFoto" accept="image/*" style="display:none">
</div>
</div>
<div class="row2">
<div class="fld"><label>Nama</label><input type="text" name="name" id="pfInName" required></div>
<div class="fld">
<label>Email <span class="fld-kunci">terkunci</span></label>
{{-- Email adalah identitas login, jadi tidak bisa diklik maupun diubah di sini. --}}
<input type="text" id="pfInEmail" readonly disabled tabindex="-1" aria-readonly="true">
</div>
</div>
@if(!$akuProfil->isAdmin())
{{-- User biasa: tampilkan Departemen (locked) dan Role (locked) --}}
<div class="row2">
<div class="fld"><label>Departemen <span class="fld-kunci">terkunci</span></label>
<input type="text" value="{{ $akuProfil->department?->name ?? '-' }}" readonly disabled tabindex="-1" aria-readonly="true">
<input type="hidden" name="department_id" value="{{ $akuProfil->department_id }}">
<p class="fld-hint">Departemen Anda ditetapkan oleh administrator dan tidak dapat diubah sendiri.</p>
</div>
<div class="fld"><label>Role <span class="fld-kunci">terkunci</span></label>
<input type="text" value="{{ $akuProfil->namaRole() }}" readonly disabled tabindex="-1" aria-readonly="true">
<input type="hidden" name="role_id" value="{{ $akuProfil->role_id }}">
<p class="fld-hint">Role Anda ditetapkan oleh administrator dan tidak dapat diubah sendiri.</p>
</div>
</div>
<div class="fld"><label>Telepon</label><input type="text" name="phone" id="pfInPhone" placeholder="08xxxxxxxxxx"></div>
@else
{{-- Admin: Telepon (kiri) + Role (kanan), lalu Bio full width --}}
<div class="row2">
<div class="fld"><label>Telepon</label><input type="text" name="phone" id="pfInPhone" placeholder="08xxxxxxxxxx"></div>
<div class="fld"><label>Role <span class="fld-kunci">terkunci</span></label>
<select name="role_id" id="pfInRole" data-select data-placeholder="Pilih role" 
        disabled readonly tabindex="-1" aria-readonly="true">
@foreach($roleProfil as $r)
<option value="{{ $r->id }}" data-dep="{{ $r->department_id ?? '' }}" @selected($akuProfil->role_id === $r->id)>{{ $r->display_name ?: $r->name }}</option>
@endforeach
</select>
<input type="hidden" name="role_id" value="{{ $akuProfil->role_id }}">
<p class="fld-hint">Admin tidak dapat mengubah rolenya sendiri untuk menjaga keamanan sistem.</p>
</div>
</div>
@endif
<div class="fld"><label>Bio</label><textarea name="bio" id="pfInBio" rows="3" placeholder="Ceritakan singkat tentang Anda (opsional)"></textarea></div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-modal-close>
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
Tutup
</button>
<button type="submit" class="btn btn-primary">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
Simpan Perubahan
</button>
</div>
</form>

{{-- Tab: password --}}
<form id="sandiForm" class="modal-form" data-pf-panel="sandi" style="display:none">
@csrf
<div class="modal-body">
<div class="fld"><label>Password Lama</label><input type="password" name="sandi_lama" required autocomplete="current-password"></div>
<div class="row2">
<div class="fld"><label>Password Baru</label><input type="password" name="sandi" required minlength="8" autocomplete="new-password"></div>
<div class="fld"><label>Ulangi Password Baru</label><input type="password" name="sandi_confirmation" required minlength="8" autocomplete="new-password"></div>
</div>
<div class="card-desc">Minimal 8 karakter. Setelah diganti, sesi di perangkat lain otomatis dikeluarkan.</div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-modal-close>
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
Tutup
</button>
<button type="submit" class="btn btn-primary">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
Ganti Password
</button>
</div>
</form>

{{-- Tab: sesi aktif --}}
<div class="modal-form" data-pf-panel="sesi" style="display:none">
<div class="modal-body">
<div id="pfSesi"><div class="empty">Memuat sesi…</div></div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-modal-close>
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
Tutup
</button>
</div>
</div>
</x-modal>
