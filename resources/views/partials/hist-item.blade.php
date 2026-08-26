{{-- Satu baris riwayat chat: baris 1 = ikon + nama proyek + waktu, baris 2 = judul tugas --}}
<a href="{{ route('chat.show', $c->id) }}"
   class="hist {{ ($aktifId ?? null) === $c->id ? 'active' : '' }}"
   title="{{ $c->title ?? 'Percakapan' }}">
<div class="hist-r1">
<svg class="hist-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
<span class="hist-p">{{ $c->namaProyek() }}</span>
<span class="hist-t">{{ $c->waktuRingkas() }}</span>
</div>
<div class="hist-r2">{{ $c->judulTugas() }}</div>
</a>
