{{--
    Komponen modal global: cangkang + header (ikon, judul, deskripsi, tombol tutup).
    Isi diletakkan lewat slot, dengan pola section yang konsisten:

        <x-modal id="contohModal" judul="Judul" desc="Keterangan" :ikon="$svg">
            <form class="modal-form">
                <div class="modal-body"> ... </div>
                <div class="modal-foot"> ... </div>
            </form>
        </x-modal>

    Header tetap di tempat, hanya .modal-body yang bergulir, dan .modal-foot
    menempel di bawah dengan padding yang seragam atas–bawah.
--}}
@props([
    'id' => null,
    'judul' => '',
    'desc' => null,
    'ikon' => null,
    'lebar' => '560px',
])
<div class="modal-bg" @if($id) id="{{ $id }}" @endif role="dialog" aria-modal="true"
     @if($id) aria-labelledby="{{ $id }}Judul" @endif>
<div class="modal" style="max-width:{{ $lebar }}">
<div class="modal-head">
@if($ikon)<span class="ct-ico">{!! $ikon !!}</span>@endif
<div style="flex:1;min-width:0">
<div class="card-title" style="gap:0" @if($id) id="{{ $id }}Judul" @endif>{{ $judul }}</div>
@if($desc)<div class="card-desc">{{ $desc }}</div>@endif
</div>
<button type="button" class="hist-fly-x" data-modal-close aria-label="Tutup">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
</button>
</div>
{{ $slot }}
</div>
</div>
