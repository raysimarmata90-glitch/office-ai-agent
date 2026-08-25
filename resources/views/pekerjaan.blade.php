@extends('layouts.user')
@section('title', 'Pekerjaan Saya')
@section('page-title', 'Pekerjaan Saya')
@section('page-sub', $ringkas['total'] . ' tugas · ' . $user->name)

@section('topbar-actions')
<a href="{{ route('dashboard') }}" class="btn">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
Chat
</a>
<button class="btn btn-primary" type="button" data-open-task>
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
Tugas Baru
</button>
@endsection

@push('style')
<style>
.kanban{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:11px}
.kcol{background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:10px;min-height:130px}
.kcol-h{display:flex;align-items:center;justify-content:space-between;font-size:12px;font-weight:700;margin-bottom:9px}
.kcol-c{background:#fff;border:1px solid var(--line2);border-radius:999px;padding:1px 8px;font-size:11px;color:var(--muted2)}
.ktask{background:#fff;border:1px solid var(--line2);border-radius:10px;padding:9px 10px;margin-bottom:8px;cursor:grab}
.ktask.drag{opacity:.45}
.kcol.over{border-color:var(--primary);background:var(--primary-soft)}
.ktask-j{font-size:12.5px;font-weight:600;line-height:1.35}
.ktask-m{display:flex;align-items:center;justify-content:space-between;margin-top:7px;gap:6px}
.ktask-p{font-size:11px;color:var(--muted2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.kcol-n{display:flex;align-items:center;gap:7px}
.kdot{width:9px;height:9px;border-radius:50%;display:block;flex:none}
.ktask-w{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;color:var(--muted3);white-space:nowrap;flex:none}
.ktask-ev{font-size:10.5px;color:var(--muted3);margin-top:4px}
.ev-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
.ev-chip{display:inline-flex;align-items:center;gap:5px;background:var(--line3);border-radius:7px;padding:3px 8px;font-size:11px;color:var(--muted)}
@media(max-width:1000px){.kanban{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush

@section('content')
<div class="grid-kpi">
@foreach($kpi as $k)
<div class="kpi">
<div class="kpi-l">{{ $k['label'] }}</div>
<div class="kpi-v">{{ $k['nilai'] }}</div>
<div class="kpi-s">{{ $ringkas['total'] ? round($k['nilai'] / $ringkas['total'] * 100) : 0 }}% dari total</div>
</div>
@endforeach
</div>

<div class="card" style="margin-top:14px">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></span>Progress Keseluruhan</div>
<div class="card-desc">Komposisi seluruh tugas Anda dalam satu bar.</div>
</div>
<div class="card-body">
<div class="stack" style="height:13px">
<i style="width:{{ $ringkas['pctDone'] }}%;background:#1f7a52"></i>
<i style="width:{{ $ringkas['pctProgress'] }}%;background:#f5a273"></i>
<i style="width:{{ $ringkas['pctTodo'] }}%;background:#eef0f6"></i>
</div>
<div style="display:flex;gap:16px;margin-top:11px;font-size:12px;color:var(--muted2);flex-wrap:wrap">
<span style="display:flex;align-items:center;gap:6px"><i style="width:9px;height:9px;border-radius:3px;background:#1f7a52;display:block"></i>Selesai {{ $ringkas['done'] }}</span>
<span style="display:flex;align-items:center;gap:6px"><i style="width:9px;height:9px;border-radius:3px;background:#f5a273;display:block"></i>Sedang Dikerjakan {{ $ringkas['progress'] }}</span>
<span style="display:flex;align-items:center;gap:6px"><i style="width:9px;height:9px;border-radius:3px;background:#eef0f6;display:block"></i>To Do {{ $ringkas['todo'] }}</span>
<span style="margin-left:auto;font-weight:700;color:var(--ink)">{{ $ringkas['pct'] }}% selesai</span>
</div>
</div>
</div>

<div class="card" style="margin-top:14px" data-timeline data-tl-key="pekerjaan" data-tl-default="3m">
<div class="card-head tl-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span>Timeline Pekerjaan</div>
<div class="tl-filter" data-tl-filter role="group" aria-label="Rentang tampilan timeline"></div>
</div>
<div class="card-body">
<div data-tl-body><div class="empty">Memuat timeline…</div></div>
</div>
<div class="card-foot" data-tl-note>Warna bar mengikuti warna proyek terkait.</div>
<script type="application/json" data-tl-data>@json($timeline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)</script>
</div>

<div class="card" style="margin-top:14px">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="6" height="16" x="4" y="4" rx="1"/><rect width="6" height="10" x="14" y="4" rx="1"/></svg></span>Kanban Saya</div>
<div class="card-desc">Seret kartu untuk memindahkan status.</div>
</div>
<div class="card-body">
<div class="kanban">
@foreach($kanban as $col)
<div class="kcol" data-status="{{ $col['key'] }}">
<div class="kcol-h">
<span class="kcol-n">
<i class="kdot" style="background:{{ \App\Models\Task::titikStatus($col['key']) }}"></i>
{{ $col['nama'] }}
</span>
<span class="kcol-c">{{ $col['count'] }}</span>
</div>
@forelse($col['items'] as $t)
<div class="ktask" draggable="true" data-task="{{ $t->id }}">
<div class="ktask-j">{{ $t->judul }}</div>
<div class="ktask-m">
<span class="ktask-p">{{ $t->project?->nama }}</span>
<span class="ktask-w" title="Terakhir diperbarui">
<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
{{ $t->waktuSingkat() }}
</span>
</div>
<div class="ktask-ev">{{ $t->evidences->count() }} evidence</div>
</div>
@empty
<div style="font-size:11.5px;color:var(--muted3);text-align:center;padding:14px 0">Kosong</div>
@endforelse
</div>
@endforeach
</div>
</div>
</div>

<div class="card" style="margin-top:14px" data-table>
<div class="card-head"><div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M3 9h18M3 15h18M9 3v18"/></svg></span>Daftar Pekerjaan</div></div>
<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:52px">No</th>
@include('partials.th-sort', ['label' => 'Judul'])
@include('partials.th-sort', ['label' => 'Proyek'])
@include('partials.th-sort', ['label' => 'Periode'])
@include('partials.th-sort', ['label' => 'Status'])
@include('partials.th-sort', ['label' => 'Reviewer'])
@include('partials.th-sort', ['label' => 'Evidence'])
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
<td data-sort="{{ $t->mulai?->timestamp }}" style="white-space:nowrap;color:var(--muted)">{{ $t->mulai?->format('d/m/y') }} – {{ $t->selesai?->format('d/m/y') }}</td>
<td data-sort="{{ $t->status }}"><span class="badge" style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">{{ $t->statusLabel() }}</span></td>
<td data-sort="{{ $t->reviewer?->name }}">{{ $t->reviewer?->name ?? '–' }}</td>
<td data-sort="{{ $t->evidences->count() }}">
@if($t->evidences->count())
<div class="ev-list">
@foreach($t->evidences->take(2) as $ev)
<a href="{{ route('tasks.evidence', $ev->id) }}" target="_blank" class="ev-chip">
<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
{{ Str::limit($ev->nama_file, 14) }}
</a>
@endforeach
@if($t->evidences->count() > 2)<span class="ev-chip">+{{ $t->evidences->count() - 2 }}</span>@endif
</div>
@else
<span style="color:var(--muted3)">–</span>
@endif
</td>
<td data-sort="{{ $t->created_at?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $t->created_at?->format('d/m/y, H:i') }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div data-empty class="empty" style="display:none">Belum ada pekerjaan.</div>
@include('partials.pager')
</div>

<div class="modal-bg" id="taskModal">
<div class="modal">
<form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data">@csrf
<div class="modal-head">
<div style="flex:1">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></span>Tugas Baru</div>
<div class="card-desc">Lengkapi detail, lampirkan evidence, lalu assign dan minta review.</div>
</div>
<button type="button" class="btn btn-sm" data-close-task>✕</button>
</div>
<div class="modal-body">
<div class="fld">
<label>Judul Tugas</label>
<input type="text" name="judul" required placeholder="Contoh: Integrasi API pembayaran" value="{{ old('judul') }}">
</div>
<div class="row2">
<div class="fld">
<label>Proyek</label>
<select name="project_id" id="taskProject" data-select data-placeholder="Pilih proyek">
<option value="">— Proyek baru —</option>
@foreach($projects as $p)
<option value="{{ $p->id }}" data-color="{{ $p->warna }}" @selected(old('project_id') == $p->id)>{{ $p->nama }}</option>
@endforeach
</select>
</div>
<div class="fld">
<label>Nama Proyek Baru</label>
<input type="text" name="project_baru" id="taskProjectBaru" placeholder="Isi bila proyek baru" value="{{ old('project_baru') }}">
</div>
</div>
<div class="row2">
<div class="fld">
<label>Status Saat Ini</label>
<select name="status" required data-select data-placeholder="Pilih status">
@foreach(\App\Models\Task::daftarStatus() as $k => $lbl)
<option value="{{ $k }}" data-color="{{ \App\Models\Task::titikStatus($k) }}" @selected(old('status', 'to_do') === $k)>{{ $lbl }}</option>
@endforeach
</select>
</div>
<div class="fld">
<label>Prioritas</label>
<select name="prioritas" required data-select data-placeholder="Pilih prioritas">
@foreach(['Tinggi' => '#b23c35', 'Sedang' => '#a05a1c', 'Rendah' => '#5b6172'] as $pr => $warna)
<option value="{{ $pr }}" data-color="{{ $warna }}" @selected(old('prioritas', 'Sedang') === $pr)>{{ $pr }}</option>
@endforeach
</select>
</div>
</div>
<div class="row2">
<div class="fld">
<label>Waktu Mulai</label>
<input type="date" name="mulai" required value="{{ old('mulai', now()->toDateString()) }}">
</div>
<div class="fld">
<label>Waktu Selesai</label>
<input type="date" name="selesai" required value="{{ old('selesai', now()->addWeek()->toDateString()) }}">
</div>
</div>
<div class="row2">
<div class="fld">
<label>Assign ke</label>
<select name="user_id" required data-select data-placeholder="Pilih pegawai">
<option value="{{ $user->id }}">{{ $user->name }} (saya)</option>
@foreach($rekan->where('id', '!=', $user->id) as $r)
<option value="{{ $r->id }}" @selected(old('user_id') == $r->id)>{{ $r->name }}</option>
@endforeach
</select>
</div>
<div class="fld">
<label>Request Reviewer</label>
<select name="reviewer_id" data-select data-placeholder="Pilih reviewer">
<option value="">— Tanpa reviewer —</option>
@foreach($rekan->where('id', '!=', $user->id) as $r)
<option value="{{ $r->id }}" @selected(old('reviewer_id') == $r->id)>{{ $r->name }}</option>
@endforeach
</select>
</div>
</div>
<div class="fld">
<label>Evidence (dokumen / gambar)</label>
<input type="file" name="evidence[]" multiple
       data-upload
       data-max-size="{{ $maksUnggahMb ?? 2 }}"
       data-judul="Seret file ke sini atau <b>pilih dari perangkat</b>"
       accept="{{ $acceptUnggah ?? '.pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp' }}">
</div>
<div class="fld">
<label>Deskripsi</label>
<textarea name="deskripsi" rows="3" placeholder="Detail pekerjaan (opsional)">{{ old('deskripsi') }}</textarea>
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-close-task>Batal</button>
<button type="submit" class="btn btn-primary">Simpan Tugas</button>
</div>
</form>
</div>
</div>
@endsection

@push('script')
<script>
(function(){
const m=document.getElementById('taskModal');
document.querySelectorAll('[data-open-task]').forEach(function(b){b.addEventListener('click',function(){m.classList.add('open')})});
document.querySelectorAll('[data-close-task]').forEach(function(b){b.addEventListener('click',function(){m.classList.remove('open')})});
m.addEventListener('click',function(e){if(e.target===m)m.classList.remove('open')});
const sel=document.getElementById('taskProject'),baru=document.getElementById('taskProjectBaru');
function sync(){baru.disabled=!!sel.value;if(sel.value)baru.value=''}
sel.addEventListener('change',sync);sync();
@if($errors->any())
m.classList.add('open');
@endif

const token=document.querySelector('meta[name="csrf-token"]').content;
let dragId=null;
document.querySelectorAll('.ktask').forEach(function(c){
c.addEventListener('dragstart',function(){dragId=c.dataset.task;c.classList.add('drag')});
c.addEventListener('dragend',function(){c.classList.remove('drag')});
});
document.querySelectorAll('.kcol').forEach(function(col){
col.addEventListener('dragover',function(e){e.preventDefault();col.classList.add('over')});
col.addEventListener('dragleave',function(){col.classList.remove('over')});
col.addEventListener('drop',function(e){
e.preventDefault();col.classList.remove('over');
if(!dragId)return;
fetch('/tasks/'+dragId+'/status',{
method:'PATCH',
headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
body:JSON.stringify({status:col.dataset.status})
}).then(function(r){if(r.ok)location.reload()});
});
});
})();
</script>
@endpush
