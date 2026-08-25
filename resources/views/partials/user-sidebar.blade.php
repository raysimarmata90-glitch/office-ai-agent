@php($u = auth()->user())
@php($riwayat = \App\Models\Conversation::with('pesanTerakhirUser')->where('user_id', $u->id)->orderByDesc('updated_at')->take(12)->get())
@php($aktifId = request()->route('conversation')?->id)
<aside class="sidebar" id="sidebar">
<div class="sb-head">
<div class="sb-logo img"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INAai"></div>
<div class="sb-title">INAai Agent</div>
<button class="sb-toggle" id="sbToggle" type="button" title="Ciutkan sidebar" aria-label="Ciutkan sidebar">
<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/></svg>
</button>
</div>

<a href="{{ route('chat.baru') }}" class="nav-item {{ request()->routeIs('chat.baru') ? 'active' : '' }}" title="Chat Baru">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
<span class="nav-label">Chat Baru</span>
</a>

<a href="{{ route('pekerjaan.index') }}" class="nav-item {{ request()->routeIs('pekerjaan.index') ? 'active' : '' }}" title="Pekerjaan Saya">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M8 2v4M16 2v4M3 10h18M9 16l2 2 4-4"/></svg>
<span class="nav-label">Pekerjaan Saya</span>
</a>

<button type="button" class="nav-item sb-hist-btn" id="histFlyBtn" title="Riwayat Chat" aria-label="Riwayat Chat">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
<span class="nav-label">Riwayat Chat</span>
</button>
<div class="sb-spacer"></div>

<div class="sb-sec nav-label">Riwayat</div>
<div class="sb-hist">
@forelse($riwayat as $c)
@include('partials.hist-item', ['c' => $c, 'aktifId' => $aktifId])
@empty
<div class="sb-empty">Belum ada riwayat.</div>
@endforelse
</div>

<div class="sb-foot">
<div class="uf-menu" id="ufMenu">
<div class="uf-head">
<div class="n">{{ $u->name }}</div>
<div class="e">{{ $u->email }}</div>
</div>
<a href="{{ route('pekerjaan.index') }}" class="mi">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
Lihat Profil
</a>
<form method="POST" action="{{ route('logout') }}">@csrf
<button type="submit" class="mi danger">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>
Logout
</button>
</form>
</div>
<button class="uf" id="ufBtn" type="button">
<div class="uf-av">{{ $u->inisial() }}</div>
<div class="uf-meta">
<div class="uf-nama">{{ $u->name }}</div>
<div class="uf-sub">{{ $u->department?->name ?? 'Pengguna' }}</div>
</div>
<svg class="uf-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 15 5 5 5-5M7 9l5-5 5 5"/></svg>
</button>
</div>
</aside>

{{-- Panel riwayat yang muncul di samping kanan sidebar saat sidebar diciutkan.
     Isinya dimuat bertahap 50 riwayat per permintaan. --}}
<div class="hist-fly" id="histFly">
<div class="hist-fly-h">
<div class="hist-fly-t">Riwayat Chat</div>
<button type="button" class="hist-fly-x" id="histFlyClose" aria-label="Tutup riwayat">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
</button>
</div>
<div class="sb-hist" id="histFlyList" style="display:flex">
<div class="hist-state" id="histFlyState"></div>
</div>
</div>
<script>
(function(){
const sb=document.getElementById('sidebar'),tg=document.getElementById('sbToggle');
const fly=document.getElementById('histFly'),flyBtn=document.getElementById('histFlyBtn'),flyX=document.getElementById('histFlyClose');
const KEY='inaai_sb_collapsed';
function labelToggle(){tg.title=sb.classList.contains('collapsed')?'Lebarkan sidebar':'Ciutkan sidebar';tg.setAttribute('aria-label',tg.title)}
if(localStorage.getItem(KEY)==='1')sb.classList.add('collapsed');
labelToggle();
tg.addEventListener('click',function(){
sb.classList.toggle('collapsed');
localStorage.setItem(KEY,sb.classList.contains('collapsed')?'1':'0');
if(!sb.classList.contains('collapsed'))fly.classList.remove('open');
labelToggle();
});

/* ===== Riwayat panel: muat 50 per permintaan, berhenti saat data habis ===== */
const daftar=document.getElementById('histFlyList'),state=document.getElementById('histFlyState');
const URL_RIWAYAT=@json(route('chat.riwayat'));
const AKTIF=@json($aktifId);
let lewati=0,habis=false,memuat=false,pernahMuat=false;

function tulisState(mode){
if(mode==='memuat'){state.className='hist-state load';state.innerHTML='<i class="hist-spin"></i>Memuat riwayat…'}
else if(mode==='habis'){state.className='hist-state';state.textContent=lewati?'Semua riwayat sudah ditampilkan.':'Belum ada riwayat.'}
else if(mode==='galat'){state.className='hist-state';state.textContent='Gagal memuat riwayat. Gulir lagi untuk mencoba.'}
else{state.className='hist-state';state.textContent=''}
}

function diDasar(){return daftar.scrollTop+daftar.clientHeight>=daftar.scrollHeight-48}

async function muat(){
if(habis||memuat)return;
memuat=true;tulisState('memuat');
try{
const q=new URLSearchParams({offset:lewati});
if(AKTIF)q.set('aktif',AKTIF);
const r=await fetch(URL_RIWAYAT+'?'+q.toString(),{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
if(!r.ok)throw new Error(r.status);
const d=await r.json();
state.insertAdjacentHTML('beforebegin',d.html);
lewati+=d.jumlah;
// Balasan kosong diperlakukan sebagai habis supaya offset tidak jalan di tempat.
habis=!!d.habis||!d.jumlah;
tulisState(habis?'habis':'');
}catch(e){
tulisState('galat');
memuat=false;
return;
}
memuat=false;
// Masih menempel di dasar (mis. gulir terjadi saat batch sebelumnya belum selesai):
// lanjut muat, karena tidak akan ada event scroll baru untuk memicunya.
if(!habis&&diDasar())muat();
}

/* Muat lanjutan saat gulir mendekati dasar panel. */
daftar.addEventListener('scroll',function(){
if(habis||memuat)return;
if(diDasar())muat();
});

/* Percakapan baru membuat daftar usang — muat ulang saat panel dibuka lagi. */
window.addEventListener('inaai:riwayat-usang',function(){
daftar.querySelectorAll('.hist').forEach(function(el){el.remove()});
lewati=0;habis=false;pernahMuat=false;tulisState('');
});

flyBtn.addEventListener('click',function(e){
e.stopPropagation();
fly.classList.toggle('open');
if(fly.classList.contains('open')&&!pernahMuat){pernahMuat=true;muat()}
});
flyX.addEventListener('click',function(){fly.classList.remove('open')});
fly.addEventListener('click',function(e){e.stopPropagation()});
document.addEventListener('click',function(){fly.classList.remove('open')});
document.addEventListener('keydown',function(e){if(e.key==='Escape')fly.classList.remove('open')});

const ub=document.getElementById('ufBtn'),um=document.getElementById('ufMenu');
ub.addEventListener('click',function(e){e.stopPropagation();um.classList.toggle('open')});
document.addEventListener('click',function(){um.classList.remove('open')});
um.addEventListener('click',function(e){e.stopPropagation()});
})();
</script>
