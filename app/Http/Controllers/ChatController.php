<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\Pekerjaan;
use App\Models\QuestionTemplate;
use App\Models\User;
use App\Services\Agent\AgentOrchestrator;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected AgentOrchestrator $agent;

    public function __construct(AgentOrchestrator $agent)
    {
        $this->agent = $agent;
    }

    /** Jumlah riwayat yang dimuat per permintaan pada panel riwayat. */
    public const RIWAYAT_PER_HALAMAN = 50;

    /** Pesan pembuka agent — pertanyaan pertama selalu menanyakan proyek yang dikerjakan. */
    public const PERTANYAAN_AWAL = 'Halo! Senang bertemu dengan Anda. Apa proyek yang sedang Anda kerjakan hari ini?';

    public const PROMPT_AWAL = 'Sapa user dengan hangat, lalu gali proyek, objektif, harapan, task, dan estimasi durasi pengerjaan.';

    /**
     * Buat percakapan baru berikut pesan pembukanya.
     */
    public static function percakapanBaru(User $user): Conversation
    {
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'title' => 'Percakapan Baru',
            'status' => 'active',
            'current_step' => 1,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => self::PERTANYAAN_AWAL,
            'step_number' => 1,
            'metadata' => ['system_prompt' => self::PROMPT_AWAL],
        ]);

        return $conversation;
    }

    /**
     * Tombol "Chat Baru": tampilkan layar percakapan kosong tanpa menyentuh database.
     * Barisnya baru dibuat saat user mengirim jawaban pertama (lihat mulai()).
     */
    public function baru()
    {
        return view('chat', ['conversation' => null]);
    }

    /**
     * Pesan pertama dari layar "Chat Baru": buat percakapannya sekarang,
     * lalu proses seperti pengiriman pesan biasa.
     */
    public function mulai(Request $request)
    {
        $request->validate(['message' => ['required', 'string']]);

        $conversation = self::percakapanBaru($request->user());
        $response = $this->sendMessage($request, $conversation);

        $data = $response->getData(true);
        $data['conversation_id'] = $conversation->id;
        $data['conversation_url'] = route('chat.show', $conversation->id);

        return response()->json($data, $response->getStatusCode());
    }

    /**
     * Riwayat percakapan bertahap untuk panel riwayat (50 per permintaan).
     */
    public function riwayat(Request $request)
    {
        $lewati = max(0, (int) $request->query('offset', 0));
        $batas = self::RIWAYAT_PER_HALAMAN;

        // Ambil satu baris ekstra untuk tahu apakah masih ada lanjutannya.
        $baris = Conversation::with('pesanTerakhirUser')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->skip($lewati)
            ->take($batas + 1)
            ->get();

        $habis = $baris->count() <= $batas;
        $riwayat = $baris->take($batas);

        $html = view('partials.hist-list', [
            'riwayat' => $riwayat,
            'aktifId' => $request->query('aktif') !== null ? (int) $request->query('aktif') : null,
        ])->render();

        return response()->json([
            'html' => $html,
            'jumlah' => $riwayat->count(),
            'habis' => $habis,
        ]);
    }

    public function startConversation(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $department = Department::findOrFail($validated['department_id']);

        // Ensure user can only create conversation for their own department
        if (auth()->user()->department_id !== $department->id) {
            abort(403, 'Anda hanya dapat membuat percakapan untuk departemen Anda sendiri.');
        }

        // Create new conversation
        $conversation = Conversation::create([
            'user_id' => auth()->id(),
            'department_id' => $department->id,
            'title' => 'Conversation with ' . $department->name . ' Agent',
            'status' => 'active',
            'current_step' => 1,
        ]);

        // Get first question template
        $firstQuestion = QuestionTemplate::where('department_id', $department->id)
            ->where('step_number', 1)
            ->first();

        if ($firstQuestion) {
            // Create first AI message
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => self::PERTANYAAN_AWAL,
                'step_number' => 1,
                'metadata' => ['system_prompt' => self::PROMPT_AWAL],
            ]);
        }

        return redirect()->route('chat.show', $conversation->id);
    }

    public function show(Conversation $conversation)
    {
        // Ensure user owns this conversation
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $conversation->load(['messages', 'department']);

        return view('chat', compact('conversation'));
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        // Ensure user owns this conversation
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        // Save user message
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'content' => $validated['message'],
            'step_number' => $conversation->current_step,
        ]);

        // Update conversation title with first user message (smart title)
        if ($conversation->current_step === 1 && $conversation->messages()->where('sender_type', 'user')->count() === 1) {
            $title = $this->generateConversationTitle($validated['message']);
            $conversation->update(['title' => $title]);
        }

        // Build context for agent
        $context = [
            'user_id' => auth()->id(),
            'conversation_id' => $conversation->id,
            'department_id' => $conversation->department_id,
            'department_code' => $conversation->department->code,
            'current_step' => $conversation->current_step,
            'conversation_history' => $this->conversationHistory($conversation, $userMessage->id),
        ];

        // Process with Agent Orchestrator
        $agentResponse = $this->agent->process($validated['message'], $context);

        if ($agentResponse['success']) {
            $responseContent = $agentResponse['response']['content'];

            if ($this->isNoMoreWork(trim($validated['message']))) {
                $projects = $this->extractProjectsFromMessages(
                    $conversation->messages()
                        ->where('sender_type', 'user')
                        ->orderBy('created_at', 'asc')
                        ->pluck('content')
                        ->all()
                );
                $responseContent = $this->ensureSummaryProjectBlocks(
                    $this->normalizeSummaryProjects($responseContent, $projects),
                    $projects
                );
            }

            // Create AI message
            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => $responseContent,
                'step_number' => $conversation->current_step,
                'metadata' => [
                    'tools_used' => $agentResponse['tools_used'] ?? [],
                    'confidence' => $agentResponse['response']['confidence'] ?? 0,
                    'system_prompt_version' => $agentResponse['metadata']['system_prompt_version'] ?? null,
                ],
            ]);

            // Update conversation step if needed
            if (isset($agentResponse['response']['next_action'])) {
                $conversation->incrementStep();
            }

            if ($this->isActivityConversationComplete($conversation)) {
                $metadata = $conversation->metadata ?? [];
                $dailyActivity = $this->buildDailyActivity($conversation);
                foreach ($dailyActivity['projects'] as &$projectActivity) {
                    $generatedDescription = $this->agent->generateWorkDescription($projectActivity);
                    if ($generatedDescription !== null) {
                        $projectActivity['work_description'] = $generatedDescription;
                        $projectActivity['summary'] = $generatedDescription;
                    }
                }
                unset($projectActivity);

                $firstProject = $dailyActivity['projects'][0] ?? $dailyActivity;
                $dailyActivity['project_company'] = $firstProject['project_company'] ?? null;
                $dailyActivity['summary'] = $firstProject['summary'] ?? '';
                $dailyActivity['work_description'] = $firstProject['work_description'] ?? '';
                $metadata['daily_activity'] = $dailyActivity;
                $conversation->update(['metadata' => $metadata]);
                $conversation->markAsCompleted();
                $this->savePekerjaan($conversation, $dailyActivity['projects']);

                $responseContent = 'Baik, catatan aktivitas hari ini sudah lengkap dan disimpan. Pekerjaan Anda: '
                    . collect($dailyActivity['projects'])
                        ->pluck('summary')
                        ->filter()
                        ->implode(' | ');
                $aiMessage->update(['content' => $responseContent]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'ai_response' => [
                    'content' => $responseContent,
                    'type' => $agentResponse['response']['type'],
                    'confidence' => $agentResponse['response']['confidence'],
                ],
            ]);
        } else {
            // Fallback response
            $fallbackMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => $agentResponse['fallback_response'] ?? 'Maaf, terjadi kesalahan dalam memproses permintaan Anda.',
                'step_number' => $conversation->current_step,
                'metadata' => [
                    'is_fallback' => true,
                    'error' => $agentResponse['error'] ?? 'Unknown error',
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'ai_response' => [
                    'content' => $fallbackMessage->content,
                    'type' => 'fallback',
                ],
            ]);
        }
    }

    private function conversationHistory(Conversation $conversation, ?int $excludeMessageId = null): array
    {
        $history = [];
        $pendingUser = null;

        foreach ($conversation->messages()->orderBy('created_at')->get() as $message) {
            if ($message->id === $excludeMessageId) {
                continue;
            }

            if ($message->isFromUser()) {
                $pendingUser = $message->content;
            } elseif ($pendingUser !== null) {
                $history[] = [
                    'user_input' => $pendingUser,
                    'agent_response' => $message->content,
                ];
                $pendingUser = null;
            } elseif (empty($history)) {
                $history[] = [
                    'user_input' => '',
                    'agent_response' => $message->content,
                ];
            }
        }

        if ($pendingUser !== null) {
            $history[] = ['user_input' => $pendingUser, 'agent_response' => ''];
        }

        return $history;
    }

    private function isActivityConversationComplete(Conversation $conversation): bool
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();
        $userMessages = $messages
            ->where('sender_type', 'user')
            ->values();

        if ($userMessages->count() < 2) {
            return false;
        }

        $lastMessage = mb_strtolower(trim((string) $userMessages->last()->content));
        $lastAiMessage = $messages
            ->where('sender_type', 'ai')
            ->last();

        $isSummaryConfirmation = $lastAiMessage !== null
            && preg_match('/(catatan ini sudah sesuai|informasi ini sudah tepat|simpan sebagai catatan)/iu', $lastAiMessage->content) === 1;

        if ($isSummaryConfirmation) {
            return $this->isConfirmation($lastMessage);
        }

        if ($lastAiMessage !== null && $this->isPriorityQuestion($lastAiMessage->content)) {
            return false;
        }

        if ($this->isNoMoreWork($lastMessage)) {
            return true;
        }

        return $lastAiMessage !== null
            && $this->isConfirmation($lastMessage)
            && preg_match('/(sudah sesuai|catatan.*simpan|simpan.*catatan|konfirmasi)/iu', $lastAiMessage->content) === 1;
    }

    private function buildDailyActivity(Conversation $conversation): array
    {
        $userMessages = $conversation->messages()
            ->where('sender_type', 'user')
            ->orderBy('created_at', 'asc')
            ->pluck('content')
            ->reject(fn ($content) => $this->isCompletionMessage($content))
            ->values()
            ->all();

        $projects = [];
        $currentProject = null;
        $currentMessages = [];

        foreach ($userMessages as $content) {
            $project = $this->extractExplicitProject($content);
            if ($project !== null) {
                if ($currentProject !== null) {
                    $projects[] = $this->makeProjectActivity($currentProject, $currentMessages);
                }
                $currentProject = $project;
                $currentMessages = [];
                continue;
            }

            if ($currentProject !== null) {
                $currentMessages[] = $content;
            }
        }

        if ($currentProject !== null) {
            $projects[] = $this->makeProjectActivity($currentProject, $currentMessages);
        }

        if ($projects === []) {
            $project = $this->extractProjectFromMessages($userMessages);
            $projects[] = $this->makeProjectActivity($project ?? 'Tidak disebutkan', $userMessages);
        }

        $firstProject = $projects[0];

        return [
            'project_company' => $firstProject['project_company'],
            'activities' => $firstProject['activities'],
            'summary' => $firstProject['summary'],
            'work_description' => $firstProject['work_description'],
            'projects' => $projects,
            'completed_at' => now()->toIso8601String(),
        ];
    }

    private function makeProjectActivity(string $project, array $messages): array
    {
        $cleanMessages = array_values(array_unique(array_filter(array_map(
            fn (string $content): string => $this->cleanWorkDescription($content, $project),
            $messages
        ))));
        $workDescription = implode(' ', $cleanMessages);

        return [
            'project_company' => $project,
            'activities' => array_map(
                fn (string $content): array => [
                    'description' => $content,
                    'project_company' => $project,
                ],
                $messages
            ),
            'summary' => $workDescription,
            'work_description' => $workDescription,
        ];
    }

    private function savePekerjaan(Conversation $conversation, array $projectActivities): void
    {
        $user = $conversation->user;

        foreach ($projectActivities as $activity) {
            Pekerjaan::create([
                'user_id' => $user->id,
                'name' => $user->name,
                'division' => $user->department?->name,
                'nama_projek' => $activity['project_company'] ?? 'Tidak disebutkan',
                'pekerjaan' => $activity['work_description'] ?? $activity['summary'],
                'status' => 'on going',
                'kategori' => 'Medium',
            ]);
        }
    }

    private function extractExplicitProject(string $content): ?string
    {
        if (preg_match('/^\s*(?:proyek|projek|project)\s+(?!ini\b)([\p{L}\d][\p{L}\d ._-]*?)\s*$/iu', $content, $matches)) {
            return trim($matches[1], " .,_-");
        }

        return null;
    }

    private function extractProject(string $content): ?string
    {
        if (preg_match('/(?:proyek|projek|project)\s+([\p{L}\d][\p{L}\d ._-]*?)(?:\s+(?:bagian|untuk|di|dengan)\b|$)/iu', $content, $matches)) {
            return trim($matches[1], " .,_-");
        }

        if (preg_match('/\bsaya\s+(?:sedang\s+)?(?:mengerjakan|membuat|mengembangkan|membangun)\s+([\p{L}\d][\p{L}\d ._-]*?)(?:\s+(?:sekarang|saat ini|bagian|untuk|di|dengan)\b|$)/iu', $content, $matches)) {
            return trim($matches[1], " .,_-");
        }

        return null;
    }

    private function extractProjectFromMessages(array $messages): ?string
    {
        foreach ($messages as $content) {
            if (preg_match('/(?:proyek|projek|project)\s+/iu', $content)) {
                $project = $this->extractProject($content);
                if ($project !== null) {
                    return $project;
                }
            }
        }

        foreach ($messages as $content) {
            $project = $this->extractProject($content);
            if ($project !== null) {
                return $project;
            }
        }

        $firstAnswer = trim((string) ($messages[0] ?? ''));
        return $firstAnswer !== '' && mb_strlen($firstAnswer) <= 100
            ? $firstAnswer
            : null;
    }

    private function extractProjectsFromMessages(array $messages): array
    {
        $projects = [];

        foreach ($messages as $content) {
            $project = $this->extractExplicitProject($content);
            if ($project !== null && !in_array($project, $projects, true)) {
                $projects[] = $project;
            }
        }

        return $projects;
    }

    private function isProjectIntroduction(string $content): bool
    {
        return preg_match('/^\s*saya\s+(?:sedang\s+)?(?:mengerjakan|membuat|mengembangkan|membangun)\s+(?!(?:nya|hal tersebut|bagian itu)\b)/iu', $content) === 1;
    }

    private function cleanWorkDescription(string $content, ?string $project = null): string
    {
        $content = preg_replace('/^\s*saya\s+(?:sedang\s+)?(?:mengerjakan|membuat|mengembangkan|membangun)\s+(?:(?:proyek|projek|project)\s+)?/iu', '', trim($content));
        $content = preg_replace('/^\s*(?:saat ini|sekarang)\s+/iu', '', $content);

        if ($project !== null) {
            $content = preg_replace('/\b(?:proyek|projek|project)\s+' . preg_quote($project, '/') . '\b/iu', '', $content);
        }

        return trim($content, " .,-");
    }

    private function normalizeSummaryProjects(string $content, array $projects): string
    {
        if ($projects === []) {
            return $content;
        }

        $projectIndex = 0;
        return preg_replace_callback(
            '/^\s*Proyek\s*:\s*.*$/imu',
            function (array $matches) use ($projects, &$projectIndex): string {
                $project = $projects[$projectIndex] ?? end($projects);
                $projectIndex++;

                return 'Proyek: ' . $project;
            },
            $content
        ) ?? $content;
    }

    private function ensureSummaryProjectBlocks(string $content, array $projects): string
    {
        $projectCount = preg_match_all('/^\s*Proyek\s*:/imu', $content);
        $missingProjects = array_slice($projects, $projectCount ?: 0);

        foreach ($missingProjects as $project) {
            $content = rtrim($content) . "\n\nProyek: {$project}\nObjektif:\nHarapan:\nTask:\nEstimasi:";
        }

        return $content;
    }

    private function isCompletionMessage(string $content): bool
    {
        return $this->isNoMoreWork($content)
            || $this->isConfirmation($content)
            || $this->isPriorityResponse($content);
    }

    private function isNoMoreWork(string $content): bool
    {
        return preg_match('/^(tidak|tidak ada|nggak ada|ga ada|gak ada|sudah|sudah semua|udah semua|itu saja|cukup)\.?$/iu', trim($content)) === 1;
    }

    private function isConfirmation(string $content): bool
    {
        return preg_match('/^(sudah|iya|ya|yes|benar|betul|sesuai|setuju|oke|ok)\.?$/iu', trim($content)) === 1;
    }

    private function isPriorityResponse(string $content): bool
    {
        return preg_match('/\b(prioritas|prioritas utama)\b/iu', trim($content)) === 1;
    }

    private function isPriorityQuestion(string $content): bool
    {
        return preg_match('/\b(prioritas|prioritas utama)\b.*\b(saat ini|sekarang)\b/iu', $content) === 1;
    }

    /**
     * Generate smart conversation title from user's first message
     */
    private function generateConversationTitle(string $message): string
    {
        $message = trim($message);
        
        // Limit to reasonable length
        $maxLength = 50;
        
        // Remove common prefixes
        $message = preg_replace('/^(hai|halo|hi|hello|saya|aku|mau|ingin|perlu)\s+/iu', '', $message);
        
        // If message contains specific keywords, extract them
        if (preg_match('/(?:proyek|projek|project)\s+([\p{L}\d][\p{L}\d ._-]{0,40})/iu', $message, $matches)) {
            return 'Proyek: ' . $this->truncateTitle($matches[1], $maxLength - 9);
        }
        
        if (preg_match('/(?:membuat|mengembangkan|membangun|coding|develop)\s+([\p{L}\d][\p{L}\d ._-]{0,40})/iu', $message, $matches)) {
            return $this->truncateTitle($matches[1], $maxLength);
        }
        
        // Extract first meaningful sentence or phrase
        if (mb_strlen($message) <= $maxLength) {
            return ucfirst($message);
        }
        
        return $this->truncateTitle($message, $maxLength);
    }

    /**
     * Truncate title intelligently at word boundary
     */
    private function truncateTitle(string $text, int $maxLength): string
    {
        $text = trim($text);
        
        if (mb_strlen($text) <= $maxLength) {
            return ucfirst($text);
        }
        
        // Try to cut at word boundary
        $truncated = mb_substr($text, 0, $maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');
        
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        
        return ucfirst(rtrim($truncated, '.,;:!?')) . '...';
    }

    public function getMessages(Conversation $conversation)
    {
        // Ensure user owns this conversation
        if ($conversation->user_id !== auth()->id()) {
            abort(403);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    public function destroy(Conversation $conversation)
    {
        // Ensure user owns this conversation
        if ($conversation->user_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus percakapan ini.');
        }

        // Delete all messages first
        $conversation->messages()->delete();

        // Delete conversation
        $conversation->delete();

        // Redirect to create new conversation (like going home)
        return redirect()->route('dashboard')->with('success', 'Percakapan berhasil dihapus.');
    }
}
