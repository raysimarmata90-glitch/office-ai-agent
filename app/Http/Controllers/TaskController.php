<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskEvidence;
use App\Models\User;
use App\Services\TaskMetrics;
use App\Support\BatasUnggah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::with(['project', 'reviewer', 'user', 'evidences'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $ringkas = TaskMetrics::ringkasStatus($tasks);
        $kanban = TaskMetrics::kanban($tasks);

        // Timeline dirender di sisi klien supaya filter rentang (hari ini s/d 1 tahun)
        // bisa berganti tanpa memuat ulang halaman.
        $timeline = $tasks->filter(fn ($t) => $t->mulai && $t->selesai)
            ->sortBy('mulai')
            ->map(fn (Task $t) => [
                'judul' => $t->judul,
                'proyek' => $t->project?->nama,
                'status' => $t->statusLabel(),
                'warna' => $t->project?->warna ?? '#f55d14',
                'mulai' => $t->mulai->toDateString(),
                'selesai' => $t->selesai->toDateString(),
            ])
            ->values();

        $kpi = [
            ['label' => 'Total Tugas', 'nilai' => $ringkas['total']],
            ['label' => 'To Do', 'nilai' => $ringkas['todo']],
            ['label' => 'Sedang Dikerjakan', 'nilai' => $ringkas['progress']],
            ['label' => 'Selesai', 'nilai' => $ringkas['done']],
        ];

        $projects = Project::orderBy('nama')->get();
        $rekan = User::where('is_active', true)->orderBy('name')->get();

        $maksUnggahMb = BatasUnggah::maksMb();
        $acceptUnggah = BatasUnggah::accept();

        return view('pekerjaan', compact(
            'tasks', 'ringkas', 'kanban', 'timeline', 'kpi',
            'projects', 'rekan', 'user', 'maksUnggahMb', 'acceptUnggah'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_baru' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Task::daftarStatus()))],
            'prioritas' => ['required', Rule::in(['Tinggi', 'Sedang', 'Rendah'])],
            'mulai' => ['required', 'date'],
            'selesai' => ['required', 'date', 'after_or_equal:mulai'],
            'user_id' => ['required', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'exists:users,id'],
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
            'judul.required' => 'Judul tugas wajib diisi.',
            'mulai.required' => 'Waktu mulai wajib diisi.',
            'selesai.required' => 'Waktu selesai wajib diisi.',
            'selesai.after_or_equal' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.',
            'user_id.required' => 'Tugas harus di-assign ke seorang pegawai.',
        ]);

        if (empty($validated['project_id']) && empty($validated['project_baru'])) {
            return back()
                ->withInput()
                ->withErrors(['project_id' => 'Pilih proyek yang tersedia atau isi nama proyek baru.']);
        }

        $projectId = $validated['project_id'] ?? null;

        if (! $projectId) {
            $projectId = Project::create([
                'nama' => $validated['project_baru'],
                'warna' => '#f55d14',
                'mulai' => $validated['mulai'],
                'selesai' => $validated['selesai'],
                'created_by' => $request->user()->id,
            ])->id;
        }

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
            'selesai_pada' => $validated['status'] === Task::STATUS_DONE ? now() : null,
        ]);

        $this->simpanEvidence($request, $task);

        return redirect()
            ->route('pekerjaan.index')
            ->with('success', 'Tugas "' . $task->judul . '" berhasil disimpan.');
    }

    public function updateStatus(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah tugas ini.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::daftarStatus()))],
        ]);

        $task->update([
            'status' => $validated['status'],
            'selesai_pada' => $validated['status'] === Task::STATUS_DONE ? now() : null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $task->status]);
        }

        return back()->with('success', 'Status tugas diperbarui.');
    }

    public function show(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id
            && $task->reviewer_id !== $request->user()->id
            && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $task->load(['project', 'user', 'reviewer', 'evidences']);

        return response()->json([
            'id' => $task->id,
            'judul' => $task->judul,
            'deskripsi' => $task->deskripsi,
            'proyek' => $task->project?->nama,
            'warna' => $task->project?->warna,
            'status' => $task->status,
            'status_label' => $task->statusLabel(),
            'prioritas' => $task->prioritas,
            'mulai' => $task->mulai?->format('d/m/y'),
            'selesai' => $task->selesai?->format('d/m/y'),
            'pemilik' => $task->user?->name,
            'reviewer' => $task->reviewer?->name,
            'evidences' => $task->evidences->map(fn ($e) => [
                'nama' => $e->nama_file,
                'url' => route('tasks.evidence', $e->id),
                'gambar' => $e->isGambar(),
            ]),
        ]);
    }

    public function evidence(Request $request, TaskEvidence $evidence)
    {
        $task = $evidence->task;

        if ($task->user_id !== $request->user()->id
            && $task->reviewer_id !== $request->user()->id
            && ! $request->user()->isAdmin()) {
            abort(403);
        }

        if (! Storage::disk('public')->exists($evidence->path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($evidence->path));
    }

    protected function simpanEvidence(Request $request, Task $task): void
    {
        foreach ((array) $request->file('evidence', []) as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            $path = $file->store('evidence/' . $task->id, 'public');

            TaskEvidence::create([
                'task_id' => $task->id,
                'uploaded_by' => $request->user()->id,
                'nama_file' => $file->getClientOriginalName(),
                'path' => $path,
                'mime' => $file->getClientMimeType(),
                'ukuran' => $file->getSize(),
            ]);
        }
    }
}
