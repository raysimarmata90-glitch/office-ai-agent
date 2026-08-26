@extends('layouts.admin')
@section('title', 'Transkrip Percakapan')
@section('page-title', $conversation->namaProyek())
@section('page-sub', ($conversation->user?->name ?? '–') . ' · ' . $conversation->created_at->translatedFormat('d F Y, H:i'))

@section('topbar-actions')
<a href="{{ route('admin.conversations') }}" class="btn">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
Kembali
</a>
@endsection

@section('content')
@php($aktif = $conversation->status === 'active')

{{-- Satu kartu berisi identitas percakapan, tugas yang dihasilkan, lalu angka ringkasnya --}}
<div class="card">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Informasi Percakapan</div>
<div class="card-desc">Identitas percakapan, tugas yang dihasilkannya, dan ringkasan angkanya.</div>
</div>
<div class="card-body">
<div class="drw-row"><div class="drw-k">Pengguna</div><div class="drw-v">
@php($wa = $conversation->user ? $conversation->user->warnaAvatar() : ['bg' => 'var(--line3)', 'text' => 'var(--muted)'])
<span style="display:inline-flex;align-items:center;gap:8px">
<span class="avatar xs" style="background:{{ $wa['bg'] }};color:{{ $wa['text'] }}">{{ $conversation->user?->inisial() ?? '?' }}</span>
{{ $conversation->user?->name ?? '–' }} · {{ $conversation->user?->email }}
</span>
</div></div>
<div class="drw-row"><div class="drw-k">Departemen</div><div class="drw-v">
@if($conversation->department)
<span class="badge" style="background:{{ $conversation->department->color }}1f;color:{{ $conversation->department->color }}">{{ $conversation->department->name }}</span>
@else – @endif
</div></div>
<div class="drw-row"><div class="drw-k">Status</div><div class="drw-v">
<span class="badge" style="background:{{ $aktif ? 'var(--st-prog-bg)' : 'var(--st-done-bg)' }};color:{{ $aktif ? 'var(--st-prog)' : 'var(--st-done)' }}">{{ $aktif ? 'Sedang Berjalan' : 'Selesai' }}</span>
</div></div>
<div class="drw-row"><div class="drw-k">Judul</div><div class="drw-v">{{ $conversation->title }}</div></div>
<div class="drw-row"><div class="drw-k">Dimulai</div><div class="drw-v">{{ $conversation->created_at?->translatedFormat('d F Y, H:i') }}</div></div>
<div class="drw-row"><div class="drw-k">Diperbarui</div><div class="drw-v">{{ $conversation->updated_at?->translatedFormat('d F Y, H:i') }}</div></div>

<div class="drw-sec">Tugas yang Dihasilkan <span class="drw-n">{{ $tugas->count() }}</span></div>
@if($tugas->count())
<div class="drw-ev">
@foreach($tugas as $t)
@php($w = \App\Models\Task::warnaStatus($t->status))
@php($pr = \App\Models\Task::warnaPrioritas($t->prioritas))
<div class="drw-evi">
<span class="i-up-fi" style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18M9 16l2 2 4-4"/></svg>
</span>
<span class="i-up-meta">
<span class="i-up-fn" title="{{ $t->judul }}">{{ $t->judul }}</span>
<span class="i-up-fs">{{ $t->project?->nama ?? 'Tanpa proyek' }} · {{ $t->mulai?->translatedFormat('d M y') }} – {{ $t->selesai?->translatedFormat('d M y') }}</span>
</span>
<span class="badge" style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">{{ $t->statusLabel() }}</span>
<span class="badge" style="background:{{ $pr['bg'] }};color:{{ $pr['text'] }}">{{ $t->prioritas }}</span>
</div>
@endforeach
</div>
@else
<div class="kosong" style="min-height:120px">
<span class="kosong-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span>
<span class="kosong-t">Belum menghasilkan tugas</span>
<span class="kosong-s">Tugas dicatat setelah percakapan diselesaikan.</span>
</div>
@endif

<div class="drw-sec">Ringkasan</div>
<div class="ringkas-grid">
@php($rk = [
    ['Total Pesan', $pesan->count(), 'netral', '<path d="M4 6h16M4 12h10M4 18h7"/>'],
    ['Jawaban Pengguna', $pesan->where('sender_type', 'user')->count(), 'prog', '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
    ['Balasan Agent', $pesan->where('sender_type', '!=', 'user')->count(), 'todo', '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>'],
    ['Tugas Tercatat', $tugas->count(), 'done', '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18M9 16l2 2 4-4"/>'],
])
@foreach($rk as [$label, $nilai, $warna, $ikon])
<div class="ringkas-item">
<span class="kpi-ico {{ $warna }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg></span>
<span class="ringkas-m"><span class="ringkas-v">{{ $nilai }}</span><span class="ringkas-l">{{ $label }}</span></span>
</div>
@endforeach
</div>
</div>
</div>

<div class="card" style="margin-top:14px">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></span>Transkrip</div>
<div class="card-desc">{{ $pesan->count() }} pesan, urut dari yang paling awal.</div>
</div>
<div class="card-body">
@if($pesan->isEmpty())
<div class="kosong">
<span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></span>
<span class="kosong-t">Belum ada pesan</span>
<span class="kosong-s">Percakapan ini belum memiliki isi.</span>
</div>
@else
<div class="trx">
@foreach($pesan as $m)
@php($dariUser = $m->sender_type === 'user')
<div class="trx-row {{ $dariUser ? 'user' : 'ai' }}">
@if($dariUser)
@php($wa = $conversation->user ? $conversation->user->warnaAvatar() : ['bg' => 'var(--line3)', 'text' => 'var(--muted)'])
<span class="trx-av" style="background:{{ $wa['bg'] }};color:{{ $wa['text'] }}">{{ $conversation->user?->inisial() ?? '?' }}</span>
@else
<span class="trx-av ai"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INAai"></span>
@endif
<div class="trx-in">
<div class="trx-nama">{{ $dariUser ? ($conversation->user?->name ?? 'Pengguna') : 'INAai Agent' }}</div>
<div class="trx-bub">{{ $m->content }}</div>
@php($opsi = $m->metadata['options'] ?? [])
@if(! $dariUser && is_array($opsi) && count($opsi))
<div class="trx-opsi">
@foreach($opsi as $o)<span>{{ $o }}</span>@endforeach
</div>
@endif
<div class="trx-t">{{ $m->created_at?->translatedFormat('d M Y, H:i') }}</div>
</div>
</div>
@endforeach
</div>
@endif
</div>
</div>
@endsection
