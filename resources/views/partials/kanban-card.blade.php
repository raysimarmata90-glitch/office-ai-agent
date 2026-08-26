{{-- Satu kartu tugas pada kanban admin. Dipakai render awal maupun muatan
     bertahap lewat /admin/kanban, supaya bentuknya tidak pernah berbeda. --}}
@php($pr = \App\Models\Task::warnaPrioritas($t->prioritas))
<div class="ktask" draggable="true" data-tugas data-id="{{ $t->id }}" data-status="{{ $t->status }}">
<div class="ktask-top">
<div class="ktask-j">{{ $t->judul }}</div>
<button type="button" class="ico-btn xs" data-lihat="{{ $t->id }}" title="Lihat detail" aria-label="Lihat detail">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
</button>
<button type="button" class="ico-btn xs" data-ubah="{{ $t->id }}" title="Ubah tugas" aria-label="Ubah tugas">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
</button>
<button type="button" class="ico-btn xs" data-hapus="{{ $t->id }}" data-judul="{{ $t->judul }}" title="Hapus tugas" aria-label="Hapus tugas">
<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
</button>
</div>
<div class="ktask-tag">
<span class="badge" style="background:{{ $pr['bg'] }};color:{{ $pr['text'] }}">{{ $t->prioritas }}</span>
@if($t->user)
@php($wu = $t->user->warnaAvatar())
<span class="ktask-rev" title="{{ $t->user->name }}">
<span class="avatar xs" style="background:{{ $wu['bg'] }};color:{{ $wu['text'] }}">{{ $t->user->inisial() }}</span>
</span>
@endif
</div>
<div class="ktask-m">
<span class="ktask-p">
<i class="kdot" style="background:{{ $t->project?->warna ?? 'var(--muted3)' }}"></i>
{{ $t->project?->nama ?? 'Tanpa Proyek' }}
</span>
</div>
<div class="ktask-ev">
<span>{{ $t->user?->name ?? '–' }}</span>
<span class="ktask-w" title="Terakhir diperbarui">
<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
{{ $t->waktuSingkat() }}
</span>
</div>
</div>
