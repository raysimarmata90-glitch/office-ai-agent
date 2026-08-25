@extends('layouts.admin')
@section('title', 'Tim')
@section('page-title', 'Tim')
@section('page-sub', $users->count() . ' pengguna terdaftar')

@section('topbar-actions')
<div style="display:flex;border:1px solid var(--line2);border-radius:9px;overflow:hidden">
<a href="{{ route('admin.users', ['view' => 'card']) }}" class="btn btn-sm" style="border:none;border-radius:0;{{ $view === 'card' ? 'background:var(--primary);color:#fff' : '' }}">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
Card
</a>
<a href="{{ route('admin.users', ['view' => 'table']) }}" class="btn btn-sm" style="border:none;border-radius:0;border-left:1px solid var(--line2);{{ $view === 'table' ? 'background:var(--primary);color:#fff' : '' }}">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
Table
</a>
</div>
<button class="btn btn-primary" type="button" data-open-assign>
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
Assign Tugas
</button>
@endsection

@push('style')
<style>
.cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.ucard{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px}
.ucard-h{display:flex;align-items:center;gap:11px;margin-bottom:13px}
.ucard-n{font-size:14px;font-weight:700}
.ucard-e{font-size:12px;color:var(--muted2)}
.ucard-r{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:12px}
.ucard-s{display:flex;justify-content:space-between;font-size:11.5px;color:var(--muted2);margin-top:7px}
.mini{display:flex;gap:5px;font-size:11px;color:var(--muted2);margin-top:6px;flex-wrap:wrap}
.mini span{display:flex;align-items:center;gap:4px}
.mini i{width:8px;height:8px;border-radius:2px;display:block}
</style>
@endpush

@section('content')
@if($view === 'card')
<div class="cards">
@foreach($users as $u)
<div class="ucard">
<div class="ucard-h">
<span class="avatar" style="width:40px;height:40px;font-size:14px;border-radius:11px">{{ $u['inisial'] }}</span>
<div style="min-width:0;flex:1">
<div class="ucard-n">{{ $u['nama'] }}</div>
<div class="ucard-e">{{ $u['email'] }}</div>
</div>
</div>
<div class="ucard-r">
<span class="badge b-todo">{{ $u['role'] }}</span>
<span class="badge b-rev">{{ $u['departemen'] }}</span>
@if($u['aktif'])<span class="badge b-done">Aktif</span>@else<span class="badge b-blok">Nonaktif</span>@endif
</div>
<div class="stack">
<i style="width:{{ $u['ringkas']['pctDone'] }}%;background:#1f7a52"></i>
<i style="width:{{ $u['ringkas']['pctProgress'] }}%;background:#f5a273"></i>
<i style="width:{{ $u['ringkas']['pctTodo'] }}%;background:#eef0f6"></i>
</div>
<div class="mini">
<span><i style="background:#1f7a52"></i>Selesai {{ $u['ringkas']['done'] }}</span>
<span><i style="background:#f5a273"></i>Proses {{ $u['ringkas']['progress'] }}</span>
<span><i style="background:#eef0f6"></i>To Do {{ $u['ringkas']['todo'] }}</span>
</div>
<div class="ucard-s">
<span>{{ $u['ringkas']['total'] }} tugas</span>
<span>{{ $u['ringkas']['pct'] }}% selesai</span>
</div>
<div style="display:flex;gap:7px;margin-top:13px">
<form method="POST" action="{{ route('admin.users.toggle-status', $u['id']) }}" style="flex:1">@csrf
<button type="submit" class="btn btn-sm" style="width:100%;justify-content:center">
{{ $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}
</button>
</form>
</div>
</div>
@endforeach
</div>
@else
<div class="card" data-table>
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
@include('partials.th-sort', ['label' => 'Nama'])
@include('partials.th-sort', ['label' => 'Departemen'])
@include('partials.th-sort', ['label' => 'Tugas'])
@include('partials.th-sort', ['label' => 'Progress'])
@include('partials.th-sort', ['label' => 'Status'])
@include('partials.th-sort', ['label' => 'Dibuat'])
<th style="width:110px">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($users as $u)
<tr data-row>
<td data-no></td>
<td data-sort="{{ $u['nama'] }}">
<div style="display:flex;align-items:center;gap:9px">
<span class="avatar">{{ $u['inisial'] }}</span>
<div style="min-width:0">
<div style="font-weight:600">{{ $u['nama'] }}</div>
<div style="font-size:11.5px;color:var(--muted2)">{{ $u['email'] }}</div>
</div>
</div>
</td>
<td style="color:var(--muted)">{{ $u['departemen'] }}</td>
<td data-sort="{{ $u['ringkas']['total'] }}">{{ $u['ringkas']['total'] }}</td>
<td data-sort="{{ $u['ringkas']['pct'] }}" style="min-width:190px">
<div style="display:flex;align-items:center;gap:9px">
<div class="stack" style="flex:1">
<i style="width:{{ $u['ringkas']['pctDone'] }}%;background:#1f7a52"></i>
<i style="width:{{ $u['ringkas']['pctProgress'] }}%;background:#f5a273"></i>
<i style="width:{{ $u['ringkas']['pctTodo'] }}%;background:#eef0f6"></i>
</div>
<span style="font-size:11.5px;color:var(--muted2);white-space:nowrap">{{ $u['ringkas']['pct'] }}%</span>
</div>
</td>
<td data-sort="{{ $u['aktif'] ? 1 : 0 }}">
@if($u['aktif'])<span class="badge b-done">Aktif</span>@else<span class="badge b-blok">Nonaktif</span>@endif
</td>
<td data-sort="{{ $u['dibuat']?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $u['dibuat']?->format('d/m/y, H:i') }}</td>
<td>
<form method="POST" action="{{ route('admin.users.toggle-status', $u['id']) }}">@csrf
<button type="submit" class="btn btn-sm">{{ $u['aktif'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
</form>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div data-empty class="empty" style="display:none">Belum ada pengguna.</div>
@include('partials.pager')
</div>
@endif

@include('partials.modal-assign')
@endsection
