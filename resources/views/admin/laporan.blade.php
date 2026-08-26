@extends('layouts.admin')
@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('page-sub', 'Rekap kinerja proyek dan kontributor')

@section('topbar-actions')
<form method="GET" action="{{ route('admin.laporan') }}" style="display:flex;align-items:center;gap:8px">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted2)"><path d="M8 2v4M16 2v4M3 10h18"/><rect width="18" height="18" x="3" y="4" rx="2"/></svg>
<select name="rentang" onchange="this.form.submit()" data-select>
<option value="semua" @selected($filter === 'semua')>Semua Waktu</option>
<option value="7hari" @selected($filter === '7hari')>7 Hari Terakhir</option>
<option value="30hari" @selected($filter === '30hari')>30 Hari Terakhir</option>
<option value="bulan" @selected($filter === 'bulan')>Bulan Ini</option>
<option value="kuartal" @selected($filter === 'kuartal')>Kuartal Ini</option>
</select>
</form>
<a href="{{ route('admin.laporan.ekspor', ['rentang' => $filter]) }}" class="btn btn-primary">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg>
Ekspor CSV
</a>
@include('partials.drawer-tugas')
@endsection

@push('script')
<script>
(function(){
/* Klik bar proyek/kontributor menyaring dua kartu lainnya sekaligus. */
let fProyek='',fKontributor='',cari='',fStatus='',fPrioritas='';
const kartu=document.querySelector('[data-table]'),info=document.getElementById('lapInfo');

function terapkan(){
document.querySelectorAll('[data-proyek].lap-klik').forEach(el=>el.classList.toggle('on',el.dataset.proyek===fProyek));
document.querySelectorAll('[data-kontributor].lap-klik').forEach(el=>el.classList.toggle('on',el.dataset.kontributor===fKontributor));

// Kartu kinerja saling menyaring.
document.querySelectorAll('[data-kontributor].lap-klik').forEach(function(el){
el.style.display=(!fProyek||orangDiProyek.has(el.dataset.kontributor))?'':'none';
});
document.querySelectorAll('[data-proyek].lap-klik').forEach(function(el){
el.style.display=(!fKontributor||proyekOrang.has(el.dataset.proyek))?'':'none';
});

let n=0;
document.querySelectorAll('tr[data-row]').forEach(function(tr){
const d=tr.dataset;
const ok=(!fProyek||d.proyek===fProyek)&&(!fKontributor||d.kontributor===fKontributor)
&&(!fStatus||d.status===fStatus)&&(!fPrioritas||d.prioritas===fPrioritas)
&&(!cari||(d.cari||'').indexOf(cari)!==-1);
tr.dataset.filterOff=ok?'':'1';
if(ok)n++;
});
if(kartu&&kartu.inaaiTable)kartu.inaaiTable.segarkan();

const bagian=[];
if(fProyek)bagian.push('proyek '+fProyek);
if(fKontributor)bagian.push('kontributor '+fKontributor);
if(fStatus)bagian.push('status '+fStatus);
if(fPrioritas)bagian.push('prioritas '+fPrioritas);
if(cari)bagian.push('kata "'+cari+'"');
info.textContent=bagian.length?(n+' tugas · disaring: '+bagian.join(' & ')):'Seluruh tugas pada rentang yang dipilih.';
}

/* Peta relasi dari baris tabel, dipakai untuk menyaring kartu satunya. */
let orangDiProyek=new Set(),proyekOrang=new Set();
function hitungRelasi(){
orangDiProyek=new Set();proyekOrang=new Set();
document.querySelectorAll('tr[data-row]').forEach(function(tr){
if(!fProyek||tr.dataset.proyek===fProyek)orangDiProyek.add(tr.dataset.kontributor);
if(!fKontributor||tr.dataset.kontributor===fKontributor)proyekOrang.add(tr.dataset.proyek);
});
}

function setSelect(id,nilai){
const el=document.getElementById(id);
if(!el)return;
el.value=[...el.options].some(o=>o.value===nilai)?nilai:'';
if(el.inaaiSel&&el.inaaiSel.segarkan)el.inaaiSel.segarkan();
}

document.querySelectorAll('.lap-klik').forEach(function(el){
function pilih(){
if(el.dataset.proyek!==undefined){
fProyek=fProyek===el.dataset.proyek?'':el.dataset.proyek;
setSelect('lpProyek',fProyek);
}else fKontributor=fKontributor===el.dataset.kontributor?'':el.dataset.kontributor;
hitungRelasi();terapkan();
}
el.addEventListener('click',pilih);
el.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();pilih()}});
});

