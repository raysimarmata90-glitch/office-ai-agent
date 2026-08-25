<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskMetrics;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectAdminController extends Controller
{
    public function index()
    {
        $projects = Project::with('tasks')->orderBy('nama')->get()->map(function (Project $p) {
            $r = TaskMetrics::ringkasStatus($p->tasks);

            return [
                'model' => $p,
                'id' => $p->id,
                'nama' => $p->nama,
                'warna' => $p->warna,
                'berisiko' => $p->berisiko,
                'tugas' => $r['total'],
                'selesai' => $r['done'],
                'pct' => $r['pct'],
                'kontributor' => $p->tasks->pluck('user_id')->unique()->count(),
                'dibuat' => $p->created_at,
                'periode' => $p->mulai && $p->selesai
                    ? $p->mulai->format('d/m/y') . ' – ' . $p->selesai->format('d/m/y')
                    : '–',
            ];
        });

        return view('admin.proyek', compact('projects'));
    }

    public function show(Project $project)
    {
        $project->load(['tasks.user', 'tasks.reviewer', 'tasks.evidences']);

        $ringkas = TaskMetrics::ringkasStatus($project->tasks);
        $kanban = TaskMetrics::kanban($project->tasks);
        $kontributor = $project->tasks->groupBy('user_id')->map(function ($items) {
            $r = TaskMetrics::ringkasStatus($items);

            return [
                'user' => $items->first()->user,
                'total' => $r['total'],
                'pct' => $r['pct'],
                'ringkas' => $r,
            ];
        })->sortByDesc('total')->values();

        return view('admin.proyek-detail', compact('project', 'ringkas', 'kanban', 'kontributor'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'warna' => ['nullable', 'string', 'max:20'],
            'deskripsi' => ['nullable', 'string'],
            'mulai' => ['nullable', 'date'],
            'selesai' => ['nullable', 'date', 'after_or_equal:mulai'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['warna'] = $validated['warna'] ?? '#f55d14';

        $project = Project::create($validated);

        return back()->with('success', 'Proyek "' . $project->nama . '" berhasil dibuat.');
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_baru' => ['nullable', 'string', 'max:255'],
            'user_id' => ['required', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', Rule::in(array_keys(Task::daftarStatus()))],
            'prioritas' => ['required', Rule::in(['Tinggi', 'Sedang', 'Rendah'])],
            'mulai' => ['required', 'date'],
            'selesai' => ['required', 'date', 'after_or_equal:mulai'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        if (empty($validated['project_id']) && empty($validated['project_baru'])) {
            return back()
                ->withInput()
                ->withErrors(['project_id' => 'Pilih proyek yang tersedia atau isi nama proyek baru.']);
        }

        $projectId = $validated['project_id'] ?? Project::create([
            'nama' => $validated['project_baru'],
            'warna' => '#f55d14',
            'mulai' => $validated['mulai'],
            'selesai' => $validated['selesai'],
            'created_by' => $request->user()->id,
        ])->id;

        $task = Task::create([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'project_id' => $projectId,
            'user_id' => $validated['user_id'],
            'reviewer_id' => $validated['reviewer_id'] ?? null,
            'created_by' => $request->user()->id,
            'status' => $validated['status'],
            'prioritas' => $validated['prioritas'],
            'mulai' => $validated['mulai'],
            'selesai' => $validated['selesai'],
        ]);

        $pegawai = User::find($validated['user_id']);

        return back()->with('success', 'Tugas "' . $task->judul . '" berhasil di-assign ke ' . $pegawai->name . '.');
    }
}
