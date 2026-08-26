@extends('layouts.admin')
@section('title', 'Percakapan')
@section('page-title', 'Percakapan')
@section('page-sub', $ringkas['total'] . ' percakapan · ' . $ringkas['pesan'] . ' pesan')

@section('content')
<div class="grid-kpi">
@php($kpi = [
    ['Total Percakapan', $ringkas['total'], 'netral', '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>'],
    ['Sedang Berjalan', $ringkas['aktif'], 'prog', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
    ['Selesai', $ringkas['selesai'], 'done', '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>'],
    ['Total Pesan', $ringkas['pesan'], 'todo', '<path d="M4 6h16M4 12h10M4 18h7"/>'],
])
@foreach($kpi as [$label, $nilai, $warna, $ikon])
<div class="kpi">
<div class="kpi-h">
<span class="kpi-ico {{ $warna }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg></span>
<div class="kpi-l">{{ $label }}</div>
</div>
<div class="kpi-v">{{ $nilai }}</div>
</div>
@endforeach
</div>

<div class="card" style="margin-top:14px">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></span>Daftar Percakapan</div>
<div class="card-desc">Telusuri percakapan pengguna dengan agent, lalu buka transkrip lengkapnya.</div>
</div>

<form method="GET" class="dp-bar">
<input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
<label class="dp-cari">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
<input type="search" name="q" value="{{ $cari }}" placeholder="Cari judul, nama, atau email pengguna…" aria-label="Cari percakapan">
</label>
<div class="dp-sel"><select name="status" data-select data-placeholder="Semua status" onchange="this.form.submit()">
<option value="">Semua status</option>
<option value="active" data-color="var(--st-prog)" @selected($status === 'active')>Sedang Berjalan</option>
<option value="completed" data-color="var(--st-done)" @selected($status === 'completed')>Selesai</option>
</select></div>
<div class="dp-sel"><select name="departemen" data-select data-placeholder="Semua departemen" onchange="this.form.submit()">
<option value="">Semua departemen</option>
@foreach($departemen as $d)<option value="{{ $d->id }}" @selected((string) $depId === (string) $d->id)>{{ $d->name }}</option>@endforeach
</select></div>
<button type="submit" class="btn btn-sm btn-primary">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
Terapkan
</button>
<a href="{{ route('admin.conversations') }}" class="btn btn-sm">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
Reset
</a>
</form>

<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:56px">No</th>
<th><span class="th-in"><svg class="th-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h10M4 18h7"/></svg>Percakapan</span></th>
<th><span class="th-in"><svg class="th-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Pengguna</span></th>
<th><span class="th-in"><svg class="th-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>Departemen</span></th>
<th><span class="th-in"><svg class="th-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>Status</span></th>
<th style="width:92px"><span class="th-in"><svg class="th-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>Pesan</span></th>
<th><span class="th-in"><svg class="th-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>Diperbarui</span></th>
<th style="width:64px">Aksi</th>
</tr>
</thead>
<tbody>
@forelse($conversations as $i => $c)
@php($aktif = $c->status === 'active')
<tr>
<td>{{ $conversations->firstItem() + $i }}</td>
<td>
<strong class="potong" style="--w:260px" title="{{ $c->title }}">{{ $c->namaProyek() }}</strong>
<span class="td-periode" title="{{ $c->judulTugas() }}">{{ \Illuminate\Support\Str::limit($c->judulTugas(), 46) }}</span>
</td>
<td>
@php($wa = $c->user ? $c->user->warnaAvatar() : ['bg' => 'var(--line3)', 'text' => 'var(--muted)'])
<div style="display:flex;align-items:center;gap:8px">
<span class="avatar xs" style="background:{{ $wa['bg'] }};color:{{ $wa['text'] }}">{{ $c->user?->inisial() ?? '?' }}</span>
<span style="min-width:0">
<span class="potong" style="--w:150px;font-weight:600">{{ $c->user?->name ?? '–' }}</span>
<span class="potong" style="--w:150px;font-size:11.5px;color:var(--muted3)">{{ $c->user?->email }}</span>
</span>
</div>
</td>
<td>
@if($c->department)
<span class="badge" style="background:{{ $c->department->color }}1f;color:{{ $c->department->color }}">{{ $c->department->name }}</span>
@else
<span style="color:var(--muted3)">–</span>
@endif
</td>
<td>
<span class="badge" style="background:{{ $aktif ? 'var(--st-prog-bg)' : 'var(--st-done-bg)' }};color:{{ $aktif ? 'var(--st-prog)' : 'var(--st-done)' }}">
{{ $aktif ? 'Sedang Berjalan' : 'Selesai' }}
</span>
</td>
<td style="white-space:nowrap">{{ $c->messages_count }} pesan</td>
<td style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $c->updated_at?->translatedFormat('d M y, H:i') }}</td>
<td>
<a class="ico-btn" href="{{ route('admin.conversation.detail', $c->id) }}" title="Lihat transkrip" aria-label="Lihat transkrip">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
</a>
</td>
</tr>
@empty
<tr><td colspan="8">
<div class="kosong">
<span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></span>
<span class="kosong-t">Belum ada percakapan</span>
<span class="kosong-s">Tidak ada percakapan yang cocok dengan pencarian atau filter saat ini.</span>
</div>
</td></tr>
@endforelse
</tbody>
</table>
</div>
@include('partials.pager-server', ['paginator' => $conversations])
</div>
@endsection

