<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Support\SesiPerangkat;
use App\Services\TaskMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $tasks = Task::with(['project', 'user'])->get();
        $projects = Project::with('tasks')->orderBy('nama')->get();

        $ringkas = TaskMetrics::ringkasStatus($tasks);

        $kpi = [
            [
                'label' => 'Total Proyek',
                'nilai' => $projects->count(),
                'sub' => $projects->filter(fn ($p) => $p->tasks->isNotEmpty())->count() . ' punya tugas',
            ],
            [
                'label' => 'Total Tugas',
                'nilai' => $ringkas['total'],
                'sub' => $ringkas['progress'] . ' sedang dikerjakan',
            ],
            [
                'label' => 'Kontributor Aktif',
                'nilai' => $tasks->pluck('user_id')->unique()->count(),
                'sub' => 'di ' . $projects->count() . ' proyek',
            ],
            [
                'label' => 'Tugas Selesai',
                'nilai' => $ringkas['done'],
                'sub' => $ringkas['pct'] . '% dari total',
            ],
        ];

        $overviewProyek = $projects->map(fn (Project $p) => [
            'id' => $p->id,
            'nama' => $p->nama,
            'warna' => $p->warna,
            'tugas' => $p->tasks->count(),
            'pct' => TaskMetrics::ringkasStatus($p->tasks)['pct'],
            'kontributor' => $p->tasks->pluck('user_id')->unique()->count(),
            'segmen' => TaskMetrics::segmen($p->tasks),
        ])->sortByDesc('tugas')->values();

        $overviewUser = $tasks->groupBy('user_id')->map(function ($items) {
            $u = $items->first()->user;

            return [
                'nama' => $u?->name ?? 'Tanpa Nama',
                'inisial' => $u?->inisial() ?? '?',
                'warna' => $u ? $u->warnaAvatar() : ['bg' => '#e8edf4', 'text' => '#475569'],
                'tugas' => $items->count(),
                'pct' => TaskMetrics::ringkasStatus($items)['pct'],
                'proyek' => $items->pluck('project_id')->unique()->count(),
                'segmen' => TaskMetrics::segmen($items),
            ];
        })->sortByDesc('tugas')->take(8)->values();

        // Timeline memakai komponen yang sama dengan halaman Pekerjaan Saya.
        $timeline = $tasks->filter(fn ($t) => $t->mulai && $t->selesai)
            ->sortBy('mulai')
            ->map(fn (Task $t) => [
                'id' => $t->id,
                'judul' => $t->judul,
                'proyek' => $t->project?->nama ?? 'Tanpa Proyek',
                'status_key' => $t->status,
                'status' => $t->statusLabel(),
                'prioritas' => $t->prioritas,
                'reviewer' => $t->user?->name,
                'evidence' => null,
                'warna' => Task::titikStatus($t->status),
                'mulai' => $t->mulai->toDateString(),
                'selesai' => $t->selesai->toDateString(),
                'progres' => 100,
            ])
            ->values();

        $semuaUser = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.dashboard', compact(
            'user', 'kpi', 'overviewProyek', 'overviewUser',
            'timeline', 'ringkas', 'projects', 'semuaUser'
        ));
    }

    /** Jumlah kartu yang dikirim sekali muat pada kanban dashboard. */
    public const KANBAN_PER_MUAT = 50;

    /**
     * Satu kolom kanban dashboard, dimuat bertahap. Seluruh saringan dikerjakan
     * di server supaya kartu yang belum termuat pun ikut tersaring — filter di
     * sisi klien hanya akan menyaring apa yang kebetulan sudah tampil.
     */
    public function kanbanFeed(Request $request)
    {
        $status = (string) $request->query('status', '');
        abort_unless(array_key_exists($status, Task::daftarStatus()), 404);

        $offset = max(0, (int) $request->query('offset', 0));
        $batas = self::KANBAN_PER_MUAT;

        $q = Task::with(['project', 'user'])
            ->where('status', $status)
            ->when($request->filled('q'), function ($w) use ($request) {
                $kunci = '%' . mb_strtolower(trim((string) $request->query('q'))) . '%';
                $w->where(function ($x) use ($kunci) {
                    $x->whereRaw('LOWER(judul) LIKE ?', [$kunci])
                        ->orWhereHas('project', fn ($p) => $p->whereRaw('LOWER(nama) LIKE ?', [$kunci]))
                        ->orWhereHas('user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$kunci]));
                });
            })
            ->when($request->filled('proyek'), fn ($w) => $w->whereHas(
                'project',
                fn ($p) => $p->where('nama', $request->query('proyek'))
            ))
            ->when($request->filled('prioritas'), fn ($w) => $w->where('prioritas', $request->query('prioritas')))
            ->when($request->filled('user'), fn ($w) => $w->whereHas(
                'user',
                fn ($u) => $u->where('name', $request->query('user'))
            ));

        // Rentang waktu: tugas ikut bila jadwalnya beririsan dengan jendela yang
        // dipilih. Tugas tanpa tanggal tidak pernah disembunyikan.
        if ($request->filled('dari') && $request->filled('sampai')) {
            $dari = $request->date('dari');
            $sampai = $request->date('sampai');
            $q->where(function ($w) use ($dari, $sampai) {
                $w->whereNull('mulai')
                    ->orWhereNull('selesai')
                    ->orWhere(fn ($x) => $x->where('selesai', '>=', $dari)->where('mulai', '<=', $sampai));
            });
        }

        $total = (clone $q)->count();
        $items = $q->orderByDesc('created_at')->orderByDesc('id')
            ->skip($offset)->take($batas)->get();

        $html = $items
            ->map(fn (Task $t) => view('partials.kanban-card', ['t' => $t])->render())
            ->implode('');

        return response()->json([
            'html' => $html,
            'jumlah' => $items->count(),
            'total' => $total,
            'habis' => $offset + $items->count() >= $total,
        ]);
    }

    public function users(Request $request)
    {
        $view = $request->query('view') === 'table' ? 'table' : 'card';

        $users = User::with(['role', 'department', 'tasks'])
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                $r = TaskMetrics::ringkasStatus($u->tasks);

                return [
                    'model' => $u,
                    'id' => $u->id,
                    'nama' => $u->name,
                    'inisial' => $u->inisial(),
                    'foto' => $u->fotoUrl(),
                    'warna' => $u->warnaAvatar(),
                    'email' => $u->email,
                    'role' => $u->role?->display_name ?? '-',
                    'departemen' => $u->department?->name ?? '-',
                    'aktif' => $u->is_active,
                    'dibuat' => $u->created_at,
                    'ringkas' => $r,
                ];
            });

        $projects = Project::orderBy('nama')->get();
        $semuaUser = User::where('is_active', true)->orderBy('name')->get();
        $daftarRole = Role::orderBy('display_name')->get(['id', 'name', 'display_name', 'department_id']);
        $daftarDep = Department::orderBy('name')->get(['id', 'name']);

        return view('admin.users', compact('users', 'view', 'projects', 'semuaUser', 'daftarRole', 'daftarDep'));
    }

    /** Admin boleh memperbarui data pengguna lain, termasuk role dan departemennya. */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'bio' => ['nullable', 'string', 'max:500'],
            'role_id' => ['required', 'exists:roles,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        // Admin yang sedang dipakai tidak boleh menonaktifkan dirinya sendiri.
        if ($user->is(auth()->user()) && ! $validated['is_active']) {
            return response()->json([
                'message' => 'Akun yang sedang Anda pakai tidak bisa dinonaktifkan.',
            ], 422);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'pesan' => 'Data ' . $user->name . ' berhasil diperbarui.',
        ]);
    }

    public function destroyUser(Request $request, User $user)
    {
        if ($user->is(auth()->user())) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun sendiri.'], 422);
        }

        if ($user->tasks()->exists()) {
            return response()->json([
                'message' => 'Pengguna ini masih punya tugas. Pindahkan tugasnya lebih dulu, atau nonaktifkan akunnya.',
            ], 422);
        }

        $nama = $user->name;
        $user->delete();

        return response()->json(['success' => true, 'pesan' => 'Pengguna ' . $nama . ' dihapus.']);
    }

    /** Detail satu pengguna untuk mengisi form ubah. */
    public function showUser(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'nama' => $user->name,
            'email' => $user->email,
            'telepon' => $user->phone,
            'bio' => $user->bio,
            'role_id' => $user->role_id,
            'department_id' => $user->department_id,
            'aktif' => (bool) $user->is_active,
            'inisial' => $user->inisial(),
            'foto' => $user->fotoUrl(),
            'diri_sendiri' => $user->is(auth()->user()),
            'sesi' => SesiPerangkat::daftar($user->id, $user->is(auth()->user()) ? session()->getId() : null),
        ]);
    }

    /**
     * Admin mengganti password pengguna lain. Password lama tidak diminta —
     * admin memang tidak memilikinya — jadi seluruh sesi pengguna itu dibuang.
     */
    public function sandiUser(Request $request, User $user)
    {
        $request->validate([
            'sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'sandi.min' => 'Password baru minimal 8 karakter.',
            'sandi.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $user->update(['password' => Hash::make($request->input('sandi'))]);

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->when($user->is(auth()->user()), fn ($q) => $q->where('id', '!=', session()->getId()))
            ->delete();

        return response()->json([
            'success' => true,
            'pesan' => 'Password ' . $user->name . ' diperbarui. Sesi lamanya dikeluarkan.',
            'sesi' => SesiPerangkat::daftar($user->id, $user->is(auth()->user()) ? session()->getId() : null),
        ]);
    }

    public function hapusFotoUser(User $user)
    {
        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
            $user->update(['foto' => null]);
        }

        return response()->json([
            'success' => true,
            'pesan' => 'Foto profil dihapus.',
            'inisial' => $user->fresh()->inisial(),
        ]);
    }

    public function keluarSesiUser(User $user, string $sesi)
    {
        if ($user->is(auth()->user()) && $sesi === session()->getId()) {
            return response()->json(['message' => 'Sesi ini sedang Anda pakai.'], 422);
        }

        DB::table('sessions')->where('user_id', $user->id)->where('id', $sesi)->delete();

        return response()->json([
            'success' => true,
            'pesan' => 'Perangkat berhasil dikeluarkan.',
            'sesi' => SesiPerangkat::daftar($user->id, $user->is(auth()->user()) ? session()->getId() : null),
        ]);
    }


    public function toggleUserStatus(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Akun admin yang sedang digunakan tidak dapat dinonaktifkan.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return back()->with(
            'success',
            $user->is_active
                ? 'User berhasil diaktifkan.'
                : 'User berhasil dinonaktifkan.'
        );
    }

    public function conversations(Request $request)
    {
        $cari = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $depId = (string) $request->query('departemen', '');
        $perHalaman = (int) $request->query('per_page', 10);
        $perHalaman = in_array($perHalaman, [10, 25, 50, 100], true) ? $perHalaman : 10;

        // Dipaginasi di server: jumlah percakapan tumbuh terus seiring pemakaian,
        // jadi mengirim seluruh baris ke browser tidak akan bertahan lama.
        $conversations = Conversation::query()
            ->with(['user', 'department', 'pesanDetail'])
            ->withCount('messages')
            ->when($cari !== '', function ($q) use ($cari) {
                $kunci = '%' . mb_strtolower($cari) . '%';
                $q->where(function ($w) use ($kunci) {
                    $w->whereRaw('LOWER(title) LIKE ?', [$kunci])
                        ->orWhereHas('user', fn ($u) => $u
                            ->whereRaw('LOWER(name) LIKE ?', [$kunci])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$kunci]));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($depId !== '', fn ($q) => $q->where('department_id', $depId))
            ->orderByDesc('updated_at')
            ->paginate($perHalaman)
            ->withQueryString();

        $ringkas = [
            'total' => Conversation::count(),
            'aktif' => Conversation::where('status', 'active')->count(),
            'selesai' => Conversation::where('status', 'completed')->count(),
            'pesan' => Message::count(),
        ];

        $departemen = Department::orderBy('name')->get(['id', 'name']);

        return view('admin.conversations', compact(
            'conversations', 'ringkas', 'departemen', 'cari', 'status', 'depId'
        ));
    }

    public function pekerjaan()
    {
        $tasks = Task::with(['project', 'user', 'reviewer'])
            ->orderByDesc('created_at')
            ->get();

        $projects = Project::orderBy('nama')->get();
        $semuaUser = User::where('is_active', true)->orderBy('name')->get();

        // Pilihan filter diambil dari tugas yang ada, bukan seluruh pengguna,
        // supaya tidak ada opsi yang pasti tidak menghasilkan baris apa pun.
        $daftarPemilik = $tasks->pluck('user.name')->filter()->unique()->sort()->values();
        $daftarReviewer = $tasks->pluck('reviewer.name')->filter()->unique()->sort()->values();
        $tanpaReviewer = $tasks->whereNull('reviewer_id')->count();

        return view('admin.pekerjaan', compact(
            'tasks', 'projects', 'semuaUser', 'daftarPemilik', 'daftarReviewer', 'tanpaReviewer'
        ));
    }

    public function laporan(Request $request)
    {
        $filter = $request->query('rentang', 'semua');

        $query = Task::with(['project', 'user']);

        $sejak = match ($filter) {
            '7hari' => now()->subDays(7),
            '30hari' => now()->subDays(30),
            'bulan' => now()->startOfMonth(),
            'kuartal' => now()->startOfQuarter(),
            default => null,
        };

        if ($sejak) {
            $query->where('created_at', '>=', $sejak);
        }

        $tasks = $query->orderByDesc('created_at')->get();
        $ringkas = TaskMetrics::ringkasStatus($tasks);

        $perProyek = $tasks->groupBy('project_id')->map(function ($items) {
            $r = TaskMetrics::ringkasStatus($items);
            $p = $items->first()->project;

            return [
                'nama' => $p?->nama ?? '-',
                'warna' => $p?->warna ?? '#f55d14',
                'total' => $r['total'],
                'done' => $r['done'],
                'progress' => $r['progress'],
                'todo' => $r['todo'],
                'segmen' => TaskMetrics::segmen($items),
                'pct' => $r['pct'],
            ];
        })->sortByDesc('total')->values();

        $perUser = $tasks->groupBy('user_id')->map(function ($items) {
            $r = TaskMetrics::ringkasStatus($items);
            $u = $items->first()->user;

            return [
                'nama' => $u?->name ?? '-',
                'inisial' => $u?->inisial() ?? '?',
                'total' => $r['total'],
                'done' => $r['done'],
                'progress' => $r['progress'],
                'todo' => $r['todo'],
                'segmen' => TaskMetrics::segmen($items),
                'pct' => $r['pct'],
            ];
        })->sortByDesc('total')->values();

        return view('admin.laporan', compact('tasks', 'ringkas', 'perProyek', 'perUser', 'filter'));
    }

    public function ekspor(Request $request)
    {
        $filter = $request->query('rentang', 'semua');

        $sejak = match ($filter) {
            '7hari' => now()->subDays(7),
            '30hari' => now()->subDays(30),
            'bulan' => now()->startOfMonth(),
            'kuartal' => now()->startOfQuarter(),
            default => null,
        };

        $query = Task::with(['project', 'user', 'reviewer']);
        if ($sejak) {
            $query->where('created_at', '>=', $sejak);
        }
        $tasks = $query->orderByDesc('created_at')->get();

        $namaFile = 'laporan-tugas-' . $filter . '-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($tasks) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No', 'Judul', 'Proyek', 'Pemilik', 'Reviewer', 'Prioritas', 'Status', 'Mulai', 'Selesai', 'Dibuat']);

            foreach ($tasks as $i => $t) {
                fputcsv($out, [
                    $i + 1,
                    $t->judul,
                    $t->project?->nama ?? '-',
                    $t->user?->name ?? '-',
                    $t->reviewer?->name ?? '-',
                    $t->prioritas,
                    $t->statusLabel(),
                    $t->mulai?->format('d/m/y') ?? '-',
                    $t->selesai?->format('d/m/y') ?? '-',
                    $t->created_at?->format('d/m/y, H:i') ?? '-',
                ]);
            }

            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function conversationDetail(Conversation $conversation)
    {
        $conversation->load(['user', 'department']);

        $pesan = $conversation->messages()->orderBy('created_at')->get();

        // Tugas yang lahir dari percakapan ini, dicocokkan lewat nama proyeknya.
        $tugas = Task::with('project')
            ->where('user_id', $conversation->user_id)
            ->whereHas('project', fn ($q) => $q->whereRaw(
                'LOWER(nama) = ?',
                [mb_strtolower(preg_replace('/^Proyek:\s*/i', '', (string) $conversation->title))]
            ))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.conversation-detail', compact('conversation', 'pesan', 'tugas'));
    }

    /**
     * Get pending password reset requests count untuk badge notifikasi
     */
    public function getPendingPasswordRequestsCount()
    {
        return \App\Models\PasswordResetRequest::pending()->count();
    }

    /**
     * Admin view password reset requests di halaman users
     */
    public function passwordRequests(Request $request)
    {
        // This will be shown as a modal or section in the users page
        $requests = \App\Models\PasswordResetRequest::with(['user', 'handledBy'])
            ->recent()
            ->get();

        $pendingCount = $requests->where('status', 'pending')->count();

        if ($request->wantsJson()) {
            return response()->json([
                'requests' => $requests->map(fn($req) => [
                    'id' => $req->id,
                    'user' => [
                        'name' => $req->user->name,
                        'email' => $req->user->email,
                        'inisial' => $req->user->inisial(),
                        'foto' => $req->user->fotoUrl(),
                    ],
                    'email' => $req->email,
                    'reason' => $req->reason,
                    'status' => $req->status,
                    'created_at' => $req->created_at->diffForHumans(),
                    'handled_by' => $req->handledBy ? $req->handledBy->name : null,
                    'handled_at' => $req->handled_at?->diffForHumans(),
                    'is_pending' => $req->isPending(),
                ]),
                'pending_count' => $pendingCount,
            ]);
        }

        return view('admin.password-requests', compact('requests', 'pendingCount'));
    }

    /**
     * Admin approve password reset request
     */
    public function approvePasswordRequest(Request $request, \App\Models\PasswordResetRequest $passwordResetRequest)
    {
        if (!$passwordResetRequest->isPending()) {
            return response()->json(['message' => 'Request ini sudah diproses.'], 422);
        }

        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'new_password.min' => 'Password baru minimal 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        // Update user password
        $user = $passwordResetRequest->user;
        $user->update(['password' => Hash::make($validated['new_password'])]);

        // Logout all user sessions except current admin session
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        // Update request status
        $passwordResetRequest->update([
            'status' => 'approved',
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'pesan' => 'Password ' . $user->name . ' berhasil direset. Semua sesi user telah dikeluarkan.',
        ]);
    }

    /**
     * Admin reject password reset request
     */
    public function rejectPasswordRequest(\App\Models\PasswordResetRequest $passwordResetRequest)
    {
        if (!$passwordResetRequest->isPending()) {
            return response()->json(['message' => 'Request ini sudah diproses.'], 422);
        }

        $passwordResetRequest->update([
            'status' => 'rejected',
            'handled_by' => auth()->id(),
            'handled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'pesan' => 'Request reset password ditolak.',
        ]);
    }
}
