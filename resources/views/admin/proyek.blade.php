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

@section('content')
<div class="card" data-table>
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
@include('partials.th-sort', ['label' => 'Proyek'])
@include('partials.th-sort', ['label' => 'Periode'])
@include('partials.th-sort', ['label' => 'Tugas'])
@include('partials.th-sort', ['label' => 'Kontributor'])
@include('partials.th-sort', ['label' => 'Progress'])
@include('partials.th-sort', ['label' => 'Status'])
@include('partials.th-sort', ['label' => 'Dibuat'])
<th style="width:88px">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($projects as $p)
<tr data-row>
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
<i style="width:{{ $p['pct'] }}%;background:{{ $p['warna'] }}"></i>
</div>
<span style="font-size:11.5px;color:var(--muted2);white-space:nowrap">{{ $p['pct'] }}%</span>
</div>
</td>
<td data-sort="{{ $p['berisiko'] ? 1 : 0 }}">
@if($p['berisiko'])
<span class="badge b-risk">Berisiko</span>
@else
<span class="badge b-done">Sehat</span>
@endif
</td>
<td data-sort="{{ $p['dibuat']?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">
{{ $p['dibuat']?->format('d/m/y, H:i') }}
</td>
<td>
<a href="{{ route('admin.proyek.show', $p['id']) }}" class="btn btn-sm">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.87 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.87 0"/><circle cx="12" cy="12" r="3"/></svg>
View
</a>
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
