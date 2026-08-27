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
                'id' => $t->id,
                'judul' => $t->judul,
                'proyek' => $t->project?->nama ?? 'Tanpa Proyek',
                'status_key' => $t->status,
                'status' => $t->statusLabel(),
                'prioritas' => $t->prioritas,
                'reviewer' => $t->reviewer?->name,
                'evidence' => $t->evidences->count(),
                // Bar timeline diwarnai status agar bahasa warnanya sama
                // dengan titik kanban dan badge di tabel.
                'warna' => Task::titikStatus($t->status),
                'mulai' => $t->mulai->toDateString(),
                'selesai' => $t->selesai->toDateString(),
                // Porsi bar yang sudah berjalan, dipakai untuk bagian pekat pada gantt.
                'progres' => $t->status === Task::STATUS_DONE ? 100 : $this->persenBerjalan($t),
            ])
            ->values();

        // Komposisi per status untuk pie chart + bar. Seluruh status selalu ikut
        // terdaftar walau nilainya nol, supaya legenda tidak berubah-ubah.
        $statusRingkas = collect(Task::daftarStatus())
            ->map(function ($label, $key) use ($tasks) {
                $jumlah = $tasks->where('status', $key)->count();

                $isi = $tasks->where('status', $key);

                return [
                    'key' => $key,
                    'label' => $label,
                    'jumlah' => $jumlah,
                    'proyek' => $isi->pluck('project_id')->filter()->unique()->count(),
                    'warna' => Task::titikStatus($key),
                    'persen' => $tasks->count() ? round($jumlah / $tasks->count() * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();

        // Gradien pie chart dihitung di sini; kalau kosong, lingkaran tetap
        // digambar sebagai cincin abu supaya tata letaknya tidak berubah.
        $potongan = [];
        $sudut = 0.0;
        foreach ($statusRingkas as $st) {
            $lebar = $tasks->count() ? $st['jumlah'] / $tasks->count() * 360 : 0;
            if ($lebar > 0) {
                $potongan[] = $st['warna'] . ' ' . round($sudut, 3) . 'deg ' . round($sudut + $lebar, 3) . 'deg';
                $sudut += $lebar;
            }
        }
        $gradienStatus = $potongan ? implode(', ', $potongan) : 'var(--line3) 0deg 360deg';

        $projects = Project::orderBy('nama')->get();
        $rekan = User::where('is_active', true)->orderBy('name')->get();

        $maksUnggahMb = BatasUnggah::maksMb();
        $acceptUnggah = BatasUnggah::accept();

        // Dipakai view untuk memutuskan munculnya tombol edit per baris.
        $bolehEdit = $tasks->mapWithKeys(fn (Task $t) => [$t->id => $this->bolehEdit($user, $t)]);

        return view('pekerjaan', compact(
            'tasks', 'ringkas', 'kanban', 'timeline', 'statusRingkas', 'gradienStatus',
            'projects', 'rekan', 'user', 'maksUnggahMb', 'acceptUnggah', 'bolehEdit'
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
            $galat = ['project_id' => 'Pilih proyek yang tersedia atau isi nama proyek baru.'];

            if ($request->expectsJson()) {
                return response()->json(['message' => $galat['project_id'], 'errors' => ['project_id' => [$galat['project_id']]]], 422);
            }

            return back()->withInput()->withErrors($galat);
        }

        $projectId = $validated['project_id'] ?? null;

        if (! $projectId) {
            $projectId = Project::create([
                'nama' => $validated['project_baru'],
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

        $pesan = 'Tugas "' . $task->judul . '" berhasil disimpan.';

        // Form modal mengirim lewat fetch dan tetap tinggal di halamannya;
        // pengalihan ke Pekerjaan Saya hanya benar untuk kiriman form biasa.
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'pesan' => $pesan, 'id' => $task->id]);
        }

        return redirect()->route('pekerjaan.index')->with('success', $pesan);
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
            'objektif' => $task->objektif,
            'harapan' => $task->harapan,
            'deliverable' => $task->deliverable,
            'detail' => $task->detail,
            'progress_text' => $task->progress_text,
            'estimasi' => $task->estimasi,
            'proyek' => $task->project?->nama,
            'warna' => $task->project?->warna,
            'status' => $task->status,
            'status_label' => $task->statusLabel(),
            'status_warna' => Task::warnaStatus($task->status),
            'prioritas' => $task->prioritas,
            'mulai' => $task->mulai?->format('d/m/Y'),
            'selesai' => $task->selesai?->format('d/m/Y'),
            'pemilik' => $task->user?->name,
            'reviewer' => $task->reviewer?->name,
            'dibuat' => $task->created_at?->translatedFormat('d M Y, H:i'),
            'diubah' => $task->updated_at?->translatedFormat('d M Y, H:i'),
            // Nilai mentah untuk mengisi ulang form edit.
            'form' => [
                'project_id' => $task->project_id,
                'user_id' => $task->user_id,
                'reviewer_id' => $task->reviewer_id,
                'mulai' => $task->mulai?->toDateString(),
                'selesai' => $task->selesai?->toDateString(),
            ],
            'boleh_edit' => $this->bolehEdit($request->user(), $task),
            'evidences' => $task->evidences->map(fn ($e) => [
                'id' => $e->id,
                'nama' => $e->nama_file,
                'ukuran' => $e->ukuran,
                'mime' => $e->mime,
                'url' => route('tasks.evidence', $e->id),
                'unduh' => route('tasks.evidence', ['evidence' => $e->id, 'unduh' => 1]),
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

        $berkas = Storage::disk('public')->path($evidence->path);

        return $request->boolean('unduh')
            ? response()->download($berkas, $evidence->nama_file)
            : response()->file($berkas);
    }

    /** Berapa persen rentang tugas yang sudah dilewati hingga hari ini. */
    protected function persenBerjalan(Task $t): int
    {
        if (! $t->mulai || ! $t->selesai) {
            return 0;
        }

        $total = $t->mulai->diffInDays($t->selesai) ?: 1;
        $lewat = $t->mulai->diffInDays(now(), false);

        return (int) max(0, min(100, round($lewat / $total * 100)));
    }

    /** Tugas hanya boleh diubah oleh admin atau orang yang membuatnya. */
    public function bolehEdit(?User $user, Task $task): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $task->created_by === $user->id;
    }

    public function update(Request $request, Task $task)
    {
        if (! $this->bolehEdit($request->user(), $task)) {
            abort(403, 'Tugas ini hanya bisa diubah oleh admin atau pembuatnya.');
        }

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
            'selesai.after_or_equal' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.',
        ]);

        if (empty($validated['project_id']) && empty($validated['project_baru'])) {
            $galat = ['project_id' => 'Pilih proyek yang tersedia atau isi nama proyek baru.'];

            if ($request->expectsJson()) {
                return response()->json(['message' => $galat['project_id'], 'errors' => ['project_id' => [$galat['project_id']]]], 422);
            }

            return back()->withInput()->withErrors($galat);
        }

        $projectId = $validated['project_id'] ?? null;

        if (! $projectId) {
            $projectId = Project::create([
                'nama' => $validated['project_baru'],
                'mulai' => $validated['mulai'],
                'selesai' => $validated['selesai'],
                'created_by' => $request->user()->id,
            ])->id;
        }

        $task->update([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'project_id' => $projectId,
            'user_id' => $validated['user_id'],
            'reviewer_id' => $validated['reviewer_id'] ?? null,
            'status' => $validated['status'],
            'prioritas' => $validated['prioritas'],
            'mulai' => $validated['mulai'],
            'selesai' => $validated['selesai'],
            'selesai_pada' => $validated['status'] === Task::STATUS_DONE ? ($task->selesai_pada ?? now()) : null,
        ]);

        $this->simpanEvidence($request, $task);

        $pesan = 'Tugas "' . $task->judul . '" berhasil diperbarui.';

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'pesan' => $pesan, 'id' => $task->id]);
        }

        return redirect()->route('pekerjaan.index')->with('success', $pesan);
    }

    public function destroy(Request $request, Task $task)
    {
        if (! $this->bolehEdit($request->user(), $task)) {
            abort(403, 'Tugas ini hanya bisa dihapus oleh admin atau pembuatnya.');
        }

        $judul = $task->judul;

        foreach ($task->evidences as $evidence) {
            Storage::disk('public')->delete($evidence->path);
        }
        $task->evidences()->delete();
        $task->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'pesan' => 'Tugas "' . $judul . '" dihapus.']);
        }

        return redirect()->route('pekerjaan.index')->with('success', 'Tugas "' . $judul . '" dihapus.');
    }

    /** Hapus satu evidence dari tugas (dipakai saat mengubah tugas). */
    public function hapusEvidence(Request $request, TaskEvidence $evidence)
    {
        $task = $evidence->task;

        if (! $this->bolehEdit($request->user(), $task)) {
            abort(403, 'Evidence ini hanya bisa dihapus oleh admin atau pembuat tugas.');
        }

        Storage::disk('public')->delete($evidence->path);
        $nama = $evidence->nama_file;
        $evidence->delete();

        return response()->json(['success' => true, 'pesan' => 'File "' . $nama . '" dihapus.']);
    }

    protected function simpanEvidence(Request $request, Task $task): void
    {
        TaskEvidence::simpanBerkas($task, $request->file('evidence', []), $request->user()->id);
    }
}
