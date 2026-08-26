@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Selamat datang, ' . explode(' ', $user->name)[0])
@section('page-sub', 'Ringkasan portofolio · diperbarui ' . now()->translatedFormat('d F Y, H.i'))

@section('topbar-actions')
<a href="{{ route('admin.laporan.ekspor') }}" class="btn">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg>
Ekspor Laporan
</a>
<button class="btn" type="button" id="segarkanHalaman" title="Muat ulang seluruh data halaman">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
Segarkan
</button>
<button class="btn btn-primary" type="button" data-open-assign>
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
Assign Tugas
</button>
@include('partials.modal-assign')
@include('partials.drawer-tugas')
@endsection

@push('script')
<script>
(function(){
if(!window.InaaiTimeline)return;
const bar=document.getElementById('rentangHalaman'),info=document.getElementById('rentangInfo');
const DATA=JSON.parse(document.querySelector('[data-tl-data]').textContent||'[]');
const KUNCI='inaai_admin_rentang';
let rentang='30d',fUser='',fProyek='',cari='',fPrioritas='';
try{const v=localStorage.getItem(KUNCI);if(v)rentang=v}catch(e){}

bar.innerHTML=window.InaaiTimeline.daftarRentang.map(r=>'<button type="button" class="tl-f" data-range="'+r.key+'">'+r.label+'</button>').join('');
function keTgl(v){const p=String(v||'').slice(0,10).split('-');return p.length<3?null:new Date(+p[0],+p[1]-1,+p[2])}
function dalamRentang(m,sl){
const j=window.InaaiTimeline.jendela(rentang),a=keTgl(m),b=keTgl(sl);
if(!a||!b)return true;
return new Date(b.getTime()+86400000)>j.mulai&&a<j.selesai;
}

/* ===== Kanban: dimuat bertahap 50 kartu per kolom =====
   Saringan dikerjakan server supaya kartu yang belum termuat ikut tersaring,
   dan tiap kolom berhenti memanggil API begitu datanya habis. */
const URL_KANBAN=@json(route('admin.kanban'));
const kolom=[...document.querySelectorAll('#kanbanDash .kcol')].map(function(el){
return {el:el,status:el.dataset.status,
body:el.querySelector('[data-kb-body]'),
state:el.querySelector('[data-kb-state]'),
hitung:el.querySelector('.kcol-c'),
offset:0,total:0,habis:false,memuat:false};
});

function iso(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}

function paramKanban(k){
const j=window.InaaiTimeline.jendela(rentang);
const q=new URLSearchParams({status:k.status,offset:k.offset,dari:iso(j.mulai),sampai:iso(j.selesai)});
if(cari)q.set('q',cari);
if(fProyek)q.set('proyek',fProyek);
if(fPrioritas)q.set('prioritas',fPrioritas);
if(fUser)q.set('user',fUser);
return q;
}

const IKON_KOSONG='<span class="kosong-ico"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 12h6"/></svg></span>';

function tulisState(k,mode){
if(mode==='memuat'){k.state.className='kb-state load';k.state.innerHTML='<i class="kb-spin"></i>Memuat…'}
else if(mode==='kosong'){k.state.className='kosong';k.state.innerHTML=IKON_KOSONG+'<span class="kosong-t">Kosong</span>'}
else if(mode==='habis'){k.state.className='kb-state';k.state.textContent='Semua '+k.total+' tugas sudah ditampilkan.'}
else if(mode==='galat'){k.state.className='kb-state';k.state.textContent='Gagal memuat. Gulir lagi untuk mencoba.'}
else{k.state.className='kb-state';k.state.textContent=''}
}

function diDasar(el){return el.scrollTop+el.clientHeight>=el.scrollHeight-40}

async function muat(k){
if(k.habis||k.memuat)return;
k.memuat=true;tulisState(k,'memuat');
try{
const r=await fetch(URL_KANBAN+'?'+paramKanban(k).toString(),{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
if(!r.ok)throw new Error(r.status);
const d=await r.json();
k.state.insertAdjacentHTML('beforebegin',d.html);
k.offset+=d.jumlah;
k.total=d.total;
k.hitung.textContent=d.total;
// Balasan kosong dianggap habis supaya offset tidak jalan di tempat.
k.habis=!!d.habis||!d.jumlah;
tulisState(k,k.total===0?'kosong':(k.habis?'habis':''));
}catch(e){
tulisState(k,'galat');
k.memuat=false;
return;
}
k.memuat=false;
// Masih menempel di dasar (kartu belum memenuhi kolom): lanjutkan memuat,
// karena tidak akan ada event scroll baru yang memicunya.
if(!k.habis&&diDasar(k.body))muat(k);
}

function muatUlangKanban(){
kolom.forEach(function(k){
k.body.querySelectorAll('.ktask').forEach(el=>el.remove());
k.offset=0;k.total=0;k.habis=false;k.memuat=false;
k.body.scrollTop=0;
muat(k);
});
}

kolom.forEach(function(k){
k.body.addEventListener('scroll',function(){if(!k.habis&&!k.memuat&&diDasar(k.body))muat(k)});
});

/* Memindah kartu mengubah KPI, kedua overview, dan timeline sekaligus — bukan
   kanbannya saja — jadi seluruh halaman dimuat ulang. */
document.getElementById('kanbanDash').addEventListener('inaai:kanban-pindah',function(){
setTimeout(()=>location.reload(),650);
});

const bSegarkan=document.getElementById('segarkanHalaman');
if(bSegarkan)bSegarkan.addEventListener('click',function(){location.reload()});

function terapkan(){
bar.querySelectorAll('[data-range]').forEach(b=>b.classList.toggle('on',b.dataset.range===rentang));
window.InaaiTimeline.rentang(rentang);
window.InaaiTimeline.saring(t=>(!fUser||t.reviewer===fUser)&&(!fProyek||t.proyek===fProyek));

muatUlangKanban();

// Overview saling menyaring
document.querySelectorAll('[data-ov-user]').forEach(el=>el.classList.toggle('on',el.dataset.ovUser===fUser));
document.querySelectorAll('[data-ov-proyek]').forEach(el=>el.classList.toggle('on',el.dataset.ovProyek===fProyek));
const proyekUser=new Set(DATA.filter(t=>!fUser||t.reviewer===fUser).map(t=>t.proyek));
document.querySelectorAll('[data-ov-proyek]').forEach(function(el){
el.style.display=(!fUser||proyekUser.has(el.dataset.ovProyek))?'':'none';
});
const userProyek=new Set(DATA.filter(t=>!fProyek||t.proyek===fProyek).map(t=>t.reviewer));
document.querySelectorAll('[data-ov-user]').forEach(function(el){
el.style.display=(!fProyek||userProyek.has(el.dataset.ovUser))?'':'none';
});

const dipakai=DATA.filter(t=>dalamRentang(t.mulai,t.selesai)&&(!fUser||t.reviewer===fUser)&&(!fProyek||t.proyek===fProyek));
const label=[];
if(fUser)label.push('kontributor '+fUser);
if(fProyek)label.push('proyek '+fProyek);
info.textContent=dipakai.length+' tugas pada rentang ini'+(label.length?' · disaring: '+label.join(' & '):'');
}

bar.addEventListener('click',function(e){
const b=e.target.closest('[data-range]');
if(!b)return;
rentang=b.dataset.range;
try{localStorage.setItem(KUNCI,rentang)}catch(err){}
terapkan();
});

document.querySelectorAll('[data-ov-user]').forEach(function(el){
function pilih(){fUser=fUser===el.dataset.ovUser?'':el.dataset.ovUser;terapkan()}
el.addEventListener('click',pilih);
el.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();pilih()}});
});
document.querySelectorAll('[data-ov-proyek]').forEach(function(el){
function pilih(){
fProyek=fProyek===el.dataset.ovProyek?'':el.dataset.ovProyek;
// Select filter kanban dan overview menunjuk nilai yang sama.
const sel=document.getElementById('kbProyek');
if(sel){sel.value=[...sel.options].some(o=>o.value===fProyek)?fProyek:'';if(sel.inaaiSel&&sel.inaaiSel.segarkan)sel.inaaiSel.segarkan()}
terapkan();
}
el.addEventListener('click',e=>{if(!e.target.closest('a'))pilih()});
el.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();pilih()}});
});