const inCari=document.getElementById('lpCari');
if(inCari)inCari.addEventListener('input',function(){cari=inCari.value.trim().toLowerCase();terapkan()});
[['lpProyek',v=>fProyek=v],['lpStatus',v=>fStatus=v],['lpPrioritas',v=>fPrioritas=v]].forEach(function([id,set]){
const el=document.getElementById(id);
if(el)el.addEventListener('change',function(){set(el.value);hitungRelasi();terapkan()});
});
const bReset=document.getElementById('lpReset');
if(bReset)bReset.addEventListener('click',function(){
cari='';fProyek='';fKontributor='';fStatus='';fPrioritas='';
if(inCari)inCari.value='';
['lpProyek','lpStatus','lpPrioritas'].forEach(id=>setSelect(id,''));
hitungRelasi();terapkan();
});

document.querySelectorAll('[data-lihat]').forEach(b=>b.addEventListener('click',()=>window.InaaiDrawerTugas.buka(b.dataset.lihat)));
document.querySelectorAll('[data-hapus]').forEach(b=>b.addEventListener('click',()=>window.InaaiDrawerTugas.hapus(b.dataset.hapus,b.dataset.judul||'')));

hitungRelasi();terapkan();
})();
</script>
@endpush

@push('style')
<style>
.lap-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}
@media(max-width:1000px){.lap-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="grid-kpi">
<div class="kpi"><div class="kpi-l">Total Tugas</div><div class="kpi-v">{{ $ringkas['total'] }}</div><div class="kpi-s">pada rentang terpilih</div></div>
<div class="kpi"><div class="kpi-l">Selesai</div><div class="kpi-v">{{ $ringkas['done'] }}</div><div class="kpi-s">{{ $ringkas['pct'] }}% dari total</div></div>
<div class="kpi"><div class="kpi-l">Sedang Dikerjakan</div><div class="kpi-v">{{ $ringkas['progress'] }}</div><div class="kpi-s">termasuk review</div></div>
<div class="kpi"><div class="kpi-l">To Do</div><div class="kpi-v">{{ $ringkas['todo'] }}</div><div class="kpi-s">belum dimulai</div></div>
</div>

<div class="lap-grid">
<div class="card">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg></span>Overview per Proyek</div>
<div class="card-desc">Bar penuh = seluruh tugas proyek itu; tiap warna adalah porsi statusnya. Klik satu baris untuk menyaring kartu lain dan rincian tugas.</div>
</div>
<div class="card-body">
<div class="legend">
@foreach(\App\Models\Task::daftarStatusSelesaiDulu() as $k => $lbl)
<span><i style="background:{{ \App\Models\Task::titikStatus($k) }}"></i>{{ $lbl }}</span>
@endforeach
</div>
@forelse($perProyek as $p)
<div class="ov-row lap-klik" data-proyek="{{ $p['nama'] }}" role="button" tabindex="0" title="Saring berdasarkan {{ $p['nama'] }}">
<div class="ov-nama">
<i class="ov-dot" style="background:{{ $p['warna'] }}"></i>
<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p['nama'] }}</span>
</div>
<div class="stack">
@foreach($p['segmen'] ?? [] as $sg)
@if($sg['w'] > 0)<i style="width:{{ $sg['w'] }}%;background:{{ $sg['warna'] }}" title="{{ $sg['label'] }}: {{ $sg['jumlah'] }}"></i>@endif
@endforeach
</div>
<div class="ov-meta">{{ $p['total'] }} tugas · {{ $p['pct'] }}%</div>
<span></span>
</div>
@empty
<div class="empty">Tidak ada data pada rentang ini.</div>
@endforelse
</div>
<div class="card-foot">Bar penuh = seluruh tugas proyek itu; tiap warna adalah porsi statusnya.</div>
</div>

<div class="card">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg></span>Overview per User</div>
<div class="card-desc">Bar penuh = seluruh tugas kontributor itu, diurutkan dari beban tertinggi. Klik satu baris untuk menyaring kartu lain dan rincian tugas.</div>
</div>
<div class="card-body">
<div class="legend">
@foreach(\App\Models\Task::daftarStatusSelesaiDulu() as $k => $lbl)
<span><i style="background:{{ \App\Models\Task::titikStatus($k) }}"></i>{{ $lbl }}</span>
@endforeach
</div>
@forelse($perUser as $u)
<div class="ov-row lap-klik" data-kontributor="{{ $u['nama'] }}" role="button" tabindex="0" title="Saring berdasarkan {{ $u['nama'] }}">
<div class="ov-nama">
<span class="avatar" style="width:24px;height:24px;font-size:10px">{{ $u['inisial'] }}</span>
<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u['nama'] }}</span>
</div>
<div class="stack">
@foreach($u['segmen'] ?? [] as $sg)
@if($sg['w'] > 0)<i style="width:{{ $sg['w'] }}%;background:{{ $sg['warna'] }}" title="{{ $sg['label'] }}: {{ $sg['jumlah'] }}"></i>@endif
@endforeach
</div>
<div class="ov-meta">{{ $u['total'] }} tugas · {{ $u['pct'] }}%</div>
<span></span>
</div>
@empty
<div class="empty">Tidak ada data pada rentang ini.</div>
@endforelse
</div>
<div class="card-foot">Diurutkan dari kontributor dengan tugas terbanyak.</div>
</div>
</div>

