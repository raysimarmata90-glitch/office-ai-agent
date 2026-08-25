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
<div class="card" data-table>
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
@include('partials.th-sort', ['label' => 'Judul'])
@include('partials.th-sort', ['label' => 'Proyek'])
@include('partials.th-sort', ['label' => 'Pemilik'])
@include('partials.th-sort', ['label' => 'Reviewer'])
@include('partials.th-sort', ['label' => 'Prioritas'])
@include('partials.th-sort', ['label' => 'Periode'])
@include('partials.th-sort', ['label' => 'Status'])
@include('partials.th-sort', ['label' => 'Dibuat'])
</tr>
</thead>
<tbody>
@foreach($tasks as $t)
@php($w = \App\Models\Task::warnaStatus($t->status))
<tr data-row>
<td data-no></td>
<td data-sort="{{ $t->judul }}"><strong>{{ $t->judul }}</strong></td>
<td data-sort="{{ $t->project?->nama }}">
<div style="display:flex;align-items:center;gap:7px">
<i style="width:8px;height:8px;border-radius:2px;background:{{ $t->project?->warna ?? '#f55d14' }};flex:none"></i>
{{ $t->project?->nama ?? '–' }}
</div>
</td>
<td data-sort="{{ $t->user?->name }}">{{ $t->user?->name ?? '–' }}</td>
<td data-sort="{{ $t->reviewer?->name }}">{{ $t->reviewer?->name ?? '–' }}</td>
<td data-sort="{{ $t->prioritas }}">{{ $t->prioritas }}</td>
<td data-sort="{{ $t->mulai?->timestamp }}" style="white-space:nowrap;color:var(--muted)">{{ $t->mulai?->format('d/m/y') }} – {{ $t->selesai?->format('d/m/y') }}</td>
<td data-sort="{{ $t->status }}"><span class="badge" style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">{{ $t->statusLabel() }}</span></td>
<td data-sort="{{ $t->created_at?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $t->created_at?->format('d/m/y, H:i') }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div data-empty class="empty" style="display:none">Belum ada tugas.</div>
@include('partials.pager')
</div>

@include('partials.modal-assign')
@endsection