const inCari=document.getElementById('kbCari');
if(inCari)inCari.addEventListener('input',function(){cari=inCari.value.trim().toLowerCase();terapkan()});
[['kbProyek',v=>fProyek=v],['kbPrioritas',v=>fPrioritas=v]].forEach(function([id,set]){
const el=document.getElementById(id);
if(el)el.addEventListener('change',function(){set(el.value);terapkan()});
});
const bReset=document.getElementById('kbReset');
if(bReset)bReset.addEventListener('click',function(){
cari='';fProyek='';fPrioritas='';fUser='';
if(inCari)inCari.value='';
['kbProyek','kbPrioritas'].forEach(function(id){
const el=document.getElementById(id);
if(el){el.value='';if(el.inaaiSel&&el.inaaiSel.segarkan)el.inaaiSel.segarkan()}
});
terapkan();
});

/* Kartu kanban dibuat setelah halaman jadi, jadi aksinya didelegasikan. */
document.getElementById('kanbanDash').addEventListener('click',function(e){
const lihat=e.target.closest('[data-lihat]');
if(lihat)return window.InaaiDrawerTugas.buka(lihat.dataset.lihat);
const ubah=e.target.closest('[data-ubah]');
if(ubah)return window.InaaiFormTugas.ubah(ubah.dataset.ubah);
const hapus=e.target.closest('[data-hapus]');
if(hapus)return window.InaaiDrawerTugas.hapus(hapus.dataset.hapus,hapus.dataset.judul||'');
});

