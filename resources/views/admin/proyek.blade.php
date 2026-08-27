@extends('layouts.admin')
@section('title', 'Proyek')
@section('page-title', 'Proyek')
@section('page-sub', $projects->count() . ' proyek terdaftar')

@section('topbar-actions')
<button class="btn btn-primary" type="button" data-open-assign>
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
Assign Tugas
</button>
@endsection

@push('script')
<script>
(function(){
let cari='';
const kartu=document.getElementById('proyekCard');
function terapkan(){
document.querySelectorAll('tr[data-row]').forEach(function(tr){
let ok=true;
if(cari&&(tr.dataset.cari||'').indexOf(cari)===-1)ok=false;
tr.dataset.filterOff=ok?'':'1';
});
if(kartu&&kartu.inaaiTable)kartu.inaaiTable.segarkan();
}
const inCari=document.getElementById('pjCari');
if(inCari)inCari.addEventListener('input',function(){cari=inCari.value.trim().toLowerCase();terapkan()});
const bReset=document.getElementById('pjReset');
if(bReset)bReset.addEventListener('click',function(){
cari='';
if(inCari)inCari.value='';
terapkan();
});

// Hapus proyek
async function hapusProyek(id, nama) {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    const lanjut = window.InaaiDialog ? await window.InaaiDialog.konfirmasi({
        judul: 'Hapus Proyek',
        teks: 'Proyek "' + nama + '" akan dihapus permanen beserta seluruh tugas yang terkait. Tindakan ini tidak bisa dibatalkan.',
        ok: 'Iya, hapus proyek',
        jenis: 'bahaya'
    }) : confirm('Hapus proyek "' + nama + '"?');
    
    if (!lanjut) return;
    
    try {
        const r = await fetch('/admin/proyek/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const d = await r.json();
        if (!r.ok) {
            window.InaaiToast && window.InaaiToast.galat(d.message || 'Gagal menghapus proyek.');
            return;
        }
        window.InaaiToast && window.InaaiToast.sukses(d.pesan || 'Proyek berhasil dihapus.');
        setTimeout(() => location.reload(), 650);
    } catch (err) {
        window.InaaiToast && window.InaaiToast.galat('Koneksi bermasalah: ' + err.message);
    }
}

document.querySelectorAll('[data-hapus-proyek]').forEach(b => {
    b.addEventListener('click', () => hapusProyek(b.dataset.hapusProyek, b.dataset.nama || ''));
});
})();
</script>
@endpush

@section('content')
<div class="card" data-table id="proyekCard">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg></span>Daftar Proyek</div>
<div class="card-desc">Seluruh proyek beserta progres dan jumlah kontributornya. Bar progres memuat porsi tiap status.</div>
<div class="legend" style="margin-top:9px;margin-bottom:0">
@foreach(\App\Models\Task::daftarStatusSelesaiDulu() as $k => $lbl)
<span><i style="background:{{ \App\Models\Task::titikStatus($k) }}"></i>{{ $lbl }}</span>
@endforeach
</div>
</div>
<div class="dp-bar">
<label class="dp-cari">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
<input type="search" id="pjCari" placeholder="Cari nama proyek…" aria-label="Cari proyek">
</label>
<button type="button" class="btn btn-sm" id="pjReset">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
Reset
</button>
</div>
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
@include('partials.th-sort', ['label' => 'Proyek', 'ikon' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>'])
@include('partials.th-sort', ['label' => 'Periode', 'ikon' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/>'])
@include('partials.th-sort', ['label' => 'Tugas', 'ikon' => '<path d="M11 12H3M16 6H3M16 18H3"/><path d="m18 9 3 3-3 3"/>'])
@include('partials.th-sort', ['label' => 'Kontributor', 'ikon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'])
@include('partials.th-sort', ['label' => 'Progress', 'ikon' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9M13 17V5M8 17v-3"/>'])
@include('partials.th-sort', ['label' => 'Dibuat', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'])
<th style="width:88px">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($projects as $p)
<tr data-row data-cari="{{ Str::lower($p['nama']) }}">
<td data-no></td>
<td data-sort="{{ $p['nama'] }}">
<div style="display:flex;align-items:center;gap:8px">
<i style="width:9px;height:9px;border-radius:3px;background:{{ $p['warna'] }};flex:none"></i>
<strong>{{ $p['nama'] }}</strong>
</div>
</td>
<td style="white-space:nowrap;color:var(--muted)">{{ $p['periode'] }}</td>
<td data-sort="{{ $p['tugas'] }}">{{ $p['tugas'] }}</td>
<td data-sort="{{ $p['kontributor'] }}">{{ $p['kontributor'] }}</td>
<td data-sort="{{ $p['pct'] }}" style="min-width:150px">
<div style="display:flex;align-items:center;gap:9px">
<div class="stack" style="flex:1">
@foreach($p['segmen'] as $sg)
@if($sg['w'] > 0)<i style="width:{{ $sg['w'] }}%;background:{{ $sg['warna'] }}" title="{{ $sg['label'] }}: {{ $sg['jumlah'] }}"></i>@endif
@endforeach
</div>
<span style="font-size:11.5px;color:var(--muted2);white-space:nowrap">{{ $p['pct'] }}%</span>
</div>
</td>
<td data-sort="{{ $p['dibuat']?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">
{{ $p['dibuat']?->format('d/m/y, H:i') }}
</td>
<td>
<a href="{{ route('admin.proyek.show', $p['id']) }}" class="btn btn-sm">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.87 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.87 0"/><circle cx="12" cy="12" r="3"/></svg>
View
</a>
<button type="button" class="btn btn-sm" data-hapus-proyek="{{ $p['id'] }}" data-nama="{{ $p['nama'] }}">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
Hapus
</button>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div data-empty class="empty" style="display:none">Belum ada proyek.</div>
@include('partials.pager')
</div>

@php($projects = \App\Models\Project::orderBy('nama')->get())
@php($semuaUser = \App\Models\User::where('is_active', true)->orderBy('name')->get())
@include('partials.modal-assign')
@endsection
