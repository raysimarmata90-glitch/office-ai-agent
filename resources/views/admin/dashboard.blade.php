@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Selamat datang, ' . explode(' ', $user->name)[0])
@section('page-sub', 'Ringkasan portofolio · diperbarui ' . now()->translatedFormat('d F Y, H.i'))

@section('topbar-actions')
<a href="{{ route('admin.laporan.ekspor') }}" class="btn">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg>
Ekspor Laporan
</a>
<button class="btn btn-primary" type="button" data-open-assign>
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
Assign Tugas
</button>
@endsection

@push('style')
<style>
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}
.ov-row{display:grid;grid-template-columns:170px 1fr 92px;gap:12px;align-items:center;padding:9px 0;border-bottom:1px solid var(--line3)}
.ov-row:last-child{border-bottom:none}
.ov-nama{font-size:13px;font-weight:600;display:flex;align-items:center;gap:7px;min-width:0}
.ov-dot{width:9px;height:9px;border-radius:3px;flex:none}
.ov-meta{font-size:11.5px;color:var(--muted2);text-align:right;white-space:nowrap}
.legend{display:flex;gap:14px;font-size:11.5px;color:var(--muted2);margin-bottom:10px;flex-wrap:wrap}
.legend span{display:flex;align-items:center;gap:5px}
.legend i{width:9px;height:9px;border-radius:3px;display:block}
.kanban{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:11px}
.kcol{background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:10px;min-height:120px}
.kcol-h{display:flex;align-items:center;justify-content:space-between;font-size:12px;font-weight:700;margin-bottom:9px}
.kcol-c{background:#fff;border:1px solid var(--line2);border-radius:999px;padding:1px 8px;font-size:11px;color:var(--muted2)}
.ktask{background:#fff;border:1px solid var(--line2);border-radius:10px;padding:9px 10px;margin-bottom:8px}
.ktask-j{font-size:12.5px;font-weight:600;line-height:1.35}
.ktask-m{display:flex;align-items:center;justify-content:space-between;margin-top:7px;gap:6px}
.ktask-p{font-size:11px;color:var(--muted2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gantt-scroll{overflow-x:auto}
.gantt-head{display:grid;grid-template-columns:repeat(6,1fr);gap:0;border-bottom:1px solid var(--line);padding-bottom:7px;margin-bottom:11px;min-width:520px}
.gantt-head div{font-size:11px;color:var(--muted2);font-weight:600;text-align:center}
.gantt-row{display:grid;grid-template-columns:150px 1fr;gap:11px;align-items:center;margin-bottom:9px;min-width:520px}
.gantt-nama{font-size:12.5px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gantt-track{position:relative;height:20px;background:var(--line3);border-radius:999px}
.gantt-bar{position:absolute;top:0;height:20px;border-radius:999px;opacity:.32}
.gantt-fill{position:absolute;top:0;height:20px;border-radius:999px}
.gantt-pct{position:absolute;right:8px;top:0;height:20px;display:flex;align-items:center;font-size:10.5px;font-weight:700;color:var(--muted)}
.akt{display:flex;gap:10px;padding:9px 0;border-bottom:1px solid var(--line3)}
.akt:last-child{border-bottom:none}
.akt-t{font-size:12.5px;line-height:1.4}
.akt-w{font-size:11px;color:var(--muted3);margin-top:2px}
.risk{display:flex;align-items:center;gap:9px;background:#fde3e1;color:#b23c35;padding:10px 14px;border-radius:11px;font-size:12.5px;margin-bottom:14px}
@media(max-width:1000px){.dash-grid{grid-template-columns:1fr}.kanban{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('content')
@if($berisiko->isNotEmpty())
<div class="risk">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/></svg>
<span><strong>{{ $berisiko->count() }} proyek berisiko:</strong> {{ $berisiko->implode(' dan ') }} mendekati deadline.</span>
<a href="{{ route('admin.proyek.index') }}" class="btn btn-sm" style="margin-left:auto">Tinjau proyek</a>
</div>
@endif

<div class="grid-kpi">
@foreach($kpi as $k)
<div class="kpi">
<div class="kpi-l">{{ $k['label'] }}</div>
<div class="kpi-v">{{ $k['nilai'] }}</div>
<div class="kpi-s">{{ $k['sub'] }}</div>
</div>
@endforeach
</div>

<div class="dash-grid">
<div class="card">
<div class="card-head">
<div class="card-title">Overview per Proyek</div>
<div class="card-desc">100% = total tugas seluruh proyek. Pekat = selesai, terang = to do.</div>
</div>
<div class="card-body">
<div class="legend">
<span><i style="background:#1f7a52"></i>Selesai</span>
<span><i style="background:#f5a273"></i>Sedang Dikerjakan</span>
<span><i style="background:#eef0f6"></i>To Do</span>
</div>
@forelse($overviewProyek as $p)
<a href="{{ route('admin.proyek.show', $p['id']) }}" class="ov-row">
<div class="ov-nama">
<i class="ov-dot" style="background:{{ $p['warna'] }}"></i>
<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p['nama'] }}</span>
@if($p['berisiko'])<span class="badge b-risk">Berisiko</span>@endif
</div>
<div class="stack">
<i style="width:{{ $p['wDone'] }}%;background:#1f7a52"></i>
<i style="width:{{ $p['wProgress'] }}%;background:#f5a273"></i>
<i style="width:{{ $p['wTodo'] }}%;background:#eef0f6"></i>
</div>
<div class="ov-meta">{{ $p['tugas'] }} tugas · {{ $p['pct'] }}%</div>
</a>
@empty
<div class="empty">Belum ada proyek.</div>
@endforelse
</div>
</div>

<div class="card">
<div class="card-head">
<div class="card-title">Overview per User</div>
<div class="card-desc">100% = total tugas. {{ $overviewUser->count() }} kontributor dengan beban tertinggi.</div>
</div>
<div class="card-body">
<div class="legend">
<span><i style="background:#1f7a52"></i>Selesai</span>
<span><i style="background:#f5a273"></i>Sedang Dikerjakan</span>
<span><i style="background:#eef0f6"></i>To Do</span>
</div>
@forelse($overviewUser as $u)
<div class="ov-row">
<div class="ov-nama">
<span class="avatar" style="width:24px;height:24px;font-size:10px">{{ $u['inisial'] }}</span>
<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u['nama'] }}</span>
</div>
<div class="stack">
<i style="width:{{ $u['wDone'] }}%;background:#1f7a52"></i>
<i style="width:{{ $u['wProgress'] }}%;background:#f5a273"></i>
<i style="width:{{ $u['wTodo'] }}%;background:#eef0f6"></i>
</div>
<div class="ov-meta">{{ $u['tugas'] }} tugas · {{ $u['pct'] }}%</div>
</div>
@empty
<div class="empty">Belum ada kontributor.</div>
@endforelse
</div>
</div>
</div>

<div class="card" style="margin-top:14px">
<div class="card-head">
<div class="card-title">Kanban per Progress</div>
<div class="card-desc">Ringkasan tugas terbaru pada setiap status.</div>
</div>
<div class="card-body">
<div class="kanban">
@foreach($kanban as $col)
<div class="kcol">
<div class="kcol-h">
<span>{{ $col['nama'] }}</span>
<span class="kcol-c">{{ $col['count'] }}</span>
</div>
@forelse($col['items']->take(4) as $t)
<div class="ktask">
<div class="ktask-j">{{ $t->judul }}</div>
<div class="ktask-m">
<span class="ktask-p">{{ $t->project?->nama }}</span>
<span class="avatar" style="width:22px;height:22px;font-size:9.5px">{{ $t->user?->inisial() }}</span>
</div>
</div>
@empty
<div style="font-size:11.5px;color:var(--muted3);text-align:center;padding:14px 0">Tidak ada tugas</div>
@endforelse
@if($col['count'] > 4)
<div style="font-size:11px;color:var(--muted2);text-align:center;padding-top:3px">+{{ $col['count'] - 4 }} lainnya</div>
@endif
</div>
@endforeach
</div>
</div>
</div>

<div class="dash-grid">
<div class="card" style="display:flex;flex-direction:column">
<div class="card-head">
<div class="card-title">Timeline Proyek</div>
</div>
<div class="card-body" style="flex:1">
<div class="gantt-scroll">
<div class="gantt-head">
@foreach($bulan as $b)
<div>{{ $b->translatedFormat('M') }}</div>
@endforeach
</div>
@forelse($gantt as $g)
<div class="gantt-row">
<div class="gantt-nama">{{ $g['nama'] }}</div>
<div class="gantt-track">
<div class="gantt-bar" style="left:{{ $g['pos']['left'] }}%;width:{{ $g['pos']['width'] }}%;background:{{ $g['warna'] }}"></div>
<div class="gantt-fill" style="left:{{ $g['pos']['left'] }}%;width:{{ $g['pos']['width'] * $g['pct'] / 100 }}%;background:{{ $g['warna'] }}"></div>
<div class="gantt-pct">{{ $g['pct'] }}%</div>
</div>
</div>
@empty
<div class="empty">Belum ada proyek berjadwal.</div>
@endforelse
</div>
</div>
<div class="card-foot">Satu warna per proyek — bar pekat = progres, bar terang = rencana.</div>
</div>

<div class="card" style="display:flex;flex-direction:column">
<div class="card-head">
<div class="card-title">Aktivitas Terbaru</div>
</div>
<div class="card-body" style="flex:1">
@forelse($aktivitas as $a)
<div class="akt">
<span class="avatar">{{ $a['inisial'] }}</span>
<div style="min-width:0">
<div class="akt-t"><strong>{{ $a['siapa'] }}</strong> — {{ $a['apa'] }}</div>
<div class="akt-w">{{ $a['waktu'] }}</div>
</div>
</div>
@empty
<div class="empty">Belum ada aktivitas.</div>
@endforelse
</div>
<div class="card-foot">Menampilkan {{ $aktivitas->count() }} pembaruan tugas terakhir.</div>
</div>
</div>

@php($semuaUser = \App\Models\User::where('is_active', true)->orderBy('name')->get())
@include('partials.modal-assign')
@endsection
