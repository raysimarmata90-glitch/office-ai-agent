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
<i style="width:{{ $ringkas['pctDone'] }}%;background:#1f7a52"></i>
<i style="width:{{ $ringkas['pctProgress'] }}%;background:#f5a273"></i>
<i style="width:{{ $ringkas['pctTodo'] }}%;background:#eef0f6"></i>
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
<i style="width:{{ $k['ringkas']['pctDone'] }}%;background:#1f7a52"></i>
<i style="width:{{ $k['ringkas']['pctProgress'] }}%;background:#f5a273"></i>
<i style="width:{{ $k['ringkas']['pctTodo'] }}%;background:#eef0f6"></i>
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

@foreach($kanban as $col)
@php($warna = \App\Models\Task::warnaStatus($col['key']))
<div class="card status-blok">
<div class="card-head">
<div class="status-head">
<span class="badge" style="background:{{ $warna['bg'] }};color:{{ $warna['text'] }}">{{ $col['nama'] }}</span>
<span style="font-size:12.5px;color:var(--muted2)">{{ $col['count'] }} tugas</span>
</div>
</div>
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
<th>Judul</th>
<th>Pemilik</th>
<th>Reviewer</th>
<th>Prioritas</th>
<th>Periode</th>
<th>Dibuat</th>
</tr>
</thead>
<tbody>
@forelse($col['items'] as $i => $t)
<tr>
<td>{{ $i + 1 }}</td>
<td><strong>{{ $t->judul }}</strong></td>
<td>{{ $t->user?->name ?? '–' }}</td>
<td>{{ $t->reviewer?->name ?? '–' }}</td>
<td>{{ $t->prioritas }}</td>
<td style="white-space:nowrap;color:var(--muted)">{{ $t->mulai?->format('d/m/y') }} – {{ $t->selesai?->format('d/m/y') }}</td>
<td style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $t->created_at?->format('d/m/y, H:i') }}</td>
</tr>
@empty
<tr><td colspan="7" class="empty">Tidak ada tugas pada status ini.</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
@endforeach
@endsection
