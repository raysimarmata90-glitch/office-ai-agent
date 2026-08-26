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

<div class="card" style="margin-top:14px" data-table id="percakapanCard">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></span>Daftar Percakapan</div>
<div class="card-desc">Telusuri percakapan pengguna dengan agent, lalu buka transkrip lengkapnya.</div>
</div>

<div class="dp-bar">
<label class="dp-cari">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
<input type="search" id="pcCari" placeholder="Cari judul, nama, atau email pengguna…" aria-label="Cari percakapan">
</label>
<div class="dp-sel"><select id="pcStatus" data-select data-placeholder="Semua status">
<option value="">Semua status</option>
<option value="active" data-color="var(--st-prog)">Sedang Berjalan</option>
<option value="completed" data-color="var(--st-done)">Selesai</option>
</select></div>
<div class="dp-sel"><select id="pcDep" data-select data-placeholder="Semua departemen">
<option value="">Semua departemen</option>
@foreach($departemen as $d)<option value="{{ $d->name }}">{{ $d->name }}</option>@endforeach
</select></div>
<button type="button" class="btn btn-sm" id="pcReset">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
Reset
</button>
</div>

<div class="tbl-wrap">
<table class="tbl">
<thead>
<tr>
<th style="width:56px">No</th>
@include('partials.th-sort', ['label' => 'Percakapan', 'ikon' => '<path d="M4 6h16M4 12h10M4 18h7"/>'])
@include('partials.th-sort', ['label' => 'Pengguna', 'ikon' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'])
@include('partials.th-sort', ['label' => 'Departemen', 'ikon' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>'])
@include('partials.th-sort', ['label' => 'Status', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>'])
@include('partials.th-sort', ['label' => 'Pesan', 'ikon' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>'])
@include('partials.th-sort', ['label' => 'Diperbarui', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'])
<th style="width:64px">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($conversations as $c)
@php($aktif = $c->status === 'active')
<tr data-row data-status="{{ $c->status }}" data-dep="{{ $c->department?->name }}"
    data-cari="{{ \Illuminate\Support\Str::lower(($c->title ?? '') . ' ' . $c->namaProyek() . ' ' . ($c->user?->name ?? '') . ' ' . ($c->user?->email ?? '')) }}">
<td data-no></td>
<td data-sort="{{ $c->namaProyek() }}">
<strong class="potong" style="--w:260px" title="{{ $c->title }}">{{ $c->namaProyek() }}</strong>
<span class="td-periode" title="{{ $c->judulTugas() }}">{{ \Illuminate\Support\Str::limit($c->judulTugas(), 46) }}</span>
</td>
<td data-sort="{{ $c->user?->name }}">
@php($wa = $c->user ? $c->user->warnaAvatar() : ['bg' => 'var(--line3)', 'text' => 'var(--muted)'])
<div style="display:flex;align-items:center;gap:8px">
<span class="avatar xs" style="background:{{ $wa['bg'] }};color:{{ $wa['text'] }}">{{ $c->user?->inisial() ?? '?' }}</span>
<span style="min-width:0">
<span class="potong" style="--w:150px;font-weight:600">{{ $c->user?->name ?? '–' }}</span>
<span class="potong" style="--w:150px;font-size:11.5px;color:var(--muted3)">{{ $c->user?->email }}</span>
</span>
</div>
</td>
<td data-sort="{{ $c->department?->name }}">
@if($c->department)
<span class="badge" style="background:{{ $c->department->color }}1f;color:{{ $c->department->color }}">{{ $c->department->name }}</span>
@else
<span style="color:var(--muted3)">–</span>
@endif
</td>
<td data-sort="{{ $aktif ? 0 : 1 }}">
<span class="badge" style="background:{{ $aktif ? 'var(--st-prog-bg)' : 'var(--st-done-bg)' }};color:{{ $aktif ? 'var(--st-prog)' : 'var(--st-done)' }}">
{{ $aktif ? 'Sedang Berjalan' : 'Selesai' }}
</span>
</td>
<td data-sort="{{ $c->messages_count }}" style="white-space:nowrap">{{ $c->messages_count }} pesan</td>
<td data-sort="{{ $c->updated_at?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $c->updated_at?->translatedFormat('d M y, H:i') }}</td>
<td>
<a class="ico-btn" href="{{ route('admin.conversation.detail', $c->id) }}" title="Lihat transkrip" aria-label="Lihat transkrip">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
</a>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div data-empty class="kosong" style="display:none">
<span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg></span>
<span class="kosong-t">Belum ada percakapan</span>
<span class="kosong-s">Tidak ada percakapan yang cocok dengan pencarian atau filter saat ini.</span>
</div>
@include('partials.pager')
</div>
@endsection

@push('script')
<script>
(function(){
let cari='',fStatus='',fDep='';
const kartu=document.getElementById('percakapanCard');
function terapkan(){
document.querySelectorAll('tr[data-row]').forEach(function(tr){
const d=tr.dataset;
let ok=true;
if(cari&&(d.cari||'').indexOf(cari)===-1)ok=false;
if(fStatus&&d.status!==fStatus)ok=false;
if(fDep&&d.dep!==fDep)ok=false;
tr.dataset.filterOff=ok?'':'1';
});
if(kartu&&kartu.inaaiTable)kartu.inaaiTable.segarkan();
}
const inCari=document.getElementById('pcCari');
if(inCari)inCari.addEventListener('input',function(){cari=inCari.value.trim().toLowerCase();terapkan()});
[['pcStatus',v=>fStatus=v],['pcDep',v=>fDep=v]].forEach(function([id,set]){
const el=document.getElementById(id);
if(el)el.addEventListener('change',function(){set(el.value);terapkan()});
});
const bReset=document.getElementById('pcReset');
if(bReset)bReset.addEventListener('click',function(){
cari='';fStatus='';fDep='';
if(inCari)inCari.value='';
['pcStatus','pcDep'].forEach(function(id){
const el=document.getElementById(id);
if(el){el.value='';if(el.inaaiSel&&el.inaaiSel.segarkan)el.inaaiSel.segarkan()}
});
terapkan();
});
})();
</script>
@endpush
