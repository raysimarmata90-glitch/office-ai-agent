@extends('layouts.admin')
@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('page-sub', 'Rekap kinerja proyek dan kontributor')

@section('topbar-actions')
<form method="GET" action="{{ route('admin.laporan') }}" style="display:flex;align-items:center;gap:8px">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted2)"><path d="M8 2v4M16 2v4M3 10h18"/><rect width="18" height="18" x="3" y="4" rx="2"/></svg>
<select name="rentang" onchange="this.form.submit()" style="border:1px solid var(--line2);border-radius:9px;padding:8px 11px;font-size:13px;font-family:inherit;background:#fff">
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
@endsection

@push('style')
<style>
.lap-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}
.lap-row{display:grid;grid-template-columns:150px 1fr 96px;gap:12px;align-items:center;padding:9px 0;border-bottom:1px solid var(--line3)}
.lap-row:last-child{border-bottom:none}
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
<div class="card-head"><div class="card-title">Kinerja per Proyek</div></div>
<div class="card-body">
@forelse($perProyek as $p)
<div class="lap-row">
<div style="display:flex;align-items:center;gap:7px;min-width:0">
<i style="width:9px;height:9px;border-radius:3px;background:{{ $p['warna'] }};flex:none"></i>
<span style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p['nama'] }}</span>
</div>
<div class="stack">
<i style="width:{{ $p['total'] ? $p['done'] / $p['total'] * 100 : 0 }}%;background:#1f7a52"></i>
<i style="width:{{ $p['total'] ? $p['progress'] / $p['total'] * 100 : 0 }}%;background:#f5a273"></i>
<i style="width:{{ $p['total'] ? $p['todo'] / $p['total'] * 100 : 0 }}%;background:#eef0f6"></i>
</div>
<div style="font-size:11.5px;color:var(--muted2);text-align:right;white-space:nowrap">{{ $p['total'] }} · {{ $p['pct'] }}%</div>
</div>
@empty
<div class="empty">Tidak ada data pada rentang ini.</div>
@endforelse
</div>
<div class="card-foot">Bar menampilkan komposisi selesai, sedang dikerjakan, dan to do.</div>
</div>

<div class="card">
<div class="card-head"><div class="card-title">Kinerja per Kontributor</div></div>
<div class="card-body">
@forelse($perUser as $u)
<div class="lap-row">
<div style="display:flex;align-items:center;gap:7px;min-width:0">
<span class="avatar" style="width:24px;height:24px;font-size:10px">{{ $u['inisial'] }}</span>
<span style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u['nama'] }}</span>
</div>
<div class="stack">
<i style="width:{{ $u['total'] ? $u['done'] / $u['total'] * 100 : 0 }}%;background:#1f7a52"></i>
<i style="width:{{ $u['total'] ? $u['progress'] / $u['total'] * 100 : 0 }}%;background:#f5a273"></i>
<i style="width:{{ $u['total'] ? $u['todo'] / $u['total'] * 100 : 0 }}%;background:#eef0f6"></i>
</div>
<div style="font-size:11.5px;color:var(--muted2);text-align:right;white-space:nowrap">{{ $u['total'] }} · {{ $u['pct'] }}%</div>
</div>
@empty
<div class="empty">Tidak ada data pada rentang ini.</div>
@endforelse
</div>
<div class="card-foot">Diurutkan dari kontributor dengan tugas terbanyak.</div>
</div>
</div>

<div class="card" style="margin-top:14px" data-table>
<div class="card-head"><div class="card-title">Rincian Tugas</div></div>
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
@include('partials.th-sort', ['label' => 'Judul'])
@include('partials.th-sort', ['label' => 'Proyek'])
@include('partials.th-sort', ['label' => 'Pemilik'])
@include('partials.th-sort', ['label' => 'Status'])
@include('partials.th-sort', ['label' => 'Dibuat'])
</tr>
</thead>
<tbody>
@foreach($tasks as $t)
@php($w = \App\Models\Task::warnaStatus($t->status))
<tr data-row>
<td data-no></td>
<td data-sort="{{ $t->judul }}">{{ $t->judul }}</td>
<td data-sort="{{ $t->project?->nama }}">{{ $t->project?->nama ?? '–' }}</td>
<td data-sort="{{ $t->user?->name }}">{{ $t->user?->name ?? '–' }}</td>
<td data-sort="{{ $t->status }}"><span class="badge" style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">{{ $t->statusLabel() }}</span></td>
<td data-sort="{{ $t->created_at?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $t->created_at?->format('d/m/y, H:i') }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div data-empty class="empty" style="display:none">Tidak ada tugas pada rentang ini.</div>
@include('partials.pager')
</div>
@endsection