terapkan();
})();
</script>
@endpush

@push('style')
<style>
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}

@media(max-width:1000px){.dash-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
{{-- Filter rentang berlaku untuk seluruh data di halaman ini --}}
<div class="page-bar">
<span class="page-bar-l">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
Rentang
</span>
<div class="tl-filter" id="rentangHalaman" role="group" aria-label="Rentang tampilan halaman"></div>
<span class="page-bar-l" id="rentangInfo" style="margin-left:auto;font-weight:500;color:var(--muted3)"></span>
</div>

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
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg></span>Overview per Proyek</div>
<div class="card-desc">Bar penuh = seluruh tugas proyek itu; tiap warna adalah porsi statusnya. Abu-abu berarti belum ada tugas.</div>
</div>
<div class="card-body">
<div class="legend">
@foreach(\App\Models\Task::daftarStatusSelesaiDulu() as $k => $lbl)
<span><i style="background:{{ \App\Models\Task::titikStatus($k) }}"></i>{{ $lbl }}</span>
@endforeach
</div>
@forelse($overviewProyek as $p)
<div class="ov-row" data-ov-proyek="{{ $p['nama'] }}" role="button" tabindex="0" title="Saring berdasarkan {{ $p['nama'] }}">
<div class="ov-nama">
<i class="ov-dot" style="background:{{ $p['warna'] }}"></i>
<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p['nama'] }}</span>
</div>
<div class="stack">
@foreach($p['segmen'] as $sg)
@if($sg['w'] > 0)<i style="width:{{ $sg['w'] }}%;background:{{ $sg['warna'] }}" title="{{ $sg['label'] }}: {{ $sg['jumlah'] }}"></i>@endif
@endforeach
</div>
<div class="ov-meta">{{ $p['tugas'] }} tugas · {{ $p['pct'] }}%</div>
<a class="ico-btn xs" href="{{ route('admin.proyek.show', $p['id']) }}" title="Buka proyek" aria-label="Buka proyek {{ $p['nama'] }}">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M9 7h8v8"/></svg>
</a>
</div>
@empty
<div class="empty">Belum ada proyek.</div>
@endforelse
</div>
</div>

