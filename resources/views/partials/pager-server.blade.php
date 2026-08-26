{{-- Pager untuk daftar yang dipaginasi di server. Tampilannya sengaja sama
     dengan partials/pager (versi klien) supaya berpindah halaman terasa serupa
     di mana pun. Parameter query lain (cari, filter) ikut terbawa. --}}
@php($q = request()->except(['page', 'per_page']))
<div class="pager">
<span>Tampilkan</span>
<select onchange="location.href=this.value" aria-label="Jumlah baris per halaman">
@foreach([10, 25, 50, 100] as $n)
<option value="{{ $paginator->url(1) . '&' . http_build_query(array_merge($q, ['per_page' => $n])) }}"
        @selected($paginator->perPage() == $n)>{{ $n }}</option>
@endforeach
</select>
<span>baris</span>

<span style="margin-left:auto;color:var(--muted2)">
@if($paginator->total())
{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}
@else
0 dari 0
@endif
</span>

@if($paginator->onFirstPage())
<span class="pg-btn ico mati" aria-hidden="true">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
</span>
@else
<a class="pg-btn ico" href="{{ $paginator->previousPageUrl() }}" title="Halaman sebelumnya" aria-label="Halaman sebelumnya">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
</a>
@endif

@if($paginator->hasMorePages())
<a class="pg-btn ico" href="{{ $paginator->nextPageUrl() }}" title="Halaman berikutnya" aria-label="Halaman berikutnya">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
</a>
@else
<span class="pg-btn ico mati" aria-hidden="true">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
</span>
@endif

<form method="GET" style="display:flex;align-items:center;gap:6px">
@foreach($q as $k => $v)
@if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
@endforeach
<input type="hidden" name="per_page" value="{{ $paginator->perPage() }}">
<span>Ke halaman</span>
<input type="number" name="page" min="1" max="{{ max(1, $paginator->lastPage()) }}"
       value="{{ $paginator->currentPage() }}" aria-label="Nomor halaman">
<button class="pg-btn ico" type="submit" title="Buka halaman" aria-label="Buka halaman">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
</button>
</form>
</div>
