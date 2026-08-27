<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\Project;
use App\Models\ProjectDeliverable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller khusus untuk chat terstruktur project tracking
 * Flow: Client/R&D → Key Deliverables → Objective/As-Is → Timeline → Tasks → Persentase
 */
class ProjectChatController extends Controller
{
    /**
     * Inisialisasi chat session baru atau ambil yang aktif
     */
    public function initSession(Request $request)
    {
        $user = $request->user();
        $session = ChatSession::getOrCreateSession($user->id);

        return $this->generateResponse($session);
    }

    /**
     * Handle user response untuk setiap step
     */
    public function handleMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'session_id' => 'nullable|exists:chat_sessions,id',
        ]);

        $user = $request->user();
        $session = $validated['session_id']
            ? ChatSession::findOrFail($validated['session_id'])
            : ChatSession::getOrCreateSession($user->id);

        // Pastikan session milik user
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $message = trim($validated['message']);

        // Process berdasarkan current step
        return match($session->current_step) {
            'select_client' => $this->handleSelectClient($session, $message),
            'select_deliverable' => $this->handleSelectDeliverable($session, $message),
            'objective_as_is' => $this->handleObjectiveAsIs($session, $message),
            'timeline_validation' => $this->handleTimelineValidation($session, $message),
            'task_inquiry' => $this->handleTaskInquiry($session, $message),
            'percentage_allocation' => $this->handlePercentageAllocation($session, $message),
            default => response()->json([
                'success' => false,
                'error' => 'Invalid step'
            ], 400)
        };
    }

    /**
     * Step 1: Pilih Client/R&D dari daftar
     */
    private function handleSelectClient(ChatSession $session, string $message)
    {
        // Cari project berdasarkan client name
        $project = Project::where('is_archived', false)
            ->where('is_blocked', false)
            ->where(function($q) use ($message) {
                $q->where('client_or_rd', 'ILIKE', $message)
                  ->orWhere('client_or_rd', 'ILIKE', "%{$message}%");
            })
            ->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project tidak ditemukan. Silakan pilih dari daftar yang tersedia.',
                'options' => $this->getClientOptions()
            ]);
        }

        // Save project selection
        $session->project_id = $project->id;
        $session->saveStepData('selected_client', $project->client_or_rd);
        $session->saveStepData('project_name', $project->key_deliverables);
        $session->moveToNextStep();

        return $this->generateResponse($session);
    }

    /**
     * Step 2: Pilih Key Deliverable dengan kategori (COMMERCIAL, TECH, EKSPANSI, LEGAL)
     */
    private function handleSelectDeliverable(ChatSession $session, string $message)
    {
        if (!$session->project_id) {
            return response()->json([
                'success' => false,
                'error' => 'Project belum dipilih'
            ], 400);
        }

        // Cari deliverable
        $deliverable = ProjectDeliverable::where('project_id', $session->project_id)
            ->where(function($q) use ($message) {
                $q->where('deliverable_name', 'ILIKE', $message)
                  ->orWhere('deliverable_name', 'ILIKE', "%{$message}%")
                  ->orWhere('code', $message);
            })
            ->first();

        if (!$deliverable) {
            return response()->json([
                'success' => false,
                'message' => 'Key deliverable tidak ditemukan. Silakan pilih dari daftar yang tersedia.',
                'options' => $this->getDeliverableOptions($session->project_id)
            ]);
        }

        // Save deliverable selection
        $session->project_deliverable_id = $deliverable->id;
        $session->saveStepData('selected_deliverable', $deliverable->deliverable_name);
        $session->saveStepData('deliverable_code', $deliverable->code);
        $session->saveStepData('deliverable_category', $deliverable->category);
        $session->moveToNextStep();

        return $this->generateResponse($session);
    }

    /**
     * Step 3: Tanya objektif as-is - apa yang sedang dikerjakan
     */
    private function handleObjectiveAsIs(ChatSession $session, string $message)
    {
        // Validasi input tidak boleh terlalu pendek
        if (strlen($message) < 10) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon jelaskan lebih detail apa yang sedang Anda kerjakan pada proyek ' .
                           ($session->getStepData('selected_client') ?? 'ini') . '.'
            ]);
        }

        $session->saveStepData('objective_as_is', $message);
        $session->moveToNextStep();

        return $this->generateResponse($session);
    }

    /**
     * Step 4: Validasi timeline - menanyakan kembali berapa lama pengerjaan
     */
    private function handleTimelineValidation(ChatSession $session, string $message)
    {
        // Parse estimasi waktu dari input user
        $estimatedDays = $this->parseTimeEstimation($message);

        if ($estimatedDays === null) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon berikan estimasi waktu pengerjaan yang jelas (contoh: "2 hari", "1 minggu", "3 bulan")'
            ]);
        }

        $session->saveStepData('estimated_days', $estimatedDays);
        $session->saveStepData('timeline_text', $message);
        $session->moveToNextStep();

        return $this->generateResponse($session);
    }

    /**
     * Step 5: Tanya tugas - masih ada tugas lain atau tidak
     */
    private function handleTaskInquiry(ChatSession $session, string $message)
    {
        $lowerMessage = strtolower(trim($message));

        // Check apakah user ingin menambah task lagi
        if (in_array($lowerMessage, ['ya', 'iya', 'yes', 'ada', 'masih ada'])) {
            // Reset ke select_deliverable untuk task berikutnya
            $session->current_step = 'select_deliverable';
            $session->save();

            return $this->generateResponse($session);
        }

        // User selesai dengan tasks
        $session->saveStepData('has_more_tasks', false);
        $session->moveToNextStep();

        return $this->generateResponse($session);
    }

    /**
     * Step 6: Penentuan persentase per pembagian key deliverables
     */
    private function handlePercentageAllocation(ChatSession $session, string $message)
    {
        // Parse persentase dari input
        $percentage = $this->parsePercentage($message);

        if ($percentage === null || $percentage < 0 || $percentage > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Mohon masukkan persentase yang valid (0-100)'
            ]);
        }

        // Save percentage untuk deliverable saat ini
        $deliverableId = $session->project_deliverable_id;
        if ($deliverableId) {
            $deliverable = ProjectDeliverable::find($deliverableId);
            if ($deliverable) {
                $deliverable->completion_percentage = $percentage;
                $deliverable->save();
            }
        }

        $session->saveStepData('completion_percentage', $percentage);

        // Mark session as completed
        $session->current_step = 'completed';
        $session->is_active = false;
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Data progress pekerjaan Anda telah berhasil disimpan.',
            'session_completed' => true,
            'summary' => $this->generateSessionSummary($session)
        ]);
    }

    /**
     * Generate response berdasarkan current step
     */
    private function generateResponse(ChatSession $session)
    {
        $response = [
            'success' => true,
            'session_id' => $session->id,
            'current_step' => $session->current_step,
        ];

        switch ($session->current_step) {
            case 'select_client':
                $response['message'] = 'Selamat datang! Silakan pilih Client atau R&D yang sedang Anda kerjakan:';
                $response['options'] = $this->getClientOptions();
                $response['question_type'] = 'select';
                break;

            case 'select_deliverable':
                $clientName = $session->getStepData('selected_client');
                $response['message'] = "Baik, proyek **{$clientName}**. Silakan pilih Key Deliverable yang akan Anda kerjakan:";
                $response['options'] = $this->getDeliverableOptions($session->project_id);
                $response['question_type'] = 'select';
                break;

            case 'objective_as_is':
                $deliverableName = $session->getStepData('selected_deliverable');
                $category = $session->getStepData('deliverable_category');
                $response['message'] = "Anda memilih **{$deliverableName}** (kategori: **{$category}**). Apa objektif pada pekerjaan ini?";
                $response['question_type'] = 'text';
                break;

            case 'timeline_validation':
                $response['message'] = 'Berapa lama estimasi waktu pengerjaan untuk task ini? (contoh: "2 hari", "1 minggu", "3 bulan")';
                $response['question_type'] = 'text';
                break;

            case 'task_inquiry':
                $response['message'] = 'Apakah masih ada tugas lain yang ingin Anda tambahkan untuk proyek ini?';
                $response['options'] = ['Ya', 'Tidak'];
                $response['question_type'] = 'select';
                break;

            case 'percentage_allocation':
                $deliverableName = $session->getStepData('selected_deliverable');
                $response['message'] = "Berapa persen progress penyelesaian untuk **{$deliverableName}**? (0-100)";
                $response['question_type'] = 'text';
                break;

            case 'completed':
                $response['message'] = 'Session sudah selesai.';
                $response['session_completed'] = true;
                break;
        }

        return response()->json($response);
    }

    /**
     * Get list of client/R&D options
     */
    private function getClientOptions(): array
    {
        return Project::where('is_archived', false)
            ->where('is_blocked', false)
            ->orderBy('no')
            ->get()
            ->map(function($project) {
                return [
                    'value' => $project->client_or_rd,
                    'label' => $project->client_or_rd . ' - ' . $project->key_deliverables,
                    'status' => $project->status,
                ];
            })
            ->toArray();
    }

    /**
     * Get list of deliverable options for a project
     */
    private function getDeliverableOptions(int $projectId): array
    {
        $deliverables = ProjectDeliverable::where('project_id', $projectId)
            ->orderBy('code')
            ->get();

        // Group by category
        $grouped = $deliverables->groupBy('category');

        $options = [];
        foreach ($grouped as $category => $items) {
            $categoryLabel = match($category) {
                'COMMERCIAL' => '💼 COMMERCIAL',
                'TECH' => '⚙️ TECH',
                'EKSPANSI' => '🚀 EKSPANSI',
                'LEGAL' => '⚖️ LEGAL',
                default => '📋 OTHER'
            };

            foreach ($items as $item) {
                $options[] = [
                    'value' => $item->deliverable_name,
                    'label' => "[{$item->code}] {$item->deliverable_name}",
                    'category' => $category,
                    'category_label' => $categoryLabel,
                    'pic' => $item->pic,
                ];
            }
        }

        return $options;
    }

    /**
     * Parse time estimation dari user input
     */
    private function parseTimeEstimation(string $input): ?int
    {
        $input = strtolower(trim($input));

        // Match pattern: "X hari/minggu/bulan"
        if (preg_match('/(\d+)\s*(hari|day|days)/i', $input, $matches)) {
            return (int)$matches[1];
        }

        if (preg_match('/(\d+)\s*(minggu|week|weeks)/i', $input, $matches)) {
            return (int)$matches[1] * 7;
        }

        if (preg_match('/(\d+)\s*(bulan|month|months)/i', $input, $matches)) {
            return (int)$matches[1] * 30;
        }

        return null;
    }

    /**
     * Parse percentage dari user input
     */
    private function parsePercentage(string $input): ?int
    {
        $input = trim($input);

        // Remove "%" sign if present
        $input = str_replace(['%', 'persen', 'percent'], '', $input);
        $input = trim($input);

        if (is_numeric($input)) {
            return (int)$input;
        }

        return null;
    }

    /**
     * Generate summary dari session
     */
    private function generateSessionSummary(ChatSession $session): array
    {
        $project = $session->project;
        $deliverable = $session->projectDeliverable;

        return [
            'client' => $session->getStepData('selected_client'),
            'project' => $project?->key_deliverables,
            'deliverable' => $session->getStepData('selected_deliverable'),
            'deliverable_category' => $session->getStepData('deliverable_category'),
            'objective' => $session->getStepData('objective_as_is'),
            'estimated_days' => $session->getStepData('estimated_days'),
            'completion_percentage' => $session->getStepData('completion_percentage'),
        ];
    }

    /**
     * Reset chat session
     */
    public function resetSession(Request $request)
    {
        $session = ChatSession::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        if ($session) {
            $session->reset();
        }

        return $this->initSession($request);
    }
}