<div class="card">
<div class="card-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg></span>Overview per User</div>
<div class="card-desc">Bar penuh = seluruh tugas kontributor itu. {{ $overviewUser->count() }} kontributor dengan beban tertinggi.</div>
</div>
<div class="card-body">
<div class="legend">
@foreach(\App\Models\Task::daftarStatusSelesaiDulu() as $k => $lbl)
<span><i style="background:{{ \App\Models\Task::titikStatus($k) }}"></i>{{ $lbl }}</span>
@endforeach
</div>
@forelse($overviewUser as $u)
<div class="ov-row" data-ov-user="{{ $u['nama'] }}" role="button" tabindex="0" title="Saring berdasarkan {{ $u['nama'] }}">
<div class="ov-nama">
<span class="avatar" style="width:24px;height:24px;font-size:10px">{{ $u['inisial'] }}</span>
<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u['nama'] }}</span>
</div>
<div class="stack">
@foreach($u['segmen'] as $sg)
@if($sg['w'] > 0)<i style="width:{{ $sg['w'] }}%;background:{{ $sg['warna'] }}" title="{{ $sg['label'] }}: {{ $sg['jumlah'] }}"></i>@endif
@endforeach
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
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="6" height="16" x="4" y="4" rx="1"/><rect width="6" height="10" x="14" y="4" rx="1"/></svg></span>Kanban per Progress</div>
<div class="card-desc">Ringkasan tugas terbaru pada setiap status.</div>
</div>
<div class="dp-bar">
<label class="dp-cari">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
<input type="search" id="kbCari" placeholder="Cari judul, proyek, atau pemilik…" aria-label="Cari tugas">
</label>
<div class="dp-sel"><select id="kbProyek" data-select data-placeholder="Semua proyek">
<option value="">Semua proyek</option>
@foreach($projects as $p)<option value="{{ $p->nama }}" data-color="{{ $p->warna }}">{{ $p->nama }}</option>@endforeach
</select></div>
<div class="dp-sel"><select id="kbPrioritas" data-select data-placeholder="Semua prioritas">
<option value="">Semua prioritas</option>
@foreach(\App\Models\Task::daftarPrioritas() as $pr => $warna)<option value="{{ $pr }}" data-color="{{ $warna }}">{{ $pr }}</option>@endforeach
</select></div>
<button type="button" class="btn btn-sm" id="kbReset">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
Reset
</button>
</div>
<div class="card-body">
<div class="kanban" id="kanbanDash" data-kanban data-kanban-url="/tasks/__ID__/status">
@foreach(\App\Models\Task::daftarStatus() as $k => $lbl)
<div class="kcol" data-status="{{ $k }}">
<div class="kcol-h">
<span class="kcol-n">
<i class="kdot" style="background:{{ \App\Models\Task::titikStatus($k) }}"></i>
{{ $lbl }}
</span>
<span class="kcol-c">0</span>
</div>
{{-- Isi kolom dimuat bertahap dari /admin/kanban, 50 kartu sekali jalan. --}}
<div class="kcol-body" data-kb-body>
<div class="kb-state" data-kb-state></div>
</div>
</div>
@endforeach
</div>
</div>
</div>

<div class="card" style="margin-top:14px" data-timeline data-tl-key="admin" data-tl-default="3m">
<div class="card-head tl-head">
<div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg></span>Timeline Proyek</div>
<div class="card-desc">Rentangnya mengikuti filter di awal halaman.</div>
</div>
<div class="card-body" style="flex:1"><div data-tl-body></div></div>
<div class="card-foot" data-tl-note></div>
<script type="application/json" data-tl-data>@json($timeline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)</script>
</div>
@endsection
