@php($u = auth()->user())
<aside class="sidebar" id="sidebar">
<div class="sb-head">
<div class="sb-logo img"><img src="{{ asset('img/logo-inaai.webp') }}" alt="INAai"></div>
<div class="sb-title">INAai Project</div>
<button class="sb-toggle" id="sbToggle" type="button" title="Ciutkan sidebar" aria-label="Ciutkan sidebar">
<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/></svg>
</button>
</div>

<a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" title="Dashboard">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
<span class="nav-label">Dashboard</span>
</a>

<a href="{{ route('admin.proyek.index') }}" class="nav-item {{ request()->routeIs('admin.proyek.*') ? 'active' : '' }}" title="Proyek">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
<span class="nav-label">Proyek</span>
</a>

<a href="{{ route('admin.pekerjaan') }}" class="nav-item {{ request()->routeIs('admin.pekerjaan') ? 'active' : '' }}" title="Tugas">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 12H3M16 6H3M16 18H3"/><path d="m18 9 3 3-3 3"/></svg>
<span class="nav-label">Tugas</span>
</a>

<a href="{{ route('admin.users') }}" class="nav-item {{ request()->routeIs('admin.users') ? 'active' : '' }}" title="Tim">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
<span class="nav-label">Tim</span>
</a>

<a href="{{ route('admin.laporan') }}" class="nav-item {{ request()->routeIs('admin.laporan') ? 'active' : '' }}" title="Laporan">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
<span class="nav-label">Laporan</span>
</a>

<a href="{{ route('admin.conversations') }}" class="nav-item {{ request()->routeIs('admin.conversations') || request()->routeIs('admin.conversation.detail') ? 'active' : '' }}" title="Percakapan">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
<span class="nav-label">Percakapan</span>
</a>

<a href="{{ route('admin.chat.histories') }}" class="nav-item {{ request()->routeIs('admin.chat.histories') ? 'active' : '' }}" title="Riwayat Chat">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5M12 7v5l4 2"/></svg>
<span class="nav-label">Riwayat Chat</span>
</a>

<div class="sb-foot">
<div class="uf-menu" id="ufMenu">
<div class="uf-head">
<div class="n">{{ $u->name }}</div>
<div class="e">{{ $u->email }}</div>
</div>
<a href="{{ route('admin.users') }}" class="mi">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
Lihat Profil
</a>
<form method="POST" action="{{ route('logout') }}">@csrf
<button type="submit" class="mi danger">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></svg>
Keluar
</button>
</form>
</div>
<button class="uf" id="ufBtn" type="button">
<div class="uf-av">{{ $u->inisial() }}</div>
<div class="uf-meta">
<div class="uf-nama">{{ $u->name }}</div>
<div class="uf-sub">{{ $u->role?->display_name ?? 'Admin' }}</div>
</div>
<svg class="uf-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 15 5 5 5-5M7 9l5-5 5 5"/></svg>
</button>
</div>
</aside>
<script>
(function(){
const sb=document.getElementById('sidebar'),tg=document.getElementById('sbToggle');
const KEY='inaai_sb_collapsed';
if(localStorage.getItem(KEY)==='1')sb.classList.add('collapsed');
tg.addEventListener('click',function(){
sb.classList.toggle('collapsed');
localStorage.setItem(KEY,sb.classList.contains('collapsed')?'1':'0');
tg.title=sb.classList.contains('collapsed')?'Lebarkan sidebar':'Ciutkan sidebar';
});
const ub=document.getElementById('ufBtn'),um=document.getElementById('ufMenu');
ub.addEventListener('click',function(e){e.stopPropagation();um.classList.toggle('open')});
document.addEventListener('click',function(){um.classList.remove('open')});
um.addEventListener('click',function(e){e.stopPropagation()});
})();
</script>
