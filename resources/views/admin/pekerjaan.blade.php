@extends('layouts.admin')
@section('title', 'Tugas')
@section('page-title', 'Tugas')
@section('page-sub', $tasks->count() . ' tugas tercatat')

@section('topbar-actions')
<button class="btn btn-primary" type="button" data-open-assign>
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
Assign Tugas
</button>
@endsection

@section('content')
<div class="card" data-table id="tugasCard">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 12H3M16 6H3M16 18H3"/><path d="m18 9 3 3-3 3"/></svg></span>Daftar Tugas</div>
<div class="card-desc">Seluruh tugas di semua proyek beserta pemilik dan reviewernya.</div>
</div>
<div class="dp-bar">
<label class="dp-cari">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
<input type="search" id="tgCari" placeholder="Cari judul, proyek, pemilik, atau reviewer…" aria-label="Cari tugas">
</label>
<div class="dp-sel"><select id="tgProyek" data-select data-placeholder="Semua proyek">
<option value="">Semua proyek</option>
@foreach($projects as $p)<option value="{{ $p->nama }}" data-color="{{ $p->warna }}">{{ $p->nama }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="tgStatus" data-select data-placeholder="Semua status">
<option value="">Semua status</option>
@foreach(\App\Models\Task::daftarStatus() as $k => $lbl)<option value="{{ $k }}" data-color="{{ \App\Models\Task::titikStatus($k) }}">{{ $lbl }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="tgPrioritas" data-select data-placeholder="Semua prioritas">
<option value="">Semua prioritas</option>
@foreach(\App\Models\Task::daftarPrioritas() as $pr => $warna)<option value="{{ $pr }}" data-color="{{ $warna }}">{{ $pr }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="tgPemilik" data-select data-placeholder="Semua pemilik">
<option value="">Semua pemilik</option>
@foreach($daftarPemilik as $nama)<option value="{{ $nama }}">{{ $nama }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="tgReviewer" data-select data-placeholder="Semua reviewer">
<option value="">Semua reviewer</option>
@if($tanpaReviewer)<option value="__kosong__">Tanpa reviewer ({{ $tanpaReviewer }})</option>@endif
@foreach($daftarReviewer as $nama)<option value="{{ $nama }}">{{ $nama }}</option>@endforeach
</select></div>
<button type="button" class="btn btn-sm" id="tgReset">
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
@include('partials.th-sort', ['label' => 'Reviewer', 'ikon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'])
@include('partials.th-sort', ['label' => 'Prioritas', 'ikon' => '<path d="m12 2 2.4 7.4H22l-6 4.5 2.3 7.1-6.3-4.6-6.3 4.6L7.9 14 2 9.4h7.6Z"/>'])
@include('partials.th-sort', ['label' => 'Status', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>'])
@include('partials.th-sort', ['label' => 'Dibuat', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'])
<th style="width:112px">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($tasks as $t)
@php($w = \App\Models\Task::warnaStatus($t->status))
@php($pr = \App\Models\Task::warnaPrioritas($t->prioritas))
<tr data-row data-status="{{ $t->status }}" data-proyek="{{ $t->project?->nama }}" data-prioritas="{{ $t->prioritas }}"
    data-pemilik="{{ $t->user?->name }}" data-reviewer="{{ $t->reviewer?->name }}"
    data-cari="{{ Str::lower($t->judul . ' ' . ($t->project?->nama ?? '') . ' ' . ($t->user?->name ?? '') . ' ' . ($t->reviewer?->name ?? '')) }}">
<td data-no></td>
<td data-sort="{{ $t->judul }}">
<strong class="potong" style="--w:250px" title="{{ $t->judul }}">{{ $t->judul }}</strong>
</td>
<td data-sort="{{ $t->project?->nama }}"><span class="potong" style="--w:160px">{{ $t->project?->nama ?? '–' }}</span></td>
<td data-sort="{{ $t->user?->name }}"><span class="potong" style="--w:140px">{{ $t->user?->name ?? '–' }}</span></td>
<td data-sort="{{ $t->reviewer?->name }}"><span class="potong" style="--w:140px">{{ $t->reviewer?->name ?? '–' }}</span></td>
<td data-sort="{{ $t->prioritas }}"><span class="badge" style="background:{{ $pr['bg'] }};color:{{ $pr['text'] }}">{{ $t->prioritas }}</span></td>
<td data-sort="{{ $t->status }}"><span class="badge" style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">{{ $t->statusLabel() }}</span></td>
<td data-sort="{{ $t->created_at?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $t->created_at?->translatedFormat('d M y, H:i') }}</td>
<td>
<div class="aksi">
<button type="button" class="ico-btn" data-lihat="{{ $t->id }}" title="Lihat detail" aria-label="Lihat detail">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
</button>
<button type="button" class="ico-btn" data-ubah="{{ $t->id }}" title="Ubah tugas" aria-label="Ubah tugas">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
</button>
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
<span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span>
<span class="kosong-t">Tidak ada tugas</span>
<span class="kosong-s">Tidak ada tugas yang cocok dengan pencarian atau filter saat ini.</span>
</div>
@include('partials.pager')
</div>

@include('partials.modal-assign')
@include('partials.drawer-tugas')
@endsection

@push('script')
<script>
(function(){
const token=document.querySelector('meta[name="csrf-token"]').content;
let cari='',fProyek='',fStatus='',fPrioritas='',fPemilik='',fReviewer='';
const kartu=document.getElementById('tugasCard');
function terapkan(){
document.querySelectorAll('tr[data-row]').forEach(function(tr){
const d=tr.dataset;
let ok=true;
if(cari&&(d.cari||'').indexOf(cari)===-1)ok=false;
if(fProyek&&d.proyek!==fProyek)ok=false;
if(fStatus&&d.status!==fStatus)ok=false;
if(fPrioritas&&d.prioritas!==fPrioritas)ok=false;
if(fPemilik&&d.pemilik!==fPemilik)ok=false;
// Nilai khusus untuk tugas yang memang belum punya reviewer.
if(fReviewer==='__kosong__'){if(d.reviewer)ok=false}
else if(fReviewer&&d.reviewer!==fReviewer)ok=false;
tr.dataset.filterOff=ok?'':'1';
});
if(kartu&&kartu.inaaiTable)kartu.inaaiTable.segarkan();
}
const inCari=document.getElementById('tgCari');
if(inCari)inCari.addEventListener('input',function(){cari=inCari.value.trim().toLowerCase();terapkan()});
[['tgProyek',v=>fProyek=v],['tgStatus',v=>fStatus=v],['tgPrioritas',v=>fPrioritas=v],
['tgPemilik',v=>fPemilik=v],['tgReviewer',v=>fReviewer=v]].forEach(function([id,set]){
const el=document.getElementById(id);
if(el)el.addEventListener('change',function(){set(el.value);terapkan()});
});
const bReset=document.getElementById('tgReset');
if(bReset)bReset.addEventListener('click',function(){
cari='';fProyek='';fStatus='';fPrioritas='';fPemilik='';fReviewer='';
if(inCari)inCari.value='';
['tgProyek','tgStatus','tgPrioritas','tgPemilik','tgReviewer'].forEach(function(id){
const el=document.getElementById(id);
if(el){el.value='';if(el.inaaiSel&&el.inaaiSel.segarkan)el.inaaiSel.segarkan()}
});
terapkan();
});

document.querySelectorAll('[data-lihat]').forEach(b=>b.addEventListener('click',()=>window.InaaiDrawerTugas.buka(b.dataset.lihat,'full')));
document.querySelectorAll('[data-ubah]').forEach(b=>b.addEventListener('click',()=>window.InaaiFormTugas.ubah(b.dataset.ubah)));
document.querySelectorAll('[data-hapus]').forEach(b=>b.addEventListener('click',()=>window.InaaiDrawerTugas.hapus(b.dataset.hapus,b.dataset.judul||'')));
})();
</script>
@endpush