<div class="card" style="margin-top:14px" data-table>
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M3 9h18M3 15h18M9 3v18"/></svg></span>Rincian Tugas</div>
<div class="card-desc" id="lapInfo"></div>
</div>
<div class="dp-bar">
<label class="dp-cari">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
<input type="search" id="lpCari" placeholder="Cari judul, proyek, atau pemilik…" aria-label="Cari tugas">
</label>
<div class="dp-sel"><select id="lpProyek" data-select data-placeholder="Semua proyek">
<option value="">Semua proyek</option>
@foreach($perProyek as $p)<option value="{{ $p['nama'] }}" data-color="{{ $p['warna'] }}">{{ $p['nama'] }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="lpStatus" data-select data-placeholder="Semua status">
<option value="">Semua status</option>
@foreach(\App\Models\Task::daftarStatus() as $k => $lbl)<option value="{{ $k }}" data-color="{{ \App\Models\Task::titikStatus($k) }}">{{ $lbl }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="lpPrioritas" data-select data-placeholder="Semua prioritas">
<option value="">Semua prioritas</option>
@foreach(\App\Models\Task::daftarPrioritas() as $pr => $warna)<option value="{{ $pr }}" data-color="{{ $warna }}">{{ $pr }}</option>@endforeach
</select></div>
<button type="button" class="btn btn-sm" id="lpReset">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
Reset
</button>
</div>
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
@include('partials.th-sort', ['label' => 'Judul', 'ikon' => '<path d="M4 6h16M4 12h10M4 18h7"/>'])
@include('partials.th-sort', ['label' => 'Proyek', 'ikon' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>'])
@include('partials.th-sort', ['label' => 'Pemilik', 'ikon' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'])
@include('partials.th-sort', ['label' => 'Status', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>'])
@include('partials.th-sort', ['label' => 'Dibuat', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'])
<th style="width:112px">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($tasks as $t)
@php($w = \App\Models\Task::warnaStatus($t->status))
<tr data-row data-proyek="{{ $t->project?->nama }}" data-kontributor="{{ $t->user?->name }}"
    data-status="{{ $t->status }}" data-prioritas="{{ $t->prioritas }}"
    data-cari="{{ Str::lower($t->judul . ' ' . ($t->project?->nama ?? '') . ' ' . ($t->user?->name ?? '')) }}">
<td data-no></td>
<td data-sort="{{ $t->judul }}"><strong class="potong" style="--w:290px" title="{{ $t->judul }}">{{ $t->judul }}</strong></td>
<td data-sort="{{ $t->project?->nama }}"><span class="potong" style="--w:170px">{{ $t->project?->nama ?? '–' }}</span></td>
<td data-sort="{{ $t->user?->name }}"><span class="potong" style="--w:150px">{{ $t->user?->name ?? '–' }}</span></td>
<td data-sort="{{ $t->status }}"><span class="badge" style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">{{ $t->statusLabel() }}</span></td>
<td data-sort="{{ $t->created_at?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $t->created_at?->translatedFormat('d M y, H:i') }}</td>
<td>
<div class="aksi">
<button type="button" class="ico-btn" data-lihat="{{ $t->id }}" title="Lihat detail" aria-label="Lihat detail">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
</button>
<a class="ico-btn" href="{{ route('admin.proyek.show', $t->project_id) }}" title="Buka proyek" aria-label="Buka proyek">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M9 7h8v8"/></svg>
</a>
<button type="button" class="ico-btn" data-hapus="{{ $t->id }}" data-judul="{{ $t->judul }}" title="Hapus tugas" aria-label="Hapus tugas">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
</button>
</div>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div data-empty class="kosong" style="display:none">
<span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M3 9h18M9 9v12"/></svg></span>
<span class="kosong-t">Tidak ada tugas</span>
<span class="kosong-s">Tidak ada tugas pada rentang atau filter saat ini.</span>
</div>
@include('partials.pager')
</div>
@endsection
