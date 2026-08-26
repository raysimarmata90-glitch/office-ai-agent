{{-- Header kolom tabel yang bisa diurutkan.
     $label  : teks header (kosongkan bila cukup ikon saja)
     $ikon   : isi <svg> opsional, ditampilkan di depan label
     $judul  : tooltip, dipakai saat header hanya berupa ikon --}}
<th class="sortable" @if(!empty($judul)) title="{{ $judul }}" @endif>
<span class="th-in">
@if(!empty($ikon))
<svg class="th-ico" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ikon !!}</svg>
@endif
@if(!empty($label)){{ $label }}@endif
<svg class="sort-ico" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 15 5 5 5-5M7 9l5-5 5 5"/></svg>
</span>
</th>
