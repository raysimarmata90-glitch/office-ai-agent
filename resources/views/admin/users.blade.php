@extends('layouts.admin')
@section('title', 'Tim')
@section('page-title', 'Tim')
@section('page-sub', $users->count() . ' pengguna terdaftar')

@section('topbar-actions')
<div class="sw-view">
<a href="{{ route('admin.users', ['view' => 'card']) }}" class="btn {{ $view === 'card' ? 'on' : '' }}">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
Card
</a>
<a href="{{ route('admin.users', ['view' => 'table']) }}" class="btn {{ $view === 'table' ? 'on' : '' }}">
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
<div class="dp-bar" style="border-bottom:0;background:transparent;padding:0 0 14px">
<label class="dp-cari">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
<input type="search" id="tmCari" placeholder="Cari nama atau email…" aria-label="Cari pengguna">
</label>
<div class="dp-sel"><select id="tmRole" data-select data-placeholder="Semua role">
<option value="">Semua role</option>
@foreach($daftarRole as $r)<option value="{{ $r->display_name ?: $r->name }}">{{ $r->display_name ?: $r->name }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="tmDep" data-select data-placeholder="Semua departemen">
<option value="">Semua departemen</option>
@foreach($daftarDep as $d)<option value="{{ $d->name }}">{{ $d->name }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="tmStatus" data-select data-placeholder="Semua status">
<option value="">Semua status</option>
<option value="1">Aktif</option>
<option value="0">Nonaktif</option>
</select></div>
<button type="button" class="btn btn-sm" id="tmReset">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
Reset
</button>
</div>

@if($view === 'card')
<div class="cards">
@foreach($users as $u)
<div class="ucard" data-orang
     data-cari="{{ Str::lower($u['nama'] . ' ' . $u['email']) }}"
     data-role="{{ $u['role'] }}" data-dep="{{ $u['departemen'] }}" data-aktif="{{ $u['aktif'] ? 1 : 0 }}">
<div class="ucard-h">
<span class="avatar" style="width:40px;height:40px;font-size:14px;border-radius:11px;background:{{ $u['warna']['bg'] }};color:{{ $u['warna']['text'] }}">
@if($u['foto'])<img src="{{ $u['foto'] }}" alt="{{ $u['nama'] }}">@else{{ $u['inisial'] }}@endif
</span>
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
{{-- Bar dan keterangannya dibaca dari yang sudah selesai lebih dulu. --}}
@php($urutStatus = \App\Models\Task::daftarStatusSelesaiDulu())
<div class="stack">
@foreach($urutStatus as $k => $lbl)
@php($n = $u['model']->tasks->where('status', $k)->count())
@if($n)<i style="width:{{ $u['ringkas']['total'] ? $n / $u['ringkas']['total'] * 100 : 0 }}%;background:{{ \App\Models\Task::titikStatus($k) }}" title="{{ $lbl }}: {{ $n }}"></i>@endif
@endforeach
</div>
<div class="mini">
@foreach($urutStatus as $k => $lbl)
<span><i style="background:{{ \App\Models\Task::titikStatus($k) }}"></i>{{ $lbl }} {{ $u['model']->tasks->where('status', $k)->count() }}</span>
@endforeach
</div>
<div class="ucard-s">
<span>{{ $u['ringkas']['total'] }} tugas</span>
<span>{{ $u['ringkas']['pct'] }}% selesai</span>
</div>
<div class="ucard-f">
<button type="button" class="btn btn-sm" data-ubah-user="{{ $u['id'] }}">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
Ubah
</button>
<button type="button" class="btn btn-sm" data-hapus-user="{{ $u['id'] }}" data-nama="{{ $u['nama'] }}">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
Hapus
</button>
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
@include('partials.th-sort', ['label' => 'Nama', 'ikon' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'])
@include('partials.th-sort', ['label' => 'Departemen', 'ikon' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>'])
@include('partials.th-sort', ['label' => 'Tugas', 'ikon' => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/>'])
@include('partials.th-sort', ['label' => 'Progress', 'ikon' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9M13 17V5M8 17v-3"/>'])
@include('partials.th-sort', ['label' => 'Status', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>'])
@include('partials.th-sort', ['label' => 'Dibuat', 'ikon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'])
<th style="width:110px">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($users as $u)
<tr data-row data-orang
    data-cari="{{ Str::lower($u['nama'] . ' ' . $u['email']) }}"
    data-role="{{ $u['role'] }}" data-dep="{{ $u['departemen'] }}" data-aktif="{{ $u['aktif'] ? 1 : 0 }}">
<td data-no></td>
<td data-sort="{{ $u['nama'] }}">
<div style="display:flex;align-items:center;gap:9px">
<span class="avatar" style="background:{{ $u['warna']['bg'] }};color:{{ $u['warna']['text'] }}">
@if($u['foto'])<img src="{{ $u['foto'] }}" alt="{{ $u['nama'] }}">@else{{ $u['inisial'] }}@endif
</span>
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
@foreach(\App\Models\Task::daftarStatusSelesaiDulu() as $k => $lbl)
@php($n = $u['model']->tasks->where('status', $k)->count())
@if($n)<i style="width:{{ $u['ringkas']['total'] ? $n / $u['ringkas']['total'] * 100 : 0 }}%;background:{{ \App\Models\Task::titikStatus($k) }}" title="{{ $lbl }}: {{ $n }}"></i>@endif
@endforeach
</div>
<span style="font-size:11.5px;color:var(--muted2);white-space:nowrap">{{ $u['ringkas']['pct'] }}%</span>
</div>
</td>
<td data-sort="{{ $u['aktif'] ? 1 : 0 }}">
@if($u['aktif'])<span class="badge b-done">Aktif</span>@else<span class="badge b-blok">Nonaktif</span>@endif
</td>
<td data-sort="{{ $u['dibuat']?->timestamp }}" style="white-space:nowrap;color:var(--muted2);font-size:12px">{{ $u['dibuat']?->format('d/m/y, H:i') }}</td>
<td>
<div class="aksi">
<button type="button" class="ico-btn" data-ubah-user="{{ $u['id'] }}" title="Ubah pengguna" aria-label="Ubah pengguna">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
</button>
<button type="button" class="ico-btn" data-hapus-user="{{ $u['id'] }}" data-nama="{{ $u['nama'] }}" title="Hapus pengguna" aria-label="Hapus pengguna">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
</button>
</div>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div data-empty class="kosong" style="display:none">
<span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
<span class="kosong-t">Tidak ada pengguna</span>
<span class="kosong-s">Tidak ada pengguna yang cocok dengan pencarian atau filter saat ini.</span>
</div>
@include('partials.pager')
</div>
@endif

@include('partials.modal-assign')

{{-- Modal ubah pengguna: struktur tab dan isinya mengikuti modal Profil Saya --}}
<x-modal id="userModal" lebar="620px" judul="Ubah Pengguna"
         desc="Perbarui data diri, ganti password, dan tinjau perangkat yang sedang masuk."
         :ikon="'<svg width=\'15\' height=\'15\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2\'/><circle cx=\'12\' cy=\'7\' r=\'4\'/></svg>'">

<div class="pf-tabs" role="tablist">
<button type="button" class="pf-tab on" data-um-tab="data" role="tab">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
Data Diri
</button>
<button type="button" class="pf-tab" data-um-tab="sandi" role="tab">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
Password
</button>
<button type="button" class="pf-tab" data-um-tab="sesi" role="tab">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
Sesi Aktif
</button>
</div>

{{-- Tab: data diri --}}
<form id="userForm" class="modal-form" data-um-panel="data">
@csrf
<div class="modal-body">
<div class="pf-foto">
<div class="pf-av" id="umAv"></div>
<div style="flex:1;min-width:0">
<div class="pf-nama" id="umNama"></div>
<div class="pf-meta" id="umMeta"></div>
<div class="pf-aksi">
<button type="button" class="btn btn-sm" id="umHapusFoto">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
Hapus Foto
</button>
</div>
</div>
</div>
<div class="row2">
<div class="fld"><label>Nama</label><input type="text" name="name" id="umName" required></div>
<div class="fld"><label>Email</label><input type="email" name="email" id="umEmail" required></div>
</div>
<div class="row2">
<div class="fld"><label>Departemen</label>
<select name="department_id" id="umDep" data-select data-placeholder="Pilih departemen">
<option value="">— Tanpa departemen —</option>
@foreach($daftarDep as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
</select>
</div>
<div class="fld"><label>Role</label>
<select name="role_id" id="umRole" required data-select data-placeholder="Pilih role">
@foreach($daftarRole as $r)<option value="{{ $r->id }}" data-dep="{{ $r->department_id ?? '' }}">{{ $r->display_name ?: $r->name }}</option>@endforeach
</select>
</div>
</div>
<div class="fld"><label>Telepon</label><input type="text" name="phone" id="umPhone" placeholder="08xxxxxxxxxx"></div>
<div class="fld"><label>Bio</label><textarea name="bio" id="umBio" rows="3" placeholder="Catatan singkat (opsional)"></textarea></div>
<div class="fld">
<label>Status Akun</label>
<label class="sw-row">
<input type="checkbox" name="is_active" id="umAktif" value="1">
<span class="sw-track"><span class="sw-dot"></span></span>
<span class="sw-teks">Akun aktif dan bisa login</span>
</label>
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-modal-close>
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
Tutup
</button>
<button type="submit" class="btn btn-primary">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
Simpan Perubahan
</button>
</div>
</form>

{{-- Tab: password --}}
<form id="umSandiForm" class="modal-form" data-um-panel="sandi" style="display:none">
@csrf
<div class="modal-body">
<div class="row2">
<div class="fld"><label>Password Baru</label><input type="password" name="sandi" minlength="8" autocomplete="new-password"></div>
<div class="fld"><label>Ulangi Password Baru</label><input type="password" name="sandi_confirmation" minlength="8" autocomplete="new-password"></div>
</div>
<div class="card-desc">Minimal 8 karakter. Sebagai admin, password lama tidak diminta. Setelah diganti, sesi pengguna di semua perangkat dikeluarkan.</div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-modal-close>
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
Tutup
</button>
<button type="submit" class="btn btn-primary">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
Ganti Password
</button>
</div>
</form>

{{-- Tab: sesi aktif --}}
<div class="modal-form" data-um-panel="sesi" style="display:none">
<div class="modal-body"><div id="umSesi"><div class="empty">Memuat sesi…</div></div></div>
<div class="modal-foot">
<button type="button" class="btn" data-modal-close>
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
Tutup
</button>
</div>
</div>
</x-modal>
@endsection

@push('script')
<script>
(function(){
const token=document.querySelector('meta[name="csrf-token"]').content;
function esc(v){return String(v==null?'':v).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]))}

/* ===== Cari & filter (berlaku untuk kartu maupun tabel) ===== */
let cari='',fRole='',fDep='',fStatus='';
function terapkan(){
document.querySelectorAll('[data-orang]').forEach(function(el){
const d=el.dataset;
let ok=true;
if(cari&&(d.cari||'').indexOf(cari)===-1)ok=false;
if(fRole&&d.role!==fRole)ok=false;
if(fDep&&d.dep!==fDep)ok=false;
if(fStatus!==''&&d.aktif!==fStatus)ok=false;
if(el.tagName==='TR')el.dataset.filterOff=ok?'':'1';
else el.style.display=ok?'':'none';
});
const kartu=document.querySelector('[data-table]');
if(kartu&&kartu.inaaiTable)kartu.inaaiTable.segarkan();
}
const inCari=document.getElementById('tmCari');
if(inCari)inCari.addEventListener('input',function(){cari=inCari.value.trim().toLowerCase();terapkan()});
[['tmRole',v=>fRole=v],['tmDep',v=>fDep=v],['tmStatus',v=>fStatus=v]].forEach(function([id,set]){
const el=document.getElementById(id);
if(el)el.addEventListener('change',function(){set(el.value);terapkan()});
});
const bReset=document.getElementById('tmReset');
if(bReset)bReset.addEventListener('click',function(){
cari='';fRole='';fDep='';fStatus='';
if(inCari)inCari.value='';
['tmRole','tmDep','tmStatus'].forEach(function(id){
const el=document.getElementById(id);
if(el){el.value='';if(el.inaaiSel&&el.inaaiSel.segarkan)el.inaaiSel.segarkan()}
});
terapkan();
});

/* ===== Modal ubah pengguna ===== */
const modal=document.getElementById('userModal'),form=document.getElementById('userForm');
let idAktif=null;
function q(sel){return modal.querySelector(sel)}
function tutup(){modal.classList.remove('open');document.body.style.overflow=''}

/* Tab: sama seperti modal profil. */
const tabs=[...modal.querySelectorAll('[data-um-tab]')],panel=[...modal.querySelectorAll('[data-um-panel]')];
function keTab(nama){
tabs.forEach(t=>t.classList.toggle('on',t.dataset.umTab===nama));
panel.forEach(p=>p.style.display=p.dataset.umPanel===nama?'':'none');
}
tabs.forEach(t=>t.addEventListener('click',()=>keTab(t.dataset.umTab)));
modal.querySelectorAll('[data-modal-close]').forEach(b=>b.addEventListener('click',tutup));
modal.addEventListener('click',e=>{if(e.target===modal)tutup()});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.classList.contains('open'))tutup()});

/* Role menyesuaikan departemen yang dipilih. */
let semuaRole=null;
function saringRole(){
const dep=q('#umDep'),role=q('#umRole');
if(!semuaRole)semuaRole=[...role.options].map(o=>({v:o.value,t:o.textContent,dep:o.getAttribute('data-dep')||''}));
const d=String(dep.value||'');
const sisa=semuaRole.filter(o=>o.dep===''||o.dep===d);
const lama=role.value;
role.innerHTML=sisa.map(o=>'<option value="'+esc(o.v)+'" data-dep="'+esc(o.dep)+'">'+esc(o.t)+'</option>').join('');
role.value=sisa.some(o=>o.v===lama)?lama:(sisa[0]?sisa[0].v:'');
if(role.inaaiSel&&role.inaaiSel.segarkan)role.inaaiSel.segarkan();
}
q('#umDep').addEventListener('change',saringRole);

async function buka(id){
try{
const r=await fetch('/admin/users/'+id,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
if(!r.ok)throw new Error(r.status);
const d=await r.json();
idAktif=id;
q('#umAv').innerHTML=d.foto?'<img src="'+esc(d.foto)+'" alt="">':esc(d.inisial);
q('#umNama').textContent=d.nama;
q('#umMeta').textContent=d.email;
q('#umName').value=d.nama||'';
q('#umEmail').value=d.email||'';
q('#umPhone').value=d.telepon||'';
q('#umBio').value=d.bio||'';
q('#umDep').value=d.department_id||'';
q('#umDep').dispatchEvent(new Event('change',{bubbles:true}));
saringRole();
q('#umRole').value=d.role_id||'';
if(q('#umRole').inaaiSel)q('#umRole').inaaiSel.segarkan();
q('#umAktif').checked=!!d.aktif;
q('#umAktif').disabled=!!d.diri_sendiri;
sandiForm.reset();
gambarSesi(d.sesi||[]);
keTab('data');
modal.classList.add('open');
document.body.style.overflow='hidden';
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Gagal memuat data pengguna: '+e.message)}
}

/* Daftar sesi memakai renderer yang sama dengan modal profil. */
function gambarSesi(daftar){
const kotak=q('#umSesi');
if(window.InaaiProfil&&window.InaaiProfil.gambarSesi)window.InaaiProfil.gambarSesi(kotak,daftar,keluarkanSesi);
else kotak.innerHTML='<div class="empty">Tidak ada sesi tercatat.</div>';
}

async function keluarkanSesi(sesi){
try{
const r=await fetch('/admin/users/'+idAktif+'/sesi/'+encodeURIComponent(sesi),{method:'DELETE',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
const d=await r.json();
if(!r.ok){window.InaaiToast&&window.InaaiToast.galat(d.message||'Gagal mengeluarkan perangkat.');return}
window.InaaiToast&&window.InaaiToast.sukses(d.pesan);
gambarSesi(d.sesi||[]);
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Koneksi bermasalah: '+e.message)}
}

const sandiForm=document.getElementById('umSandiForm');
sandiForm.addEventListener('submit',async function(e){
e.preventDefault();
try{
const r=await fetch('/admin/users/'+idAktif+'/sandi',{method:'POST',body:new FormData(sandiForm),headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
const d=await r.json();
if(!r.ok){
let pesan=d.message||'Gagal mengganti password.';
if(d.errors)pesan=Object.values(d.errors).flat().join(' ');
window.InaaiToast&&window.InaaiToast.galat(pesan);
return;
}
sandiForm.reset();
window.InaaiToast&&window.InaaiToast.sukses(d.pesan);
if(d.sesi)gambarSesi(d.sesi);
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Koneksi bermasalah: '+e.message)}
});

q('#umHapusFoto').addEventListener('click',async function(){
const lanjut=window.InaaiDialog?await window.InaaiDialog.konfirmasi({
judul:'Hapus Foto Profil',
teks:'Foto profil pengguna ini akan dihapus dan diganti inisial namanya.',
ok:'Iya, hapus foto',jenis:'bahaya'
}):confirm('Hapus foto profil?');
if(!lanjut)return;
try{
const r=await fetch('/admin/users/'+idAktif+'/foto',{method:'DELETE',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
const d=await r.json();
if(!r.ok){window.InaaiToast&&window.InaaiToast.galat(d.message||'Gagal menghapus foto.');return}
q('#umAv').innerHTML=esc(d.inisial||'?');
window.InaaiToast&&window.InaaiToast.sukses(d.pesan);
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Koneksi bermasalah: '+e.message)}
});

form.addEventListener('submit',async function(e){
e.preventDefault();
const fd=new FormData(form);
fd.append('_method','PATCH');
try{
const r=await fetch('/admin/users/'+idAktif,{method:'POST',body:fd,headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
const d=await r.json();
if(!r.ok){
let pesan=d.message||'Gagal menyimpan.';
if(d.errors)pesan=Object.values(d.errors).flat().join(' ');
window.InaaiToast&&window.InaaiToast.galat(pesan);
return;
}
window.InaaiToast&&window.InaaiToast.sukses(d.pesan);
tutup();
setTimeout(()=>location.reload(),650);
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Koneksi bermasalah: '+e.message)}
});

async function hapus(id,nama){
const lanjut=window.InaaiDialog?await window.InaaiDialog.konfirmasi({
judul:'Hapus Pengguna',
teks:'Akun "'+nama+'" akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.',
ok:'Iya, hapus pengguna',jenis:'bahaya'
}):confirm('Hapus pengguna ini?');
if(!lanjut)return;
try{
const r=await fetch('/admin/users/'+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
const d=await r.json();
if(!r.ok){window.InaaiToast&&window.InaaiToast.galat(d.message||'Gagal menghapus.');return}
window.InaaiToast&&window.InaaiToast.sukses(d.pesan);
setTimeout(()=>location.reload(),650);
}catch(e){window.InaaiToast&&window.InaaiToast.galat('Koneksi bermasalah: '+e.message)}
}

document.querySelectorAll('[data-ubah-user]').forEach(b=>b.addEventListener('click',()=>buka(b.dataset.ubahUser)));
document.querySelectorAll('[data-hapus-user]').forEach(b=>b.addEventListener('click',()=>hapus(b.dataset.hapusUser,b.dataset.nama||'')));
})();
</script>
@endpush
