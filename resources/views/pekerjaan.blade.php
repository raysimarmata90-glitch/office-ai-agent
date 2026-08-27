@extends('layouts.user')
@section('title', 'Pekerjaan Saya')
@section('page-title', 'Pekerjaan Saya')
@section('page-sub', $ringkas['total'] . ' tugas · ' . $user->name)

@section('topbar-actions')
    <a href="{{ route('dashboard') }}" class="btn">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z" />
        </svg>
        Chat
    </a>
    <button class="btn btn-primary" type="button" data-open-task>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M12 5v14" />
        </svg>
        Tugas Baru
    </button>
@endsection


@section('content')
    <div class="page-col">
        {{-- Filter rentang berlaku untuk seluruh data di halaman ini --}}
        <div class="page-bar">
            <span class="page-bar-l">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 5h18M6 12h12M10 19h4" />
                </svg>
                Rentang
            </span>
            <div class="tl-filter" id="rentangHalaman" role="group" aria-label="Rentang tampilan halaman"></div>
            <span class="page-bar-l" id="rentangInfo" style="margin-left:auto;font-weight:500;color:var(--muted3)"></span>
        </div>

        {{-- Komposisi status: pie chart + bar seluruh status (selalu tampil, termasuk yang kosong) --}}
        @php($totalTugas = $ringkas['total'])
        <div class="card">
            <div class="card-head">
                <div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M21.2 15.9A10 10 0 1 1 8.1 2.8" />
                            <path d="M22 12A10 10 0 0 0 12 2v10Z" />
                        </svg></span>Komposisi Status</div>
                <div class="card-desc">{{ $totalTugas }} tugas · sebaran seluruh status pekerjaan Anda.</div>
            </div>
            <div class="card-body">
                <div class="komposisi">
                    <div class="pie-wrap">
                        <div class="pie" style="background:conic-gradient({{ $gradienStatus }})" role="img"
                            aria-label="Sebaran status: {{ collect($statusRingkas)->map(fn($x) => $x['label'] . ' ' . $x['jumlah'])->implode(', ') }}">
                            <div class="pie-hole">
                                <span class="pie-n">{{ $totalTugas }}</span>
                                <span class="pie-l">tugas</span>
                            </div>
                        </div>
                    </div>

                    <div class="komposisi-bar">
                        @foreach ($statusRingkas as $st)
                            <div class="kb-row" data-status-bar="{{ $st['key'] }}" role="button" tabindex="0"
                                title="Klik untuk melihat rincian {{ $st['label'] }}">
                                <div class="kb-head">
                                    <span class="kb-nama"><i class="kdot"
                                            style="background:{{ $st['warna'] }}"></i>{{ $st['label'] }}</span>
                                    <span class="kb-nilai" style="text-align:right">
                                        <span data-kb-jumlah>{{ $st['jumlah'] }}</span> tugas
                                        <span class="kb-sub" style="display:block" data-kb-proyek>{{ $st['proyek'] }}
                                            proyek</span>
                                    </span>
                                </div>
                                <div class="kb-track"><i data-kb-bar
                                        style="width:{{ $st['persen'] }}%;background:{{ $st['warna'] }}"></i></div>
                            </div>
                        @endforeach
                        <div class="kb-drill" id="statusDrill" style="display:none"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:14px" data-timeline data-tl-key="pekerjaan" data-tl-default="30d">
            <div class="card-head tl-head">
                <div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" />
                            <path d="M8 2v4M16 2v4M3 10h18" />
                        </svg></span>Timeline Pekerjaan</div>
            </div>
            <div class="card-body">
                <div data-tl-body>
                    <div class="empty">Memuat timeline…</div>
                </div>
            </div>
            <div class="card-foot" data-tl-note>Warna bar mengikuti status tugas.</div>
            <script type="application/json" data-tl-data>@json($timeline, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)</script>
        </div>

        <div class="card" style="margin-top:14px" data-table id="daftarCard">
            <div class="card-head tl-head">
                <div class="card-title"><span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M3 3h18v18H3z" />
                            <path d="M3 9h18M3 15h18M9 3v18" />
                        </svg></span>Daftar Pekerjaan</div>
                <div class="tl-filter" id="viewSwitch" role="group" aria-label="Tampilan daftar pekerjaan">
                    <button type="button" class="tl-f on" data-view="kanban">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="6" height="16" x="4" y="4" rx="1" />
                            <rect width="6" height="10" x="14" y="4" rx="1" />
                        </svg>
                        Kanban
                    </button>
                    <button type="button" class="tl-f" data-view="tabel">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3h18v18H3z" />
                            <path d="M3 9h18M3 15h18M9 3v18" />
                        </svg>
                        Table
                    </button>
                </div>
            </div>

            <div class="dp-bar">
                <label class="dp-cari">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    <input type="search" id="dpCari" placeholder="Cari judul, proyek, atau reviewer…"
                        aria-label="Cari pekerjaan">
                </label>
                <div class="dp-sel"><select id="dpProyek" data-select data-placeholder="Semua proyek">
                        <option value="">Semua proyek</option>
                        @foreach ($tasks->pluck('project.nama')->filter()->unique()->sort() as $np)
                            <option value="{{ $np }}">{{ $np }}</option>
                        @endforeach
                    </select></div>
                <div class="dp-sel" id="dpStatusWrap"><select id="dpStatus" data-select
                        data-placeholder="Semua status">
                        <option value="">Semua status</option>
                        @foreach (\App\Models\Task::daftarStatus() as $k => $lbl)
                            <option value="{{ $k }}" data-color="{{ \App\Models\Task::titikStatus($k) }}">
                                {{ $lbl }}</option>
                        @endforeach
                    </select></div>
                <div class="dp-sel"><select id="dpPrioritas" data-select data-placeholder="Semua prioritas">
                        <option value="">Semua prioritas</option>
                        @foreach (\App\Models\Task::daftarPrioritas() as $pr => $warna)
                            <option value="{{ $pr }}" data-color="{{ $warna }}">{{ $pr }}
                            </option>
                        @endforeach
                    </select></div>
                <button type="button" class="btn btn-sm dp-reset" id="dpReset">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
                        <path d="M3 3v5h5" />
                    </svg>
                    Reset
                </button>
            </div>
            <div class="card-body" data-view-panel="kanban">
                <div class="kanban" data-kanban data-kanban-url="/tasks/__ID__/status">
                    @foreach ($kanban as $col)
                        <div class="kcol" data-status="{{ $col['key'] }}">
                            <div class="kcol-h">
                                <span class="kcol-n">
                                    <i class="kdot"
                                        style="background:{{ \App\Models\Task::titikStatus($col['key']) }}"></i>
                                    {{ $col['nama'] }}
                                </span>
                                <span class="kcol-c">{{ $col['count'] }}</span>
                            </div>
                            <div class="kcol-body">
                                @forelse($col['items'] as $t)
                                    @php($pr = \App\Models\Task::warnaPrioritas($t->prioritas))
                                    <div class="ktask" draggable="true" data-task="{{ $t->id }}"
                                        data-status="{{ $t->status }}" data-proyek="{{ $t->project?->nama }}"
                                        data-prioritas="{{ $t->prioritas }}"
                                        data-cari="{{ Str::lower($t->judul . ' ' . ($t->project?->nama ?? '') . ' ' . ($t->reviewer?->name ?? '')) }}"
                                        data-mulai="{{ $t->mulai?->toDateString() }}"
                                        data-selesai="{{ $t->selesai?->toDateString() }}">
                                        <div class="ktask-top">
                                            <div class="ktask-j">{{ $t->judul }}</div>
                                            @if ($bolehEdit[$t->id] ?? false)
                                                <button type="button" class="ico-btn xs"
                                                    data-ubah="{{ $t->id }}" title="Ubah tugas"
                                                    aria-label="Ubah tugas">
                                                    <svg width="13" height="13" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                    </svg>
                                                </button>
                                                <button type="button" class="ico-btn xs"
                                                    data-hapus="{{ $t->id }}" data-judul="{{ $t->judul }}"
                                                    title="Hapus tugas" aria-label="Hapus tugas">
                                                    <svg width="13" height="13" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                        <div class="ktask-tag">
                                            <span class="badge"
                                                style="background:{{ $pr['bg'] }};color:{{ $pr['text'] }}">{{ $t->prioritas }}</span>
                                            @if ($t->reviewer)
                                                @php($wr = $t->reviewer->warnaAvatar())
                                                <span class="ktask-rev" title="Reviewer: {{ $t->reviewer->name }}">
                                                    <span class="avatar xs"
                                                        style="background:{{ $wr['bg'] }};color:{{ $wr['text'] }}">{{ $t->reviewer->inisial() }}</span>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="ktask-m">
                                            <span class="ktask-p">{{ $t->project?->nama }}</span>
                                        </div>
                                        <div class="ktask-ev">
                                            <span>{{ $t->evidences->count() }} evidence</span>
                                            <span class="ktask-w" title="Terakhir diperbarui">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="9" />
                                                    <path d="M12 7v5l3 2" />
                                                </svg>
                                                {{ $t->waktuSingkat() }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="kosong">
                                        <span class="kosong-ico"><svg width="18" height="18" viewBox="0 0 24 24"
                                                fill="none" stroke="currentColor" stroke-width="1.8"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <rect width="18" height="18" x="3" y="3" rx="2" />
                                                <path d="M9 12h6" />
                                            </svg></span>
                                        <span class="kosong-t">Kosong</span>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div data-view-panel="tabel" style="display:none">
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th style="width:52px">No</th>
                                @include('partials.th-sort', [
                                    'label' => 'Judul',
                                    'ikon' => '<path d="M4 6h16M4 12h10M4 18h7"/>',
                                ])
                                @include('partials.th-sort', [
                                    'label' => 'Proyek',
                                    'ikon' =>
                                        '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.7-.9L9.6 3.9A2 2 0 0 0 7.9 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>',
                                ])
                                @include('partials.th-sort', [
                                    'label' => 'Status',
                                    'ikon' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
                                ])
                                @include('partials.th-sort', [
                                    'label' => 'Prioritas',
                                    'ikon' =>
                                        '<path d="m12 2 2.4 7.4H22l-6 4.5 2.3 7.1-6.3-4.6-6.3 4.6L7.9 14 2 9.4h7.6Z"/>',
                                ])
                                @include('partials.th-sort', [
                                    'label' => 'Reviewer',
                                    'ikon' =>
                                        '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
                                ])
                                @include('partials.th-sort', [
                                    'label' => '',
                                    'judul' => 'Evidence',
                                    'ikon' =>
                                        '<path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
                                ])
                                @include('partials.th-sort', [
                                    'label' => 'Dibuat',
                                    'ikon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
                                ])
                                <th style="width:112px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $t)
                                @php($w = \App\Models\Task::warnaStatus($t->status))
                                <tr data-row data-status="{{ $t->status }}" data-proyek="{{ $t->project?->nama }}"
                                    data-prioritas="{{ $t->prioritas }}"
                                    data-cari="{{ Str::lower($t->judul . ' ' . ($t->project?->nama ?? '') . ' ' . ($t->reviewer?->name ?? '')) }}"
                                    data-mulai="{{ $t->mulai?->toDateString() }}"
                                    data-selesai="{{ $t->selesai?->toDateString() }}">
                                    <td data-no></td>
                                    <td data-sort="{{ $t->judul }}">
                                        <strong class="potong" style="--w:290px"
                                            title="{{ $t->judul }}">{{ $t->judul }}</strong>
                                    </td>
                                    <td data-sort="{{ $t->project?->nama }}">
                                        <span class="potong" style="--w:170px"
                                            title="{{ $t->project?->nama }}">{{ $t->project?->nama ?? '–' }}</span>
                                    </td>
                                    <td data-sort="{{ $t->status }}"><span class="badge"
                                            style="background:{{ $w['bg'] }};color:{{ $w['text'] }}">{{ $t->statusLabel() }}</span>
                                    </td>
                                    @php($pr = \App\Models\Task::warnaPrioritas($t->prioritas))
                                    <td data-sort="{{ $t->prioritas }}"><span class="badge"
                                            style="background:{{ $pr['bg'] }};color:{{ $pr['text'] }}">{{ $t->prioritas }}</span>
                                    </td>
                                    <td data-sort="{{ $t->reviewer?->name }}">
                                        @if ($t->reviewer)
                                            @php($wr = $t->reviewer->warnaAvatar())
                                            <div style="display:flex;align-items:center;gap:7px">
                                                <span class="avatar xs"
                                                    style="background:{{ $wr['bg'] }};color:{{ $wr['text'] }}">{{ $t->reviewer->inisial() }}</span>
                                                <span class="potong" style="--w:140px"
                                                    title="{{ $t->reviewer->name }}">{{ $t->reviewer->name }}</span>
                                            </div>
                                        @else
                                            <span style="color:var(--muted3)">–</span>
                                        @endif
                                    </td>
                                    <td data-sort="{{ $t->evidences->count() }}" style="white-space:nowrap">
                                        @if ($t->evidences->count())
                                            {{ $t->evidences->count() }} file{{ $t->evidences->count() > 1 ? 's' : '' }}
                                        @else
                                            <span style="color:var(--muted3)">–</span>
                                        @endif
                                    </td>
                                    <td data-sort="{{ $t->created_at?->timestamp }}"
                                        style="white-space:nowrap;color:var(--muted2);font-size:12px">
                                        {{ $t->created_at?->translatedFormat('d M y, H:i') ?? '–' }}</td>
                                    <td>
                                        <div class="aksi">
                                            <button type="button" class="ico-btn" data-lihat="{{ $t->id }}"
                                                title="Lihat detail" aria-label="Lihat detail">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </button>
                                            @if ($bolehEdit[$t->id] ?? false)
                                                <button type="button" class="ico-btn" data-ubah="{{ $t->id }}"
                                                    title="Ubah tugas" aria-label="Ubah tugas">
                                                    <svg width="15" height="15" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                    </svg>
                                                </button>
                                                <button type="button" class="ico-btn" data-hapus="{{ $t->id }}"
                                                    data-judul="{{ $t->judul }}" title="Hapus tugas"
                                                    aria-label="Hapus tugas">
                                                    <svg width="15" height="15" viewBox="0 0 24 24"
                                                        fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round">
                                                        <path
                                                            d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div data-empty class="kosong" style="display:none">
                    <span class="kosong-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3h18v18H3z" />
                            <path d="M3 9h18M9 9v12" />
                        </svg></span>
                    <span class="kosong-t">Belum ada pekerjaan</span>
                    <span class="kosong-s">Tidak ada tugas yang cocok dengan filter atau kata kunci saat ini.</span>
                </div>
                @include('partials.pager')
            </div>
        </div>

    </div>{{-- /page-col --}}

    <div class="modal-bg" id="taskModal">
        <div class="modal">
            <form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data" id="taskForm"
                class="modal-form">@csrf
                <input type="hidden" name="_method" id="taskMethod" disabled>
                <div class="modal-head">
                    <div style="flex:1">
                        <div class="card-title"><span class="ct-ico"><svg width="15" height="15"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg></span><span id="taskFormJudul">Tugas Baru</span></div>
                        <div class="card-desc" id="taskFormDesc">Lengkapi detail, lampirkan evidence, lalu assign dan
                            minta review.</div>
                    </div>
                    <button type="button" class="btn btn-sm" data-close-task>✕</button>
                </div>
                <div class="modal-body">
                    <div class="fld">
                        <label>Judul Tugas</label>
                        <input type="text" name="judul" required placeholder="Contoh: Integrasi API pembayaran"
                            value="{{ old('judul') }}">
                    </div>
                    <div class="row2">
                        <div class="fld">
                            <label>Proyek</label>
                            <select name="project_id" id="taskProject" data-select data-placeholder="Pilih proyek">
                                <option value="">— Proyek baru —</option>
                                @foreach ($projects as $p)
                                    <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>
                                        {{ $p->client_or_rd }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fld" id="taskProjectBaruFld">
                            <label>Nama Proyek Baru</label>
                            <input type="text" name="project_baru" id="taskProjectBaru"
                                placeholder="Isi nama proyek baru" value="{{ old('project_baru') }}">
                        </div>
                    </div>
                    <div class="row2">
                        <div class="fld">
                            <label>Status Saat Ini</label>
                            <select name="status" required data-select data-placeholder="Pilih status">
                                @foreach (\App\Models\Task::daftarStatus() as $k => $lbl)
                                    <option value="{{ $k }}"
                                        data-color="{{ \App\Models\Task::titikStatus($k) }}"
                                        @selected(old('status', 'to_do') === $k)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="fld">
                            <label>Prioritas</label>
                            <select name="prioritas" required data-select data-placeholder="Pilih prioritas">
                                @foreach (\App\Models\Task::daftarPrioritas() as $pr => $warna)
                                    <option value="{{ $pr }}" data-color="{{ $warna }}"
                                        @selected(old('prioritas', 'Sedang') === $pr)>{{ $pr }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row2">
                        <div class="fld">
                            <label>Waktu Mulai</label>
                            <input type="text" name="mulai" id="taskMulai" required data-datepicker
                                value="{{ old('mulai', now()->toDateString()) }}">
                        </div>
                        <div class="fld">
                            <label>Waktu Selesai</label>
                            <input type="text" name="selesai" id="taskSelesai" required data-datepicker
                                data-min="{{ old('mulai', now()->toDateString()) }}"
                                value="{{ old('selesai', now()->addWeek()->toDateString()) }}">
                        </div>
                    </div>
                    <div class="row2">
                        <div class="fld">
                            <label>Assign ke</label>
                            @if ($user->isAdmin())
                                <select name="user_id" id="taskUser" required data-select
                                    data-placeholder="Pilih pegawai">
                                    <option value="{{ $user->id }}">{{ $user->name }} (saya)</option>
                                    @foreach ($rekan->where('id', '!=', $user->id) as $r)
<option value="{{ $r->id }}" @selected(old('user_id') == $r->id)>{{ $r->name }}</option>
@endforeach
</select>
@else
{{-- Non-admin tidak boleh memindahkan tugas ke orang lain. --}}
<select id="taskUser" data-select disabled>
<option value="{{ $user->id }}">{{ $user->name }} (saya)</option>
@foreach ($rekan->where('id', '!=', $user->id) as $r)
<option value="{{ $r->id }}">{{ $r->name }}</option>
@endforeach
</select>
<input type="hidden" name="user_id" id="taskUserVal" value="{{ $user->id }}">
@endif
</div>
<div class="fld">
<label>Request Reviewer</label>
<select name="reviewer_id" data-select data-placeholder="Pilih reviewer">
<option value="">— Tanpa reviewer —</option>
@foreach ($rekan->where('id', '!=', $user->id) as $r)
<option value="{{ $r->id }}" @selected(old('reviewer_id') == $r->id)>{{ $r->name }}</option>
@endforeach
</select>
</div>
</div>
<div class="fld">
<label>Deskripsi</label>
<textarea name="deskripsi" rows="3" placeholder="Detail pekerjaan (opsional)">{{ old('deskripsi') }}</textarea>
</div>
<div class="fld" id="taskEvidenceLama" style="display:none">
<label>File Saat Ini</label>
<div class="drw-ev" id="taskEvidenceList"></div>
</div>
<div class="fld">
<label>Tambah Evidence (dokumen / gambar)</label>
<input type="file" name="evidence[]" multiple
       data-upload
       data-max-size="{{ $maksUnggahMb ?? 2 }}"
       data-judul="Seret file ke sini atau <b>pilih dari perangkat</b>"
       accept="{{ $acceptUnggah ?? '.pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp' }}">
</div>
</div>
<div class="modal-foot">
<button type="button" class="btn" data-close-task>
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
Batal
</button>
<button type="submit" class="btn btn-primary" id="taskSubmit">
<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
<span id="taskSubmitLabel">Simpan Tugas</span>
</button>
</div>
</form>
</div>
</div>
{{-- Drawer detail tugas, muncul dari kanan layar --}}
<div class="drw-bg" id="taskDrawer">
<aside class="drw" role="dialog" aria-modal="true" aria-labelledby="drwJudul">
<div class="drw-head">
<span class="ct-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></span>
<div class="drw-t" id="drwJudul">Detail Tugas</div>
<button type="button" class="hist-fly-x" data-drw-close aria-label="Tutup detail">
<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
</button>
</div>
<div class="drw-body" id="drwBody"></div>
<div class="drw-foot" id="drwFoot"></div>
</aside>
</div>
@endsection

@push('script')
    <script>
        (function() {
            const m = document.getElementById('taskModal');
            document.querySelectorAll('[data-open-task]').forEach(function(b) {
                b.addEventListener('click', function() {
                    resetFormTambah();
                    m.classList.add('open')
                })
            });
            document.querySelectorAll('[data-close-task]').forEach(function(b) {
                b.addEventListener('click', function() {
                    m.classList.remove('open')
                })
            });
            m.addEventListener('click', function(e) {
                if (e.target === m) m.classList.remove('open')
            });
            const sel = document.getElementById('taskProject'),
                baru = document.getElementById('taskProjectBaru'),
                baruFld = document.getElementById('taskProjectBaruFld');

            function sync() {
                const proyekBaru = !sel.value;
                baruFld.style.display = proyekBaru ? '' : 'none';
                baru.disabled = !proyekBaru;
                if (!proyekBaru) baru.value = '';
            }
            sel.addEventListener('change', sync);
            sync();

            // Waktu selesai mengikuti batas bawah waktu mulai.
            const dMulai = document.getElementById('taskMulai'),
                dSelesai = document.getElementById('taskSelesai');
            if (dMulai && dSelesai) {
                dMulai.addEventListener('change', function() {
                    if (dSelesai.inaaiDt) dSelesai.inaaiDt.aturMin(dMulai.value);
                    if (dMulai.value && dSelesai.value && dSelesai.value < dMulai.value) {
                        dSelesai.value = dMulai.value;
                        dSelesai.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                });
            }
            @if ($errors->any()) m.classList.add('open'); @endif

            const token = document.querySelector('meta[name="csrf-token"]').content;

            /* ===== Drawer detail + mode ubah ===== */
            const drw = document.getElementById('taskDrawer'),
                drwBody = document.getElementById('drwBody'),
                drwFoot = document.getElementById('drwFoot'),
                drwJudul = document.getElementById('drwJudul');
            const form = document.getElementById('taskForm'),
                metode = document.getElementById('taskMethod'),
                fJudul = document.getElementById('taskFormJudul'),
                fDesc = document.getElementById('taskFormDesc'),
                fSubmit = document.getElementById('taskSubmit');
            const aksiStore = form.getAttribute('action');

            function esc(v) {
                return String(v == null ? '' : v).replace(/[&<>'"]/g, c => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#039;',
                    '"': '&quot;'
                } [c]))
            }
            /* Pakai ulang pemetaan jenis file dari komponen upload agar ikon & labelnya seragam. */
            function jenisBerkas(nama, mime) {
                if (window.InaaiUpload && window.InaaiUpload.jenisFile) return window.InaaiUpload.jenisFile({
                    name: nama || '',
                    type: mime || ''
                });
                return {
                    label: 'Berkas',
                    kelas: 'lain',
                    ikon: ''
                };
            }

            function ukuran(b) {
                if (window.InaaiUpload && window.InaaiUpload.ukuran) return window.InaaiUpload.ukuran(b || 0);
                return (b || 0) + ' B';
            }

            function tutupDrawer() {
                drw.classList.remove('open');
                document.body.style.overflow = ''
            }
            drw.addEventListener('click', function(e) {
                if (e.target === drw) tutupDrawer()
            });
            document.querySelectorAll('[data-drw-close]').forEach(b => b.addEventListener('click', tutupDrawer));
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && drw.classList.contains('open')) tutupDrawer()
            });

            function barisDetail(label, isi) {
                return '<div class="drw-row"><div class="drw-k">' + esc(label) + '</div><div class="drw-v">' + isi +
                    '</div></div>'
            }

            async function ambilTugas(id) {
                const r = await fetch('/tasks/' + id, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!r.ok) throw new Error(r.status);
                return r.json();
            }

            async function bukaDrawer(id) {
                drwBody.innerHTML = '<div class="empty">Memuat detail…</div>';
                drwFoot.innerHTML = '';
                drw.classList.add('open');
                document.body.style.overflow = 'hidden';
                let d;
                try {
                    d = await ambilTugas(id)
                } catch (e) {
                    drwBody.innerHTML = '<div class="empty">Gagal memuat detail tugas.</div>';
                    return
                }

                drwJudul.textContent = d.judul || 'Detail Tugas';
                const w = d.status_warna || {};
                let html = '';
                html += barisDetail('Status', '<span class="badge" style="background:' + esc(w.bg) + ';color:' +
                    esc(w.text) + '">' + esc(d.status_label) + '</span>');
                html += barisDetail('Proyek',
                    '<span style="display:inline-flex;align-items:center;gap:7px"><i style="width:8px;height:8px;border-radius:2px;background:' +
                    esc(d.warna || '#f55d14') + ';display:block"></i>' + esc(d.proyek || '–') + '</span>');
                html += barisDetail('Prioritas', esc(d.prioritas || '–'));
                html += barisDetail('Periode', esc((d.mulai || '–') + ' – ' + (d.selesai || '–')));
                html += barisDetail('Assign ke', esc(d.pemilik || '–'));
                html += barisDetail('Reviewer', esc(d.reviewer || '–'));
                html += barisDetail('Dibuat', esc(d.dibuat || '–'));
                html += barisDetail('Diubah', esc(d.diubah || '–'));
                if (d.deskripsi) html += barisDetail('Deskripsi', '<span style="white-space:pre-wrap">' + esc(d
                    .deskripsi) + '</span>');

                const ev = d.evidences || [];
                html += '<div class="drw-sec">Evidence <span class="drw-n">' + ev.length + '</span></div>';
                if (ev.length) {
                    html += '<div class="drw-ev">' + ev.map(function(e) {
                        const j = jenisBerkas(e.nama, e.mime);
                        return '<div class="drw-evi">' +
                            '<span class="i-up-fi ' + j.kelas + '">' + j.ikon + '</span>' +
                            '<span class="i-up-meta"><span class="i-up-fn" title="' + esc(e.nama) + '">' +
                            esc(e.nama) + '</span>' +
                            '<span class="i-up-fs">' + esc(j.label) + ' · ' + esc(ukuran(e.ukuran)) +
                            '</span></span>' +
                            '<a class="ico-btn" href="' + esc(e.url) +
                            '" target="_blank" rel="noopener" title="Lihat file" aria-label="Lihat file">' +
                            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></a>' +
                            '<a class="ico-btn" href="' + esc(e.unduh) +
                            '" title="Unduh file" aria-label="Unduh file">' +
                            '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></svg></a>' +
                            '</div>';
                    }).join('') + '</div>';
                } else {
                    html += '<div class="empty" style="padding:16px">Belum ada evidence pada tugas ini.</div>';
                }
                drwBody.innerHTML = html;

                const IKO_X =
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
                const IKO_PENSIL =
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
                const IKO_SAMPAH =
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
                // Hapus dipisah di pojok kiri agar tidak bersebelahan dengan aksi aman.
                drwFoot.innerHTML =
                    (d.boleh_edit ? '<button type="button" class="btn btn-danger" data-drw-hapus="' + d.id +
                        '" data-judul="' + esc(d.judul) + '">' + IKO_SAMPAH + 'Hapus</button>' : '') +
                    '<span class="drw-foot-sela"></span>' +
                    '<button type="button" class="btn" data-drw-close>' + IKO_X + 'Tutup</button>' +
                    (d.boleh_edit ? '<button type="button" class="btn btn-primary" data-drw-ubah="' + d.id + '">' +
                        IKO_PENSIL + 'Ubah Tugas</button>' : '');
                drwFoot.querySelectorAll('[data-drw-close]').forEach(b => b.addEventListener('click', tutupDrawer));
                const bUbah = drwFoot.querySelector('[data-drw-ubah]');
                if (bUbah) bUbah.addEventListener('click', function() {
                    tutupDrawer();
                    bukaUbah(d.id)
                });
                const bHapus = drwFoot.querySelector('[data-drw-hapus]');
                if (bHapus) bHapus.addEventListener('click', function() {
                    tutupDrawer();
                    hapusTugas(d.id, d.judul || '')
                });
            }

            function setSelect(nama, nilai) {
                let el = form.querySelector('select[name="' + nama + '"]');
                // Saat "Assign ke" dikunci, select-nya tanpa name — nilainya disimpan di input tersembunyi.
                if (!el && nama === 'user_id') el = document.getElementById('taskUser');
                if (!el) return;
                el.value = nilai == null ? '' : String(nilai);
                el.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                const hidden = document.getElementById('taskUserVal');
                if (nama === 'user_id' && hidden && nilai != null) hidden.value = String(nilai);
            }

            /* Daftar file yang sudah melekat pada tugas, lengkap dengan tombol hapus. */
            function gambarEvidenceLama(daftar) {
                const fld = document.getElementById('taskEvidenceLama'),
                    list = document.getElementById('taskEvidenceList');
                if (!daftar || !daftar.length) {
                    fld.style.display = 'none';
                    list.innerHTML = '';
                    return
                }
                fld.style.display = '';
                list.innerHTML = daftar.map(function(e) {
                    const j = jenisBerkas(e.nama, e.mime);
                    return '<div class="drw-evi" data-ev="' + e.id + '">' +
                        '<span class="i-up-fi ' + j.kelas + '">' + j.ikon + '</span>' +
                        '<span class="i-up-meta"><span class="i-up-fn" title="' + esc(e.nama) + '">' + esc(e
                            .nama) + '</span>' +
                        '<span class="i-up-fs">' + esc(j.label) + ' · ' + esc(ukuran(e.ukuran)) +
                        '</span></span>' +
                        '<a class="ico-btn" href="' + esc(e.url) +
                        '" target="_blank" rel="noopener" title="Lihat file" aria-label="Lihat file">' +
                        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg></a>' +
                        '<button type="button" class="ico-btn" data-hapus-ev="' + e.id + '" data-nama="' + esc(e
                            .nama) + '" title="Hapus file" aria-label="Hapus file">' +
                        '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>' +
                        '</div>';
                }).join('');
                list.querySelectorAll('[data-hapus-ev]').forEach(function(b) {
                    b.addEventListener('click', async function() {
                        const lanjut = window.InaaiDialog ? await window.InaaiDialog.konfirmasi({
                            judul: 'Hapus File Evidence',
                            teks: 'File "' + b.dataset.nama +
                                '" akan dihapus permanen dari tugas ini.',
                            ok: 'Iya, hapus file',
                            jenis: 'bahaya'
                        }) : confirm('Hapus file ini?');
                        if (!lanjut) return;
                        try {
                            const r = await fetch('/evidence/' + b.dataset.hapusEv, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const d = await r.json();
                            if (!r.ok) {
                                window.InaaiToast && window.InaaiToast.galat(d.message ||
                                    'File gagal dihapus.');
                                return
                            }
                            const baris = list.querySelector('[data-ev="' + b.dataset.hapusEv +
                                '"]');
                            if (baris) baris.remove();
                            if (!list.querySelector('[data-ev]')) fld.style.display = 'none';
                            window.InaaiToast && window.InaaiToast.sukses(d.pesan);
                        } catch (e) {
                            window.InaaiToast && window.InaaiToast.galat('Koneksi bermasalah: ' + e
                                .message)
                        }
                    });
                });
            }

            async function bukaUbah(id) {
                let d;
                try {
                    d = await ambilTugas(id)
                } catch (e) {
                    alert('Gagal memuat data tugas.');
                    return
                }
                if (!d.boleh_edit) {
                    alert('Tugas ini hanya bisa diubah oleh admin atau pembuatnya.');
                    return
                }

                form.setAttribute('action', '/tasks/' + id);
                metode.value = 'PATCH';
                metode.disabled = false;
                fJudul.textContent = 'Ubah Tugas';
                fDesc.textContent = 'Perbarui detail tugas.';
                document.getElementById('taskSubmitLabel').textContent = 'Simpan Perubahan';

                form.querySelector('[name="judul"]').value = d.judul || '';
                form.querySelector('[name="deskripsi"]').value = d.deskripsi || '';
                setSelect('project_id', d.form.project_id);
                setSelect('status', d.status);
                setSelect('prioritas', d.prioritas);
                setSelect('user_id', d.form.user_id);
                setSelect('reviewer_id', d.form.reviewer_id);
                const im = form.querySelector('[name="mulai"]'),
                    is = form.querySelector('[name="selesai"]');
                if (im) {
                    im.value = d.form.mulai || '';
                    im.dispatchEvent(new Event('change', {
                        bubbles: true
                    }))
                }
                if (is) {
                    is.value = d.form.selesai || '';
                    is.dispatchEvent(new Event('change', {
                        bubbles: true
                    }))
                }
                gambarEvidenceLama(d.evidences || []);
                sync();
                m.classList.add('open');
            }

            function resetFormTambah() {
                form.reset();
                // form.reset() mengosongkan kotak tampilan date picker — sinkronkan lagi.
                form.querySelectorAll('[data-datepicker],[data-datetimepicker],[data-timepicker],[data-daterange]')
                    .forEach(function(el) {
                        if (el.inaaiDt && el.inaaiDt.segarkan) el.inaaiDt.segarkan();
                    });
                gambarEvidenceLama([]);
                sync();
                form.setAttribute('action', aksiStore);
                metode.value = '';
                metode.disabled = true;
                fJudul.textContent = 'Tugas Baru';
                fDesc.textContent = 'Lengkapi detail, lampirkan evidence, lalu assign dan minta review.';
                document.getElementById('taskSubmitLabel').textContent = 'Simpan Tugas';
            }

            document.querySelectorAll('[data-lihat]').forEach(b => b.addEventListener('click', () => bukaDrawer(b
                .dataset.lihat)));
            document.querySelectorAll('[data-ubah]').forEach(b => b.addEventListener('click', () => bukaUbah(b.dataset
                .ubah)));

            /* ===== Hapus tugas (kartu kanban & baris tabel) ===== */
            async function hapusTugas(id, judul) {
                const lanjut = window.InaaiDialog ? await window.InaaiDialog.konfirmasi({
                    judul: 'Hapus Tugas',
                    teks: 'Tugas "' + judul +
                        '" beserta seluruh file evidence-nya akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.',
                    ok: 'Iya, hapus tugas',
                    jenis: 'bahaya'
                }) : confirm('Hapus tugas ini?');
                if (!lanjut) return;
                try {
                    const r = await fetch('/tasks/' + id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const d = await r.json();
                    if (!r.ok) {
                        window.InaaiToast && window.InaaiToast.galat(d.message || 'Tugas gagal dihapus.');
                        return
                    }
                    window.InaaiToast && window.InaaiToast.sukses(d.pesan);
                    setTimeout(() => location.reload(), 650);
                } catch (e) {
                    window.InaaiToast && window.InaaiToast.galat('Koneksi bermasalah: ' + e.message);
                }
            }
            document.querySelectorAll('[data-hapus]').forEach(function(b) {
                b.addEventListener('click', function(e) {
                    e.stopPropagation();
                    hapusTugas(b.dataset.hapus, b.dataset.judul || '')
                });
            });

            /* ===== Filter rentang halaman + drilldown status =====
               Satu sumber kebenaran: rentang waktu dan status aktif menyaring komposisi,
               timeline, kanban, dan tabel sekaligus. */
            (function() {
                function esc(v) {
                    return String(v == null ? '' : v).replace(/[&<>'"]/g, c => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        "'": '&#039;',
                        '"': '&quot;'
                    } [c]))
                }
                const bar = document.getElementById('rentangHalaman'),
                    info = document.getElementById('rentangInfo');
                const drill = document.getElementById('statusDrill');
                if (!bar || !window.InaaiTimeline) return;

                const KUNCI = 'inaai_pekerjaan_rentang';
                const DATA = JSON.parse(document.querySelector('[data-tl-data]').textContent || '[]');
                const STATUS_URUT = [...document.querySelectorAll('[data-status-bar]')].map(e => e.dataset
                    .statusBar);
                const WARNA = {};
                document.querySelectorAll('[data-status-bar]').forEach(function(e) {
                    WARNA[e.dataset.statusBar] = e.querySelector('.kdot').style.background;
                });
                // Palet proyek untuk drilldown — sengaja beda dari palet status.
                const PALET = ['#f55d14', '#0e7490', '#4d7c0f', '#db2777', '#4338ca', '#b45309', '#0f766e',
                    '#9333ea'
                ];
                let rentang = '30d',
                    status = null;
                try {
                    const v = localStorage.getItem(KUNCI);
                    if (v) rentang = v
                } catch (e) {}

                bar.innerHTML = window.InaaiTimeline.daftarRentang.map(function(r) {
                    return '<button type="button" class="tl-f" data-range="' + r.key + '">' + r.label +
                        '</button>';
                }).join('');

                function jendela() {
                    return window.InaaiTimeline.jendela(rentang)
                }

                function keTgl(v) {
                    const p = String(v || '').slice(0, 10).split('-');
                    return p.length < 3 ? null : new Date(+p[0], +p[1] - 1, +p[2])
                }

                function dalamRentang(mulai, selesai) {
                    const j = jendela(),
                        a = keTgl(mulai),
                        b = keTgl(selesai);
                    if (!a || !b) return true; // tugas tanpa jadwal selalu ikut tampil
                    return new Date(b.getTime() + 86400000) > j.mulai && a < j.selesai;
                }
                let cari = '',
                    fProyek = '',
                    fStatus = '',
                    fPrioritas = '';

                function lolos(el, pakaiFilterTabel) {
                    const d = el.dataset;
                    if (status && d.status && d.status !== status) return false;
                    if (!dalamRentang(d.mulai, d.selesai)) return false;
                    if (!pakaiFilterTabel) return true;
                    if (cari && (d.cari || '').indexOf(cari) === -1) return false;
                    if (fProyek && d.proyek !== fProyek) return false;
                    if (fStatus && d.status !== fStatus) return false;
                    if (fPrioritas && d.prioritas !== fPrioritas) return false;
                    return true;
                }

                function terapkan() {
                    bar.querySelectorAll('[data-range]').forEach(b => b.classList.toggle('on', b.dataset.range ===
                        rentang));

                    // Timeline
                    window.InaaiTimeline.rentang(rentang);
                    window.InaaiTimeline.saring(function(t) {
                        return !status || t.statusKey === status
                    });

                    // Kanban
                    document.querySelectorAll('.kcol').forEach(function(col) {
                        let n = 0;
                        col.querySelectorAll('.ktask').forEach(function(c) {
                            const ok = lolos(c, true);
                            c.style.display = ok ? '' : 'none';
                            if (ok) n++;
                        });
                        const hit = col.querySelector('.kcol-c');
                        if (hit) hit.textContent = n;
                        const kosong = col.querySelector('div[style*="Kosong"],.kcol-kosong');
                        col.classList.toggle('sembunyi', !!status && col.dataset.status !== status);
                    });

                    // Tabel
                    let baris = 0;
                    document.querySelectorAll('tr[data-row]').forEach(function(tr) {
                        const ok = lolos(tr, true);
                        tr.dataset.filterOff = ok ? '' : '1';
                        baris += ok ? 1 : 0;
                    });
                    const kartuTabel = document.getElementById('daftarCard');
                    if (kartuTabel && kartuTabel.inaaiTable) kartuTabel.inaaiTable.segarkan();

                    // Komposisi
                    const dipakai = DATA.filter(t => dalamRentang(t.mulai, t.selesai));
                    document.querySelectorAll('[data-status-bar]').forEach(function(row) {
                        const k = row.dataset.statusBar;
                        const isi = dipakai.filter(t => t.status_key === k);
                        const proyek = new Set(isi.map(t => t.proyek)).size;
                        row.querySelector('[data-kb-jumlah]').textContent = isi.length;
                        row.querySelector('[data-kb-proyek]').textContent = proyek + ' proyek';
                        row.querySelector('[data-kb-bar]').style.width = (dipakai.length ? isi.length /
                            dipakai.length * 100 : 0) + '%';
                        row.classList.toggle('on', status === k);
                    });

                    info.textContent = dipakai.length + ' tugas pada rentang ini' + (status ?
                        ' · disaring per status' : '');
                    gambarDrill(dipakai);
                }

                function gambarDrill(dipakai) {
                    const pie = document.querySelector('.pie'),
                        lubangN = document.querySelector('.pie-n'),
                        lubangL = document.querySelector('.pie-l');
                    const judul = document.querySelector('.card-desc');

                    const kotakStatus = document.querySelector('.komposisi-bar');

                    if (!status) {
                        drill.style.display = 'none';
                        drill.innerHTML = '';
                        document.querySelectorAll('[data-status-bar]').forEach(el => el.style.display = '');
                        // Kembalikan pie ke komposisi seluruh status.
                        gambarPie(STATUS_URUT.map(function(k) {
                            const n = dipakai.filter(t => t.status_key === k).length;
                            return {
                                warna: WARNA[k],
                                nilai: n
                            };
                        }), dipakai.length, 'tugas');
                        if (judul) judul.textContent = dipakai.length +
                            ' tugas · sebaran seluruh status pekerjaan Anda.';
                        return;
                    }

                    // Saat drilldown, daftar status disembunyikan — yang tampil hanya rinciannya.
                    document.querySelectorAll('[data-status-bar]').forEach(el => el.style.display = 'none');

                    const isi = dipakai.filter(t => t.status_key === status);
                    const label = (document.querySelector('[data-status-bar="' + status + '"] .kb-nama') || {})
                        .textContent || status;
                    const grup = {};
                    isi.forEach(function(t) {
                        (grup[t.proyek] = grup[t.proyek] || []).push(t)
                    });
                    const nama = Object.keys(grup).sort(function(a, b) {
                        return grup[b].length - grup[a].length
                    });

                    // Pie kini menggambarkan sebaran status terpilih per proyek.
                    gambarPie(nama.map(function(p, i) {
                        return {
                            warna: PALET[i % PALET.length],
                            nilai: grup[p].length
                        }
                    }), isi.length, 'tugas');
                    if (judul) judul.textContent = label.trim() + ' · tersebar di ' + nama.length + ' proyek.';

                    drill.style.display = '';
                    drill.innerHTML = '<div class="kb-drill-h">' +
                        '<div class="kb-drill-t">Rincian ' + esc(label.trim()) + ' per proyek</div>' +
                        '<button type="button" class="btn btn-sm" data-drill-tutup><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>Hapus filter</button>' +
                        '</div>' +
                        (nama.length ? (function() {
                                // Persen = porsi proyek terhadap total status; panjang bar dinormalisasi ke
                                // proyek terbanyak supaya yang tertinggi tampil penuh.
                                const maks = Math.max.apply(null, nama.map(p => grup[p].length));
                                return nama.map(function(p, i) {
                                    const n = grup[p].length;
                                    const persen = isi.length ? Math.round(n / isi.length * 1000) / 10 :
                                        0;
                                    const w = maks ? n / maks * 100 : 0;
                                    const c = PALET[i % PALET.length];
                                    return '<div class="kb-row" style="cursor:default">' +
                                        '<div class="kb-head"><span class="kb-nama"><i class="kdot" style="background:' +
                                        c + '"></i>' + esc(p) + '</span>' +
                                        '<span class="kb-nilai" style="text-align:right">' + n +
                                        ' tugas' +
                                        '<span class="kb-sub" style="display:block">' + persen +
                                        '%</span></span></div>' +
                                        '<div class="kb-track"><i style="width:' + w + '%;background:' +
                                        c + '"></i></div></div>';
                                }).join('');
                            })() :
                            '<div class="kosong"><span class="kosong-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9 12h6"/></svg></span><span class="kosong-t">Tidak ada tugas</span><span class="kosong-s">Status ini tidak punya tugas pada rentang yang dipilih.</span></div>'
                            );

                    drill.querySelector('[data-drill-tutup]').addEventListener('click', function() {
                        status = null;
                        terapkan()
                    });
                }

                /* Gambar ulang donat dari daftar {warna, nilai}. */
                function gambarPie(bagian, total, satuan) {
                    const pie = document.querySelector('.pie');
                    if (!pie) return;
                    let sudut = 0;
                    const potong = [];
                    bagian.forEach(function(b) {
                        const lebar = total ? b.nilai / total * 360 : 0;
                        if (lebar > 0) {
                            potong.push(b.warna + ' ' + sudut.toFixed(3) + 'deg ' + (sudut + lebar).toFixed(
                                3) + 'deg');
                            sudut += lebar
                        }
                    });
                    pie.style.background = 'conic-gradient(' + (potong.length ? potong.join(', ') :
                        'var(--line3) 0deg 360deg') + ')';
                    const n = pie.querySelector('.pie-n'),
                        l = pie.querySelector('.pie-l');
                    if (n) n.textContent = total;
                    if (l) l.textContent = satuan;
                }

                bar.addEventListener('click', function(e) {
                    const b = e.target.closest('[data-range]');
                    if (!b) return;
                    rentang = b.dataset.range;
                    try {
                        localStorage.setItem(KUNCI, rentang)
                    } catch (err) {}
                    terapkan();
                });

                document.querySelectorAll('[data-status-bar]').forEach(function(row) {
                    function pilih() {
                        status = status === row.dataset.statusBar ? null : row.dataset.statusBar;
                        terapkan()
                    }
                    row.addEventListener('click', pilih);
                    row.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            pilih()
                        }
                    });
                });

                /* Pencarian & filter khusus Daftar Pekerjaan */
                const inCari = document.getElementById('dpCari');

                function pasangFilter(id, setter) {
                    const el = document.getElementById(id);
                    if (el) el.addEventListener('change', function() {
                        setter(el.value);
                        terapkan()
                    });
                }
                if (inCari) inCari.addEventListener('input', function() {
                    cari = inCari.value.trim().toLowerCase();
                    terapkan()
                });
                pasangFilter('dpProyek', v => fProyek = v);
                pasangFilter('dpStatus', v => fStatus = v);
                pasangFilter('dpPrioritas', v => fPrioritas = v);
                const bReset = document.getElementById('dpReset');
                if (bReset) bReset.addEventListener('click', function() {
                    cari = '';
                    fProyek = '';
                    fStatus = '';
                    fPrioritas = '';
                    if (inCari) inCari.value = '';
                    ['dpProyek', 'dpStatus', 'dpPrioritas'].forEach(function(id) {
                        const el = document.getElementById(id);
                        if (el) {
                            el.value = '';
                            if (el.inaaiSel && el.inaaiSel.segarkan) el.inaaiSel.segarkan()
                        }
                    });
                    terapkan();
                });

                terapkan();
            })();

            /* ===== Peralihan tampilan Kanban / Table ===== */
            (function() {
                const sw = document.getElementById('viewSwitch');
                if (!sw) return;
                const KUNCI = 'inaai_pekerjaan_view';

                function pakai(nama) {
                    sw.querySelectorAll('[data-view]').forEach(b => b.classList.toggle('on', b.dataset.view ===
                        nama));
                    document.querySelectorAll('[data-view-panel]').forEach(p => {
                        p.style.display = p.dataset.viewPanel === nama ? '' : 'none'
                    });
                    // Kanban sudah dikelompokkan per status, jadi filternya tidak perlu di sana.
                    const st = document.getElementById('dpStatusWrap');
                    if (st) st.style.display = nama === 'tabel' ? '' : 'none';
                    try {
                        localStorage.setItem(KUNCI, nama)
                    } catch (e) {}
                }
                let awal = 'kanban';
                try {
                    const v = localStorage.getItem(KUNCI);
                    if (v === 'kanban' || v === 'tabel') awal = v
                } catch (e) {}
                pakai(awal);
                sw.addEventListener('click', function(e) {
                    const b = e.target.closest('[data-view]');
                    if (b) pakai(b.dataset.view);
                });
            })();

            /* Seret dan lepasnya ditangani komponen global inaai-kanban; halaman hanya
               perlu memuat ulang supaya komposisi status dan timeline ikut menyesuaikan. */
            document.querySelector('[data-kanban]').addEventListener('inaai:kanban-pindah', function() {
                setTimeout(() => location.reload(), 650);
            });
        })();
    </script>
@endpush)
