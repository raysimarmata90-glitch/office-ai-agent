<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDeliverable;
use App\Models\Task;
use App\Models\TaskEvidence;
use App\Models\User;
use App\Services\TaskMetrics;
use App\Support\BatasUnggah;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectAdminController extends Controller
{
    public function index()
    {
        $projects = Project::with('tasks')->orderBy('client_or_rd')->get()->map(function (Project $p) {
            $r = TaskMetrics::ringkasStatus($p->tasks);

            return [
                'model' => $p,
                'id' => $p->id,
                'nama' => $p->client_or_rd,
                'warna' => $p->warna,
                'tugas' => $r['total'],
                'selesai' => $r['done'],
                'pct' => TaskMetrics::averageProgressPercentage($p->tasks),
                'kontributor' => $p->tasks->pluck('user_id')->unique()->count(),
                'segmen' => TaskMetrics::segmen($p->tasks),
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
        $project->load(['tasks.user', 'tasks.reviewer', 'tasks.evidences', 'deliverables']);

        $ringkas = TaskMetrics::ringkasStatus($project->tasks);
        $kanban = TaskMetrics::kanban($project->tasks);
        $kontributor = $project->tasks->groupBy('user_id')->map(function ($items) {
            $r = TaskMetrics::ringkasStatus($items);

            return [
                'user' => $items->first()->user,
                'total' => $r['total'],
                'pct' => TaskMetrics::averageProgressPercentage($items),
                'ringkas' => $r,
            ];
        })->sortByDesc('total')->values();

        $projects = Project::orderBy('client_or_rd')->get();
        $semuaUser = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.proyek-detail', compact('project', 'ringkas', 'kanban', 'kontributor', 'projects', 'semuaUser'));
    }

    public function updateDeliverablePercentages(Request $request, Project $project)
    {
        $validated = $request->validate([
            'percentages' => ['required', 'array'],
            'percentages.*' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $deliverableIds = $project->deliverables()->pluck('id')->all();
        $percentages = collect($validated['percentages']);

        if ($percentages->keys()->diff($deliverableIds)->isNotEmpty()) {
            return response()->json(['message' => 'Key deliverable tidak valid untuk proyek ini.'], 422);
        }

        if ($percentages->sum() !== 100) {
            return response()->json(['message' => 'Total persentase key deliverable harus tepat 100%.'], 422);
        }

        ProjectDeliverable::where('project_id', $project->id)
            ->whereIn('id', $percentages->keys())
            ->get()
            ->each(function (ProjectDeliverable $deliverable) use ($percentages) {
                $deliverable->update([
                    'completion_percentage' => $percentages->get((string) $deliverable->id),
                    'is_completed' => $percentages->get((string) $deliverable->id) === 100,
                ]);
            });

        return response()->json([
            'success' => true,
            'message' => 'Pembagian persentase key deliverable berhasil disimpan.',
        ]);
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

        $project = Project::create([
            'client_or_rd' => $validated['nama'],
        ]);

        return back()->with('success', 'Proyek "' . $project->client_or_rd . '" berhasil dibuat.');
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
            'evidence.*' => [
                'nullable',
                'file',
                'max:' . BatasUnggah::maksKb(),
                'extensions:' . implode(',', BatasUnggah::EKSTENSI),
            ],
        ], [
            'evidence.*.uploaded' => 'File evidence gagal diunggah. Ukuran maksimal ' . BatasUnggah::maksMb() . ' MB per file.',
            'evidence.*.max' => 'Ukuran file evidence maksimal ' . BatasUnggah::maksMb() . ' MB per file.',
            'evidence.*.extensions' => 'Format evidence harus salah satu dari: ' . implode(', ', BatasUnggah::EKSTENSI) . '.',
            'selesai.after_or_equal' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.',
        ]);

        if (empty($validated['project_id']) && empty($validated['project_baru'])) {
            $pesan = 'Pilih proyek yang tersedia atau isi nama proyek baru.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan, 'errors' => ['project_id' => [$pesan]]], 422);
            }

            return back()->withInput()->withErrors(['project_id' => $pesan]);
        }

        $projectId = $validated['project_id'] ?? Project::create([
            'client_or_rd' => $validated['project_baru'],
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

        TaskEvidence::simpanBerkas($task, $request->file('evidence', []), $request->user()->id);

        $pegawai = User::find($validated['user_id']);
        $pesan = 'Tugas "' . $task->judul . '" berhasil di-assign ke ' . $pegawai->name . '.';

        // Form modal mengirim lewat fetch supaya halamannya tidak berpindah.
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'pesan' => $pesan, 'id' => $task->id]);
        }

        return back()->with('success', $pesan);
    }

    /**
     * Delete a project and all its related tasks and evidences
     */
    public function destroy(Request $request, Project $project)
    {
        $namaProyek = $project->client_or_rd;

        // Delete all evidences related to tasks in this project
        foreach ($project->tasks as $task) {
            foreach ($task->evidences as $evidence) {
                $evidence->hapusBerkas();
                $evidence->delete();
            }
        }

        // Delete all tasks in this project
        $project->tasks()->delete();

        // Delete the project itself
        $project->delete();

        $pesan = 'Proyek "' . $namaProyek . '" berhasil dihapus.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'pesan' => $pesan]);
        }

        return redirect()->route('admin.proyek.index')->with('success', $pesan);
    }
}
