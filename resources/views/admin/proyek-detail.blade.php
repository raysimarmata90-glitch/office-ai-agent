@extends('layouts.admin')
@section('title', $project->nama)
@section('page-title', $project->nama)
@section('page-sub', 'Detail proyek dan daftar tugas per status')

@section('topbar-actions')
<a href="{{ route('admin.proyek.index') }}" class="btn">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7M19 12H5"/></svg>
Kembali
</a>
@endsection

@push('style')
<style>
.det-grid{display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px}
.info-row{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--line3);font-size:13px}
.info-row:last-child{border-bottom:none}
.info-k{color:var(--muted2)}
.kontrib{display:flex;align-items:center;gap:9px;padding:8px 0;border-bottom:1px solid var(--line3)}
.kontrib:last-child{border-bottom:none}
.status-blok{margin-bottom:14px}
.status-head{display:flex;align-items:center;gap:9px;margin-bottom:9px}
@media(max-width:900px){.det-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="grid-kpi" style="margin-bottom:14px">
<div class="kpi"><div class="kpi-l">Total Tugas</div><div class="kpi-v">{{ $ringkas['total'] }}</div><div class="kpi-s">{{ $ringkas['pct'] }}% selesai</div></div>
<div class="kpi"><div class="kpi-l">Selesai</div><div class="kpi-v">{{ $ringkas['done'] }}</div><div class="kpi-s">dari {{ $ringkas['total'] }} tugas</div></div>
<div class="kpi"><div class="kpi-l">Sedang Dikerjakan</div><div class="kpi-v">{{ $ringkas['progress'] }}</div><div class="kpi-s">termasuk review</div></div>
<div class="kpi"><div class="kpi-l">To Do</div><div class="kpi-v">{{ $ringkas['todo'] }}</div><div class="kpi-s">belum dimulai</div></div>
</div>

<div class="det-grid">
<div class="card">
<div class="card-head"><div class="card-title">Ringkasan Proyek</div></div>
<div class="card-body">
<div class="stack" style="height:11px;margin-bottom:14px">
<i style="width:{{ $ringkas['pctDone'] }}%;background:var(--st-done)"></i>
<i style="width:{{ $ringkas['pctProgress'] }}%;background:var(--st-prog)"></i>
<i style="width:{{ $ringkas['pctTodo'] }}%;background:var(--st-todo-bg)"></i>
</div>
<div class="info-row"><span class="info-k">Nama Proyek</span><strong>{{ $project->nama }}</strong></div>
<div class="info-row"><span class="info-k">Periode</span><span>{{ $project->mulai?->format('d/m/y') ?? '–' }} – {{ $project->selesai?->format('d/m/y') ?? '–' }}</span></div>
<div class="info-row"><span class="info-k">Status</span>
<span>@if($project->berisiko)<span class="badge b-risk">Berisiko</span>@else<span class="badge b-done">Sehat</span>@endif</span>
</div>
<div class="info-row"><span class="info-k">Kontributor</span><span>{{ $kontributor->count() }} orang</span></div>
<div class="info-row"><span class="info-k">Dibuat</span><span>{{ $project->created_at?->format('d/m/y, H:i') }}</span></div>
@if($project->deskripsi)
<div style="margin-top:12px;font-size:13px;color:var(--muted);line-height:1.6">{{ $project->deskripsi }}</div>
@endif
</div>
</div>

<div class="card">
<div class="card-head"><div class="card-title">Kontributor</div></div>
<div class="card-body">
@forelse($kontributor as $k)
<div class="kontrib">
<span class="avatar">{{ $k['user']?->inisial() }}</span>
<div style="flex:1;min-width:0">
<div style="font-size:13px;font-weight:600">{{ $k['user']?->name }}</div>
<div class="stack" style="height:6px;margin-top:5px">
<i style="width:{{ $k['ringkas']['pctDone'] }}%;background:var(--st-done)"></i>
<i style="width:{{ $k['ringkas']['pctProgress'] }}%;background:var(--st-prog)"></i>
<i style="width:{{ $k['ringkas']['pctTodo'] }}%;background:var(--st-todo-bg)"></i>
</div>
</div>
<span style="font-size:11.5px;color:var(--muted2);white-space:nowrap">{{ $k['total'] }} · {{ $k['pct'] }}%</span>
</div>
@empty
<div class="empty">Belum ada kontributor.</div>
@endforelse
</div>
</div>
</div>

{{-- Daftar tugas: kanban / tabel, gaya sama dengan halaman Pekerjaan Saya --}}
<div class="card" style="margin-top:14px" data-table id="tugasCard">
<div class="card-head tl-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M3 9h18M3 15h18M9 3v18"/></svg></span>Daftar Tugas</div>
<div class="tl-filter" id="viewSwitch" role="group" aria-label="Tampilan daftar tugas">
<button type="button" class="tl-f on" data-view="kanban">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="6" height="16" x="4" y="4" rx="1"/><rect width="6" height="10" x="14" y="4" rx="1"/></svg>
Kanban
</button>
<button type="button" class="tl-f" data-view="tabel">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
Table
</button>
</div>
</div>

<div class="card-body" data-view-panel="kanban">
<div class="kanban" data-kanban data-kanban-url="/tasks/__ID__/status">
@foreach($kanban as $col)
<div class="kcol" data-status="{{ $col['key'] }}">
<div class="kcol-h">
<span class="kcol-n">
<i class="kdot" style="background:{{ \App\Models\Task::titikStatus($col['key']) }}"></i>
{{ $col['nama'] }}
</span>
<span class="kcol-c">{{ $col['count'] }}</span>
</div>
<div class="kcol-body">
@forelse($col['items'] as $t)
@php($pr = \App\Models\Task::warnaPrioritas($t->prioritas))
<div class="ktask" draggable="true" data-id="{{ $t->id }}" data-status="{{ $t->status }}">
<div class="ktask-top">
<div class="ktask-j">{{ $t->judul }}</div>
<button type="button" class="ico-btn xs" data-lihat="{{ $t->id }}" title="Lihat detail" aria-label="Lihat detail">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
</button>
<button type="button" class="ico-btn xs" data-ubah="{{ $t->id }}" title="Ubah tugas" aria-label="Ubah tugas">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
</button>
<button type="button" class="ico-btn xs" data-hapus="{{ $t->id }}" data-judul="{{ $t->judul }}" title="Hapus tugas" aria-label="Hapus tugas">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
</button>
</div>
<div class="ktask-tag">
<span class="badge" style="background:{{ $pr['bg'] }};color:{{ $pr['text'] }}">{{ $t->prioritas }}</span>
@if($t->reviewer)
@php($wr = $t->reviewer->warnaAvatar())
<span class="ktask-rev" title="Reviewer: {{ $t->reviewer->name }}">
<span class="avatar xs" style="background:{{ $wr['bg'] }};color:{{ $wr['text'] }}">{{ $t->reviewer->inisial() }}</span>
</span>
@endif
</div>
<div class="ktask-m"><span class="ktask-p">{{ $t->user?->name ?? '–' }}</span></div>
<div class="ktask-ev">
<span>{{ $t->evidences->count() }} evidence</span>
<span class="ktask-w" title="Terakhir diperbarui">
<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
{{ $t->waktuSingkat() }}
</span>
</div>
</div>
@empty
<div class="kosong">
<span class="kosong-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 12h6"/></svg></span>
<span class="kosong-t">Kosong</span>
</div>
@endforelse
</div>
</div>
@endforeach
</div>
</div>

<div data-view-panel="tabel" style="display:none">
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
@include('partials.th-sort', ['label' => 'Judul', 'ikon' => '<path d="M4 6h16M4 12h10M4 18h7"/>'])
@include('partials.th-sort', ['label' => 'Pemilik', 'ikon' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'])
@include('partials.th-sort', ['label' => 'Status', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>'])
@include('partials.th-sort', ['label' => 'Prioritas', 'ikon' => '<path d="m12 2 2.4 7.4H22l-6 4.5 2.3 7.1-6.3-4.6-6.3 4.6L7.9 14 2 9.4h7.6Z"/>'])
@include('partials.th-sort', ['label' => 'Reviewer', 'ikon' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'])
@include('partials.th-sort', ['label' => '', 'judul' => 'Evidence', 'ikon' => '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>'])
@include('partials.th-sort', ['label' => 'Dibuat', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'])
<th style="width:112px">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($project->tasks as $t)
@php($w = \App\Models\Task::warnaStatus($t->status))
@php($pr = \App\Models\Task::warnaPrioritas($t->prioritas))
<tr data-row>
<td data-no></td>
<td data-sort="{{ $t->judul }}"><strong class="potong" style="--w:290px" title="{{ $t->judul }}">{{ $t->judul }}</strong></td>
<td data-sort="{{ $t->user?->name }}"><span class="potong" style="--w:150px">{{ $t->user?->name ?? '–' }}</span></td>
<td data-sort="{{ $t->status }}"><span class="badge" style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">{{ $t->statusLabel() }}</span></td>
<td data-sort="{{ $t->prioritas }}"><span class="badge" style="background:{{ $pr['bg'] }};color:{{ $pr['text'] }}">{{ $t->prioritas }}</span></td>
<td data-sort="{{ $t->reviewer?->name }}">
@if($t->reviewer)
@php($wr = $t->reviewer->warnaAvatar())
<div style="display:flex;align-items:center;gap:7px">
<span class="avatar xs" style="background:{{ $wr['bg'] }};color:{{ $wr['text'] }}">{{ $t->reviewer->inisial() }}</span>
<span class="potong" style="--w:130px">{{ $t->reviewer->name }}</span>
</div>
@else
<span style="color:var(--muted3)">–</span>
@endif
</td>
<td data-sort="{{ $t->evidences->count() }}" style="white-space:nowrap">
@if($t->evidences->count()){{ $t->evidences->count() }} file{{ $t->evidences->count() > 1 ? 's' : '' }}@else<span style="color:var(--muted3)">–</span>@endif
</td>
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
<span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M3 9h18M9 9v12"/></svg></span>
<span class="kosong-t">Belum ada tugas</span>
</div>
@include('partials.pager')
</div>
</div>

@push('script')
<script>
(function(){
/* Ringkasan dan kontributor ikut berubah setelah kartu dipindah. */
document.querySelector('[data-kanban]').addEventListener('inaai:kanban-pindah',function(){setTimeout(()=>location.reload(),650)});
const sw=document.getElementById('viewSwitch');
if(!sw)return;
const KUNCI='inaai_proyek_view';
function pakai(nama){
sw.querySelectorAll('[data-view]').forEach(b=>b.classList.toggle('on',b.dataset.view===nama));
document.querySelectorAll('[data-view-panel]').forEach(p=>{p.style.display=p.dataset.viewPanel===nama?'':'none'});
try{localStorage.setItem(KUNCI,nama)}catch(e){}
}
let awal='kanban';
try{const v=localStorage.getItem(KUNCI);if(v==='kanban'||v==='tabel')awal=v}catch(e){}
pakai(awal);
sw.addEventListener('click',function(e){const b=e.target.closest('[data-view]');if(b)pakai(b.dataset.view)});

document.querySelectorAll('[data-lihat]').forEach(b=>b.addEventListener('click',()=>window.InaaiDrawerTugas.buka(b.dataset.lihat)));
document.querySelectorAll('[data-ubah]').forEach(b=>b.addEventListener('click',()=>window.InaaiFormTugas.ubah(b.dataset.ubah)));
document.querySelectorAll('[data-hapus]').forEach(b=>b.addEventListener('click',()=>window.InaaiDrawerTugas.hapus(b.dataset.hapus,b.dataset.judul||'')));
})();
</script>
@endpush

{{-- Modal ubah tugas dan drawer detail dipakai tombol aksi di kanban maupun tabel. --}}
@include('partials.modal-assign')
@include('partials.drawer-tugas')
@endsection
