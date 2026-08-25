<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ChatHistory;
use App\Models\Department;
use App\Models\Pekerjaan;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskMetrics;
use Illuminate\Http\Request;
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
                'sub' => $projects->where('berisiko', true)->count() . ' berisiko',
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

        $overviewProyek = $projects->map(function (Project $p) use ($ringkas) {
            $r = TaskMetrics::ringkasStatus($p->tasks);
            $total = max(1, $ringkas['total']);

            return [
                'id' => $p->id,
                'nama' => $p->nama,
                'warna' => $p->warna,
                'berisiko' => $p->berisiko,
                'tugas' => $r['total'],
                'pct' => $r['pct'],
                'kontributor' => $p->tasks->pluck('user_id')->unique()->count(),
                'wDone' => $r['done'] / $total * 100,
                'wProgress' => $r['progress'] / $total * 100,
                'wTodo' => $r['todo'] / $total * 100,
            ];
        })->sortByDesc('tugas')->values();

        $overviewUser = $tasks->groupBy('user_id')->map(function ($items) use ($ringkas) {
            $r = TaskMetrics::ringkasStatus($items);
            $total = max(1, $ringkas['total']);
            $u = $items->first()->user;

            return [
                'nama' => $u?->name ?? 'Tanpa Nama',
                'inisial' => $u?->inisial() ?? '?',
                'tugas' => $r['total'],
                'pct' => $r['pct'],
                'proyek' => $items->pluck('project_id')->unique()->count(),
                'wDone' => $r['done'] / $total * 100,
                'wProgress' => $r['progress'] / $total * 100,
                'wTodo' => $r['todo'] / $total * 100,
            ];
        })->sortByDesc('tugas')->take(8)->values();

        $kanban = TaskMetrics::kanban($tasks->sortByDesc('created_at')->values());

        $bulan = TaskMetrics::bulanTimeline($tasks, 6);
        $gantt = $projects->map(function (Project $p) use ($bulan) {
            $r = TaskMetrics::ringkasStatus($p->tasks);

            return [
                'nama' => $p->nama,
                'warna' => $p->warna,
                'pct' => $r['pct'],
                'pos' => TaskMetrics::posisiBar($p->mulai, $p->selesai, $bulan),
            ];
        })->filter(fn ($g) => $g['pos'] !== null)->values();

        $aktivitas = Task::with(['user', 'project'])
            ->orderByDesc('updated_at')
            ->take(6)
            ->get()
            ->map(fn (Task $t) => [
                'inisial' => $t->user?->inisial() ?? '?',
                'siapa' => $t->user?->name ?? 'Sistem',
                'apa' => $t->statusLabel() . ' · ' . $t->judul,
                'waktu' => $t->updated_at?->diffForHumans(),
            ]);

        $berisiko = $projects->where('berisiko', true)->pluck('nama')->values();

        return view('admin.dashboard', compact(
            'user', 'kpi', 'overviewProyek', 'overviewUser', 'kanban',
            'bulan', 'gantt', 'aktivitas', 'ringkas', 'berisiko', 'projects'
        ));
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

        return view('admin.users', compact('users', 'view', 'projects', 'semuaUser'));
    }

    public function updatePekerjaan(Request $request, Pekerjaan $pekerjaan)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['on going', 'completed'])],
            'kategori' => ['required', Rule::in(['Highest', 'High', 'Medium', 'Low', 'Lowest'])],
        ]);

        $pekerjaan->update($validated);

        return back()->with('success', 'Status dan kategori pekerjaan berhasil diperbarui.');
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

    public function conversations()
    {
        $conversations = Conversation::with(['user', 'department', 'messages'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.conversations', compact('conversations'));
    }

    public function chatHistories()
    {
        $histories = ChatHistory::with(['conversation', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('admin.chat-histories', compact('histories'));
    }

    public function pekerjaan()
    {
        $tasks = Task::with(['project', 'user', 'reviewer'])
            ->orderByDesc('created_at')
            ->get();

        $projects = Project::orderBy('nama')->get();
        $semuaUser = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.pekerjaan', compact('tasks', 'projects', 'semuaUser'));
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
        $conversation->load(['user', 'department', 'messages']);
        return view('admin.conversation-detail', compact('conversation'));
    }
}
