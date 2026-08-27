<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;
use App\Models\QuestionTemplate;
use App\Models\User;
use App\Models\WorkActivity;
use App\Services\Agent\AgentOrchestrator;
use App\Services\Agent\OptionGenerator;
use App\Services\WorkActivityService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected AgentOrchestrator $agent;
    protected OptionGenerator $optionGenerator;
    protected WorkActivityService $workActivityService;

    public function __construct(
        AgentOrchestrator $agent, 
        OptionGenerator $optionGenerator,
        WorkActivityService $workActivityService
    ) {
        $this->agent = $agent;
        $this->optionGenerator = $optionGenerator;
        $this->workActivityService = $workActivityService;
    }

    /** Jumlah riwayat yang dimuat per permintaan pada panel riwayat. */
    public const RIWAYAT_PER_HALAMAN = 50;

    /** Pesan pembuka agent — pilihan dari daftar proyek baku Planning. */
    public const PERTANYAAN_AWAL = 'Halo! Senang bertemu dengan Anda. Pilih proyek dari daftar Planning yang sedang Anda kerjakan hari ini:';

    public const PERTANYAAN_TANPA_PROYEK = 'Halo! Senang bertemu dengan Anda. Belum ada proyek baku di Planning. Minta admin menambahkan proyek dan assign task terlebih dahulu, lalu mulai chat lagi.';

    public const PROMPT_AWAL = 'Sapa user, minta pilih proyek baku dari Planning, lalu gali objektif as-is, harapan, deliverable, task sesuai planning, detail+progress, dan estimasi durasi.';

    /**
     * Metadata pesan pembuka: dropdown proyek baku + proyek yang dikerjakan user.
     */
    public static function metadataAwal(?User $user = null): array
    {
        $opsi = $user ? self::opsiProyekPlanning($user) : [];

        return [
            'system_prompt' => self::PROMPT_AWAL,
            'has_options' => $opsi !== [],
            'options' => $opsi,
            'question_type' => 'project_list',
            'allow_custom' => false,
        ];
    }

    /**
     * Proyek yang di-assign ke user dulu, lalu sisa proyek baku Planning.
     *
     * @return list<string>
     */
    public static function opsiProyekPlanning(User $user): array
    {
        $milikSaya = Project::whereHas('tasks', fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('nama')
            ->pluck('nama')
            ->all();

        $semua = Project::orderBy('nama')->pluck('nama')->all();

        return array_values(array_unique(array_merge($milikSaya, $semua)));
    }

    /**
     * Task Planning user untuk satu proyek (belum done).
     *
     * @return list<string>
     */
    public static function opsiTaskPlanning(User $user, string $projectName): array
    {
        $project = Project::where('nama', $projectName)->first();
        if ($project === null) {
            return [];
        }

        return Task::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->where('status', '!=', Task::STATUS_DONE)
            ->orderByRaw("CASE prioritas WHEN 'Tinggi' THEN 1 WHEN 'Sedang' THEN 2 ELSE 3 END")
            ->orderBy('judul')
            ->pluck('judul')
            ->all();
    }

    /**
     * Buat percakapan baru berikut pesan pembukanya.
     */
    public static function percakapanBaru(User $user): Conversation
    {
        $meta = self::metadataAwal($user);
        $content = ($meta['options'] ?? []) === []
            ? self::PERTANYAAN_TANPA_PROYEK
            : self::PERTANYAAN_AWAL;

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
            'content' => $content,
            'step_number' => 1,
            'metadata' => $meta,
        ]);

        return $conversation;
    }

    /**
     * Tombol "Chat Baru": tampilkan layar percakapan kosong tanpa menyentuh database.
     * Barisnya baru dibuat saat user mengirim jawaban pertama (lihat mulai()).
     */
    public function baru()
    {
        $user = request()->user();
        $metaAwal = self::metadataAwal($user);
        $pertanyaanAwal = ($metaAwal['options'] ?? []) === []
            ? self::PERTANYAAN_TANPA_PROYEK
            : self::PERTANYAAN_AWAL;

        return view('chat', [
            'conversation' => null,
            'metaAwal' => $metaAwal,
            'pertanyaanAwal' => $pertanyaanAwal,
        ]);
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
        $baris = Conversation::with(['pesanTerakhirUser', 'pesanDetail'])
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
            $meta = self::metadataAwal(auth()->user());
            $content = ($meta['options'] ?? []) === []
                ? self::PERTANYAAN_TANPA_PROYEK
                : self::PERTANYAAN_AWAL;

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => $content,
                'step_number' => 1,
                'metadata' => $meta,
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
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $message = trim($validated['message']);

        $previousAiMessage = $conversation->messages()
            ->where('sender_type', 'ai')
            ->orderByDesc('id')
            ->first();

        // Validate user answer BEFORE saving the message
        $validationMessage = $this->validateChatAnswer($message, $previousAiMessage);
        if ($validationMessage !== null) {
            // Save the invalid message to keep it in history
            $userMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'user',
                'content' => $message,
                'step_number' => $conversation->current_step,
            ]);

            // Create AI validation message
            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => $validationMessage,
                'step_number' => $conversation->current_step,
                'metadata' => [
                    'is_validation_error' => true,
                    'has_options' => (bool) ($previousAiMessage?->metadata['has_options'] ?? false),
                    'options' => $previousAiMessage?->metadata['options'] ?? [],
                    'question_type' => $previousAiMessage?->metadata['question_type'] ?? 'text_input',
                    'expects' => $previousAiMessage?->metadata['expects'] ?? null,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jawaban perlu diperbaiki.',
                'validation_error' => true,
                'ai_response' => [
                    'content' => $validationMessage,
                    'type' => 'validation_error',
                    'has_options' => (bool) ($previousAiMessage?->metadata['has_options'] ?? false),
                    'options' => $previousAiMessage?->metadata['options'] ?? [],
                    'question_type' => $previousAiMessage?->metadata['question_type'] ?? 'text_input',
                    'allow_custom' => $previousAiMessage?->metadata['allow_custom'] ?? true,
                ],
            ]);
        }

        // Save user message only if validation passed
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'content' => $message,
            'step_number' => $conversation->current_step,
        ]);

        // ✨ NEW: Process user answer dengan NLU dan update WorkActivity
        $nluResult = $this->workActivityService->processUserAnswer(
            $conversation,
            $userMessage,
            $previousAiMessage
        );

        // Judul percakapan = nama proyek dari Planning saat dipilih.
        if ($conversation->current_step === 1 && $conversation->messages()->where('sender_type', 'user')->count() === 1) {
            if (! $this->labelPilihanAwal($message)) {
                $conversation->update(['title' => $this->generateConversationTitle($message)]);
            }
        } elseif (in_array(trim((string) $conversation->title), ['Percakapan Baru', 'New Chat'], true)
            && $this->isValidProjectName($message)) {
            $conversation->update(['title' => $this->generateConversationTitle($message)]);
        }

        $prevType = $previousAiMessage?->metadata['question_type'] ?? null;

        // Pilih proyek dari daftar Planning (pertanyaan pembuka atau daftar ulang)
        if (in_array($prevType, ['project_list', 'project_selection'], true)) {
            return $this->handleSelectedPreviousProject($conversation, $message);
        }

        // Proyek lain: tampilkan lagi daftar proyek baku
        if ($prevType === 'other_project' && preg_match('/^(ya|iya)/iu', trim($message)) === 1) {
            return $this->tampilkanDaftarProyek($conversation);
        }

        // Kompatibilitas sesi lama yang masih minta nama proyek bebas
        if (($previousAiMessage?->metadata['expects'] ?? null) === 'project_name') {
            return $this->handleProjectName($conversation, $message);
        }

        $projectName = $this->extractProjectNameFromHistory($conversation);
        $plannedTasks = $projectName
            ? self::opsiTaskPlanning(auth()->user(), $projectName)
            : [];

        // Build context for agent
        $context = [
            'user_id' => auth()->id(),
            'conversation_id' => $conversation->id,
            'department_id' => $conversation->department_id,
            'department_code' => $conversation->department->code,
            'current_step' => $conversation->current_step,
            'conversation_history' => $this->conversationHistory($conversation, $userMessage->id),
            'project_name' => $projectName,
            'objective' => $this->extractAnswerForType($conversation, 'objective'),
            'expectation' => $this->extractAnswerForType($conversation, 'expectation'),
            'deliverable' => $this->extractAnswerForType($conversation, 'deliverable'),
            'current_task' => $this->extractAnswerForType($conversation, 'current_task'),
            'planned_tasks' => $plannedTasks,
            'planned_projects' => self::opsiProyekPlanning(auth()->user()),
        ];

        // Process with Agent Orchestrator
        $agentResponse = $this->agent->process($validated['message'], $context);

        if ($agentResponse['success']) {
            $responseContent = $agentResponse['response']['content'];

            // Parse response to check if it has options
            $parsedResponse = $this->optionGenerator->parseResponse($responseContent);
            $parsedResponse = $this->ensureStructuredOptions($parsedResponse, $responseContent, [
                'department_code' => $conversation->department->code,
                'project_name' => $projectName,
                'objective' => $context['objective'],
                'current_task' => $context['current_task'],
                'planned_tasks' => $plannedTasks,
                'user_input' => $message,
                'conversation_id' => $conversation->id,
            ]);
            $parsedResponse = $this->alignOptionsWithPlanning($parsedResponse, $plannedTasks);

            if ($this->isNoMoreWork($message)) {
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
                $parsedResponse = $this->optionGenerator->parseResponse($responseContent);
                $parsedResponse = $this->ensureStructuredOptions($parsedResponse, $responseContent, [
                    'department_code' => $conversation->department->code,
                    'conversation_id' => $conversation->id,
                ]);
            }

            // Create AI message
            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => $parsedResponse['message'],
                'step_number' => $conversation->current_step,
                'metadata' => [
                    'tools_used' => $agentResponse['tools_used'] ?? [],
                    'confidence' => $agentResponse['response']['confidence'] ?? 0,
                    'system_prompt_version' => $agentResponse['metadata']['system_prompt_version'] ?? null,
                    'has_options' => $parsedResponse['has_options'],
                    'options' => $parsedResponse['options'],
                    'question_type' => $parsedResponse['type'],
                    'allow_custom' => $parsedResponse['allow_custom'] ?? true,
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
                $this->simpanHasilPercakapan($conversation, $dailyActivity['projects']);

                $responseContent = 'Baik, catatan aktivitas hari ini sudah lengkap dan disimpan. Pekerjaan Anda: '
                    . collect($dailyActivity['projects'])
                        ->pluck('summary')
                        ->filter()
                        ->implode(' | ');
                $aiMessage->update(['content' => $responseContent]);
            }

            // Saat percakapan ditutup, yang dikirim ke klien harus ringkasan yang
            // tersimpan — bukan hasil parse pertanyaan berikutnya — dan tanpa pilihan.
            $selesai = ! $conversation->fresh()->isActive();

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'selesai' => $selesai,
                'ai_response' => [
                    'content' => $selesai ? $responseContent : $parsedResponse['message'],
                    'type' => $agentResponse['response']['type'],
                    'confidence' => $agentResponse['response']['confidence'],
                    'has_options' => $selesai ? false : $parsedResponse['has_options'],
                    'options' => $selesai ? [] : $parsedResponse['options'],
                    'question_type' => $parsedResponse['type'],
                    'allow_custom' => $selesai ? false : ($parsedResponse['allow_custom'] ?? true),
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

    private function validateChatAnswer(string $message, ?Message $previousAiMessage): ?string
    {
        $options = $previousAiMessage?->metadata['options'] ?? [];
        $questionType = $previousAiMessage?->metadata['question_type'] ?? null;
        $allowCustom = $previousAiMessage?->metadata['allow_custom'] ?? true;
        $expects = $previousAiMessage?->metadata['expects'] ?? null;
        $previousContent = (string) ($previousAiMessage?->content ?? '');

        // Skip validasi jika pertanyaan sebelumnya adalah konfirmasi ringkasan
        $isConfirmationQuestion = $questionType === 'confirmation'
            || preg_match('/(catatan ini sudah sesuai|informasi ini sudah tepat|simpan sebagai catatan|apakah catatan ini sudah sesuai)/iu', $previousContent) === 1;

        if ($isConfirmationQuestion) {
            // Tidak perlu validasi untuk jawaban konfirmasi
            return null;
        }

        // Objektif as-is = input bebas; cek dulu sebelum aturan Planning ketat
        $isObjectiveAsIs = $expects === 'objective_text'
            || $questionType === 'objective'
            || preg_match('/objektif as-is|sedang Anda kerjakan pada proyek/iu', $previousContent) === 1;

        if ($isObjectiveAsIs) {
            if (! $this->isValidFreeTextAnswer($message, 'objective')) {
                return 'Maaf, saya memerlukan informasi yang lebih jelas dan spesifik tentang objektif pekerjaan. Apa yang sedang Anda kerjakan pada proyek ini?';
            }

            return null;
        }

        // ✨ NEW: Expectation juga input bebas (tidak wajib pilih dari opsi)
        $isExpectationFreeText = $questionType === 'expectation'
            || preg_match('/apa yang diharapkan dari anda|diharapkan dari anda pada pekerjaan/iu', $previousContent) === 1;

        if ($isExpectationFreeText) {
            if (! $this->isValidFreeTextAnswer($message, 'expectation')) {
                return 'Maaf, saya memerlukan informasi yang lebih jelas dan spesifik tentang harapan/ekspektasi. Apa ekspektasi Anda terhadap pekerjaan ini?';
            }

            return null;
        }

        // ✨ NEW: Deliverable juga input bebas (tidak wajib pilih dari opsi)
        $isDeliverableFreeText = $questionType === 'deliverable'
            || preg_match('/hasil kerja.*deliverable|apa hasil kerja yang harus dihasilkan/iu', $previousContent) === 1;

        if ($isDeliverableFreeText) {
            if (! $this->isValidFreeTextAnswer($message, 'deliverable')) {
                return 'Maaf, saya memerlukan informasi yang lebih jelas dan spesifik tentang hasil kerja (deliverable). Apa hasil kerja yang harus dihasilkan dari pekerjaan ini?';
            }

            return null;
        }

        // ✨ NEW: Task Detail juga input bebas (tidak wajib pilih dari opsi)
        $isTaskDetailFreeText = $questionType === 'task_detail'
            || preg_match('/detail yang dilakukan apa/iu', $previousContent) === 1;

        if ($isTaskDetailFreeText) {
            if (! $this->isValidFreeTextAnswer($message, 'task_detail')) {
                return 'Maaf, saya memerlukan informasi yang lebih jelas dan spesifik tentang detail pekerjaan. Detail yang dilakukan apa?';
            }

            return null;
        }

        // ✨ NEW: Progress juga input bebas (tidak wajib pilih dari opsi)
        $isProgressFreeText = $questionType === 'progress'
            || preg_match('/progressnya sampai mana/iu', $previousContent) === 1;

        if ($isProgressFreeText) {
            if (! $this->isValidFreeTextAnswer($message, 'progress')) {
                return 'Maaf, saya memerlukan informasi yang lebih jelas dan spesifik tentang progress pekerjaan. Progressnya sampai mana?';
            }

            return null;
        }

        // ✨ NEW: Estimation juga input bebas (tidak wajib pilih dari opsi)
        $isEstimationFreeText = $questionType === 'estimation'
            || preg_match('/berapa lama pengerjaannya/iu', $previousContent) === 1;

        if ($isEstimationFreeText) {
            if (! $this->isValidFreeTextAnswer($message, 'estimation')) {
                return 'Maaf, saya memerlukan informasi yang lebih jelas dan spesifik tentang estimasi waktu pengerjaan. Berapa lama pengerjaannya?';
            }

            return null;
        }

        foreach ($options as $option) {
            if (strcasecmp($message, trim((string) $option)) === 0) {
                return null;
            }
        }

        // Proyek/task Planning: wajib pilih dari daftar
        $ketat = in_array($questionType, ['project_list', 'project_selection'], true)
            || ($questionType === 'current_task' && $allowCustom === false);

        if ($ketat && $options !== []) {
            return 'Maaf, silakan pilih dari daftar yang tersedia agar sesuai dengan Planning.';
        }

        // Strict validation for project name (sesi lama)
        if ($expects === 'project_name' ||
            ($questionType === 'text_input' && stripos($previousContent, 'nama proyek') !== false)) {
            if (! $this->isValidProjectName($message)) {
                return 'Maaf, saya memerlukan informasi yang lebih jelas dan spesifik. Apa nama proyek Anda?';
            }

            return null;
        }

        // Validation for "Something else" / jawaban lain
        if (in_array('Something else', $options, true) && ! in_array($message, $options, true)) {
            if (! $this->isValidFreeTextAnswer($message, $questionType)) {
                $questionContext = $this->getQuestionContext($questionType ?? 'text');

                return "Maaf, saya memerlukan informasi yang lebih jelas dan spesifik tentang {$questionContext}. "
                    . ($previousAiMessage?->content ?? 'Silakan jelaskan dengan lebih detail.');
            }

            return null;
        }

        return null;
    }

    /**
     * Validate project name - must be meaningful and specific
     */
    private function isValidProjectName(string $name): bool
    {
        $name = trim($name);
        
        // Must have at least 3 characters
        if (mb_strlen($name) < 3) {
            return false;
        }

        // Must not be basic validation check first
        if (!$this->isMeaningfulAnswer($name)) {
            return false;
        }

        // Count words
        $words = preg_split('/\s+/', $name);
        $wordCount = count($words);
        
        // Single word validation
        if ($wordCount === 1) {
            $lowerName = mb_strtolower($name);
            
            // Reject generic single words
            $genericSingleWords = ['proyek', 'project', 'sistem', 'system', 'aplikasi', 'app', 'website', 'site', 'data', 'test', 'tes', 'coba'];
            if (in_array($lowerName, $genericSingleWords)) {
                return false;
            }
            
            // Check for repeated character patterns (more than 50% of word is repeated chars)
            // e.g., "gogo" (go-go), "aajja" (aa-jj), "aaabbb"
            $charCount = [];
            $chars = mb_str_split($lowerName);
            foreach ($chars as $char) {
                $charCount[$char] = ($charCount[$char] ?? 0) + 1;
            }
            
            // Check if any character appears too frequently (more than 40% of total)
            $totalChars = mb_strlen($lowerName);
            foreach ($charCount as $char => $count) {
                if ($count / $totalChars > 0.4) {
                    // Too much repetition - likely random typing
                    return false;
                }
            }
            
            // Check for repeating pairs/patterns: "gogo", "abab", "aajja"
            // Detect if word has repeating consecutive chars (aa, bb, cc, etc)
            if (preg_match('/(.)\1/', $lowerName)) {
                // Has double letters (aa, bb, etc)
                // This is OK for real words like "Mayora" (no doubles), "Google" (oo)
                // But reject if MOST of the word is doubled patterns
                $doubleCount = preg_match_all('/(.)\1/', $lowerName);
                if ($doubleCount / ($totalChars / 2) > 0.5) {
                    // More than 50% of potential pairs are doubles
                    return false;
                }
            }
            
            // Check for vowel/consonant ratio to detect random typing
            // Real words have balanced vowel-consonant ratio
            $vowels = preg_match_all('/[aeiouAEIOU]/', $name);
            $consonants = preg_match_all('/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]/', $name);
            $totalLetters = $vowels + $consonants;
            
            if ($totalLetters > 0) {
                $vowelRatio = $vowels / $totalLetters;
                // Real words typically have 30-50% vowels
                // Reject if too few vowels (< 15%) or too many (> 70%)
                if ($vowelRatio < 0.15 || $vowelRatio > 0.70) {
                    return false;
                }
            }
            
            // Check for alternating consonant patterns (common in random typing)
            // e.g., "ijbnv", "dfghj", "qwrty"
            if (preg_match('/[bcdfghjklmnpqrstvwxyz]{3,}/i', $lowerName)) {
                // Too many consecutive consonants - likely random
                return false;
            }
            
            // Accept if it's a known company/brand name pattern
            if (mb_strlen($name) >= 5 || preg_match('/[A-Z]/', $name) || preg_match('/\d/', $name)) {
                // But still reject keyboard patterns
                $keyboardPatterns = [
                    'qwerty', 'asdf', 'zxcv', 'qaz', 'wsx', 'edc', 'rfv', 'tgb', 'yhn', 'ujm',
                    'qwe', 'asd', 'zxc', 'rty', 'fgh', 'vbn', 'uio', 'jkl', 'bnm',
                    'dfg', 'cvb', 'ghj', 'erty', 'sdfg', 'xcvb', 'tyui', 'ghjk', 'vbnm'
                ];
                
                foreach ($keyboardPatterns as $pattern) {
                    if (str_contains($lowerName, $pattern)) {
                        return false;
                    }
                }
                
                return true; // Valid single word (e.g., "Mayora", "Tokopedia", "Dashboard123")
            }
            
            return false; // Too short single word without context
        }

        // Multi-word validation: count meaningful words (at least 3 characters)
        $meaningfulWords = array_filter($words, function($word) {
            return mb_strlen(trim($word)) >= 3;
        });

        // Must have at least 2 meaningful words
        if (count($meaningfulWords) < 2) {
            return false;
        }

        // Reject generic/vague combinations
        $genericWords = ['proyek', 'project', 'sistem', 'system', 'aplikasi', 'app', 'website', 'site', 'data', 'test', 'tes', 'coba', 'baru', 'new'];
        $lowerWords = array_map('mb_strtolower', $words);
        
        // If first word is generic and second word exists, check if second word is meaningful
        if (count($words) === 2 && in_array($lowerWords[0], $genericWords)) {
            $secondWord = $words[1];
            
            // Reject if second word is just a number or too short (< 3 chars)
            if (is_numeric($secondWord) || mb_strlen($secondWord) < 3) {
                return false;
            }
            
            // Check if second word is meaningful (not random typing)
            // Accept if it's a proper name/brand (has good vowel ratio, no keyboard patterns)
            $secondLower = mb_strtolower($secondWord);
            
            // Check for keyboard patterns in second word
            $keyboardPatterns = ['qwerty', 'asdf', 'zxcv', 'qaz', 'wsx', 'edc', 'rfv'];
            foreach ($keyboardPatterns as $pattern) {
                if (str_contains($secondLower, $pattern)) {
                    return false;
                }
            }
            
            // Check if it's an acronym (all uppercase, 3-6 chars) - accept immediately
            if (preg_match('/^[A-Z]{3,6}$/', $secondWord)) {
                return true; // Valid acronyms like "AKR", "BPJS", "IBM", "NASA"
            }
            
            // Check vowel ratio of second word
            $vowels = preg_match_all('/[aeiouAEIOU]/', $secondWord);
            $consonants = preg_match_all('/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]/', $secondWord);
            $totalLetters = $vowels + $consonants;
            
            if ($totalLetters > 0) {
                $vowelRatio = $vowels / $totalLetters;
                // Accept if vowel ratio is reasonable (20-60% is acceptable for names)
                // OR if it's 0-15% but at least 3 chars (could be acronym in mixed case)
                if (($vowelRatio >= 0.20 && $vowelRatio <= 0.60) || 
                    ($vowelRatio <= 0.15 && mb_strlen($secondWord) >= 3)) {
                    return true; // Valid combination like "Proyek Mayora", "Proyek BPJS", "Project Dashboard"
                }
            }
            
            // If vowel ratio check fails, still accept if word length is reasonable (5+)
            if (mb_strlen($secondWord) >= 5) {
                return true;
            }
            
            return false;
        }

        // Check for keyboard patterns in the whole name
        $lowerName = mb_strtolower($name);
        $keyboardPatterns = [
            'qwerty', 'asdf', 'zxcv', 'qaz', 'wsx', 'edc', 'rfv', 'tgb', 'yhn', 'ujm',
            'qwe', 'asd', 'zxc', 'rty', 'fgh', 'vbn', 'uio', 'jkl', 'bnm',
            'dfg', 'cvb', 'ghj', 'erty', 'sdfg', 'xcvb', 'tyui', 'ghjk', 'vbnm'
        ];
        
        foreach ($keyboardPatterns as $pattern) {
            if (str_contains($lowerName, $pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate free text answer for "Something else" / objektif as-is
     */
    private function isValidFreeTextAnswer(string $answer, ?string $questionType): bool
    {
        $answer = trim($answer);

        // Special handling untuk estimation, progress, dan task_detail - lebih fleksibel
        if (in_array($questionType, ['estimation', 'progress', 'task_detail'], true)) {
            // Minimal 2 karakter (untuk jawaban seperti "2 hari", "50%", "hampir selesai")
            if (mb_strlen($answer) < 2) {
                return false;
            }

            // Tolak jawaban generik yang terlalu pendek
            $shortGeneric = ['tes', 'test', 'coba', 'ok', 'oke', 'ya', 'tidak'];
            if (in_array(mb_strtolower($answer), $shortGeneric, true)) {
                return false;
            }

            // Tolak keyboard smash
            if (preg_match('/^(.)\1{4,}$/u', $answer) === 1) {
                return false;
            }

            $lower = mb_strtolower($answer);
            foreach (['qwerty', 'asdfgh', 'zxcvbn'] as $pattern) {
                if (str_contains($lower, $pattern)) {
                    return false;
                }
            }

            // Terima jawaban yang mengandung angka + kata (misal: "2 hari", "3 minggu", "50%", "75 persen")
            // Atau jawaban deskriptif minimal 3 karakter
            return mb_strlen($answer) >= 3;
        }

        // Validasi default untuk pertanyaan lainnya (objective, expectation, deliverable)
        if (mb_strlen($answer) < 5) {
            return false;
        }

        $shortGeneric = ['tes', 'test', 'coba', 'lain', 'other', 'etc', 'dll', 'lainnya', 'ya', 'tidak', 'ok', 'oke'];
        if (in_array(mb_strtolower($answer), $shortGeneric, true)) {
            return false;
        }

        // Tolak keyboard smash / spam karakter berulang saja
        if (preg_match('/^(.)\1{4,}$/u', $answer) === 1) {
            return false;
        }

        $lower = mb_strtolower($answer);
        foreach (['qwerty', 'asdfgh', 'zxcvbn'] as $pattern) {
            if (str_contains($lower, $pattern)) {
                return false;
            }
        }

        // Minimal 2 kata (Unicode-aware) ATAU >= 12 karakter bermakna
        $words = preg_split('/\s+/u', $answer, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $meaningfulWords = array_filter($words, fn ($w) => mb_strlen(preg_replace('/[^\p{L}\d]/u', '', $w) ?? '') >= 2);

        return count($meaningfulWords) >= 2 || mb_strlen($answer) >= 12;
    }

    /**
     * Get question context for error message
     */
    private function getQuestionContext(string $questionType): string
    {
        return match ($questionType) {
            'objective' => 'objektif pekerjaan',
            'expectation' => 'harapan dari Anda',
            'deliverable' => 'hasil kerja (deliverable)',
            'current_task' => 'task Planning yang dikerjakan',
            'task_detail' => 'detail dan progress',
            'progress' => 'progress pekerjaan',
            'task_challenge' => 'kendala yang dihadapi',
            'task_approach' => 'pendekatan task',
            'estimation' => 'estimasi waktu',
            default => 'jawaban Anda',
        };
    }

    private function isMeaningfulAnswer(string $message): bool
    {
        $message = trim($message);
        $letters = preg_replace('/[^\p{L}]/u', '', $message) ?? '';
        $alphanumeric = preg_replace('/[^\p{L}\d]/u', '', $message) ?? '';

        if (mb_strlen($alphanumeric) < 2 || mb_strlen($letters) < 2) {
            return false;
        }

        // Hanya tolak spam karakter berulang (aaa, xxx) — bukan kalimat panjang normal
        if (preg_match('/(.)\1{4,}/u', $message) === 1) {
            return false;
        }

        $lowerMessage = mb_strtolower($message);
        foreach (['qwerty', 'asdfgh', 'zxcvbn'] as $pattern) {
            if (str_contains($lowerMessage, $pattern)) {
                return false;
            }
        }

        // Rasio unik hanya untuk string PENDEK (deteksi "aaaa"/"ababab"),
        // jangan terapkan ke kalimat panjang — huruf vokal Indonesia selalu berulang.
        if (mb_strlen($letters) >= 6 && mb_strlen($letters) <= 24) {
            $uniqueLetters = count(array_unique(mb_str_split(mb_strtolower($letters))));
            if ($uniqueLetters / mb_strlen($letters) < 0.35) {
                return false;
            }
        }

        return true;
    }

    private function ensureStructuredOptions(array $parsedResponse, string $responseContent, array $context): array
    {
        $question = mb_strtolower($responseContent);
        
        // ✨ PRIORITY: Cek dulu apakah ini pertanyaan konfirmasi ringkasan
        $isConfirmationQuestion = preg_match(
            '/catatan ini sudah sesuai|informasi ini sudah tepat|simpan sebagai catatan|apakah catatan ini sudah sesuai/iu',
            $question
        ) === 1
            || ($parsedResponse['type'] ?? null) === 'confirmation';

        if ($isConfirmationQuestion) {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'confirmation',
                'allow_custom' => true,
            ];
        }

        $isObjectiveAsIs = preg_match(
            '/objektif as-is|sedang Anda kerjakan pada proyek|apa yang sedang Anda kerjakan/iu',
            $question
        ) === 1
            || ($parsedResponse['type'] ?? null) === 'objective';

        // Objektif as-is selalu input bebas — buang opsi jika AI mengirimkannya
        if ($isObjectiveAsIs) {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'objective',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Expectation juga selalu input bebas — buang opsi jika AI mengirimkannya
        $isExpectationFreeText = preg_match(
            '/apa yang diharapkan dari anda|diharapkan dari anda pada pekerjaan/iu',
            $question
        ) === 1
            || ($parsedResponse['type'] ?? null) === 'expectation';

        if ($isExpectationFreeText) {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'expectation',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Deliverable juga selalu input bebas — buang opsi jika AI mengirimkannya
        $isDeliverableFreeText = preg_match(
            '/hasil kerja.*deliverable|apa hasil kerja yang harus dihasilkan/iu',
            $question
        ) === 1
            || ($parsedResponse['type'] ?? null) === 'deliverable';

        if ($isDeliverableFreeText) {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'deliverable',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Task Detail juga selalu input bebas — buang opsi jika AI mengirimkannya
        $isTaskDetailFreeText = preg_match(
            '/detail yang dilakukan apa(?!.*progress)/iu',
            $question
        ) === 1
            || ($parsedResponse['type'] ?? null) === 'task_detail';

        if ($isTaskDetailFreeText) {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'task_detail',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Progress juga selalu input bebas — buang opsi jika AI mengirimkannya
        $isProgressFreeText = preg_match(
            '/progressnya sampai mana/iu',
            $question
        ) === 1
            || ($parsedResponse['type'] ?? null) === 'progress';

        if ($isProgressFreeText) {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'progress',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Estimation juga selalu input bebas — buang opsi jika AI mengirimkannya
        $isEstimationFreeText = preg_match(
            '/berapa lama pengerjaannya/iu',
            $question
        ) === 1
            || ($parsedResponse['type'] ?? null) === 'estimation';

        if ($isEstimationFreeText) {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'estimation',
                'allow_custom' => true,
            ];
        }

        if ($parsedResponse['has_options'] && ! empty($parsedResponse['options'])) {
            $parsedResponse['options'] = array_values(array_filter(
                $parsedResponse['options'],
                fn ($opt) => strcasecmp(trim($opt), 'Something else') !== 0
            ));

            return $parsedResponse;
        }

        $question = mb_strtolower($responseContent);
        $type = match (true) {
            preg_match('/fokus utama|detail.*progress|progress.*sampai|detail yang dilakukan|detail task/iu', $question) === 1 => 'task_detail',
            preg_match('/progress|progres|sampai mana|berapa persen/iu', $question) === 1 => 'progress',
            preg_match('/hasil kerja.*deliverable|deliverable|hasil kerja yang harus|output yang harus/iu', $question) === 1 => 'deliverable',
            preg_match('/kendala|hambatan|masalah yang dihadapi/iu', $question) === 1 => 'task_challenge',
            preg_match('/sudah sesuai|sudah benar|konfirmasi|simpan sebagai catatan/iu', $question) === 1 => 'confirmation',
            preg_match('/prioritas.*pekerjaan|task.*prioritas|proyek.*prioritas/iu', $question) === 1 => 'priority',
            preg_match('/estimasi.*waktu|berapa.*waktu|durasi|berapa lama/iu', $question) === 1 => 'estimation',
            preg_match('/proyek lain/iu', $question) === 1 => 'other_project',
            // Objektif as-is = input bebas, jangan inject opsi
            preg_match('/objektif as-is|sedang Anda kerjakan pada proyek|apa yang sedang Anda kerjakan/iu', $question) === 1 => 'objective_text',
            preg_match('/harapan|ekspektasi|diharapkan dari|expected|hasil yang diinginkan/iu', $question) === 1 => 'expectation',
            preg_match('/task.*kerjakan|pekerjaan.*kerjakan|task planning/iu', $question) === 1 => 'current_task',
            default => null,
        };

        if ($type === null) {
            return $parsedResponse;
        }

        // Objektif as-is: paksa tanpa opsi (input teks bebas)
        if ($type === 'objective_text') {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'objective',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Expectation: paksa tanpa opsi (input teks bebas)
        if ($type === 'expectation') {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'expectation',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Deliverable: paksa tanpa opsi (input teks bebas)
        if ($type === 'deliverable') {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'deliverable',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Task Detail: paksa tanpa opsi (input teks bebas)
        if ($type === 'task_detail') {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'task_detail',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Progress: paksa tanpa opsi (input teks bebas)
        if ($type === 'progress') {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'progress',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Estimation: paksa tanpa opsi (input teks bebas)
        if ($type === 'estimation') {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'estimation',
                'allow_custom' => true,
            ];
        }

        // ✨ NEW: Confirmation: tidak perlu validasi ketat, terima jawaban apa saja
        if ($type === 'confirmation') {
            return [
                'has_options' => false,
                'message' => $parsedResponse['message'] ?? $responseContent,
                'options' => [],
                'type' => 'confirmation',
                'allow_custom' => true,
            ];
        }

        $options = $this->optionGenerator->generateOptions($type, $context);

        // Task wajib dari Planning jika tersedia
        if ($type === 'current_task' && ! empty($context['planned_tasks'])) {
            $options = $context['planned_tasks'];
        }

        // ✨ PENTING: Expectation, Objective, dan Deliverable TIDAK perlu opsi (input bebas)
        if (in_array($type, ['expectation', 'objective', 'objective_text', 'deliverable'], true)) {
            $options = [];
        }

        $options = array_values(array_filter(
            $options,
            fn ($opt) => strcasecmp(trim($opt), 'Something else') !== 0
        ));

        return [
            'has_options' => ! empty($options),
            'message' => $parsedResponse['message'] ?? $responseContent,
            'options' => $options,
            'type' => $type,
            'allow_custom' => ! in_array($type, ['project_list', 'current_task'], true)
                || empty($context['planned_tasks']),
        ];
    }

    /**
     * Pastikan opsi task selalu dari Planning jika daftar tersedia.
     */
    private function alignOptionsWithPlanning(array $parsedResponse, array $plannedTasks): array
    {
        $type = $parsedResponse['type'] ?? null;

        if ($type === 'current_task' && $plannedTasks !== []) {
            $parsedResponse['options'] = $plannedTasks;
            $parsedResponse['has_options'] = true;
            $parsedResponse['allow_custom'] = false;
        }

        if (in_array($type, ['project_list', 'project_selection'], true)) {
            $parsedResponse['allow_custom'] = false;
        }

        return $parsedResponse;
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
        $storedProjectName = trim((string) ($conversation->metadata['project_name'] ?? ''));
        $currentProject = $storedProjectName !== '' ? $storedProjectName : null;
        $currentMessages = [];

        foreach ($userMessages as $content) {
            if ($storedProjectName !== '' && strcasecmp(trim($content), $storedProjectName) === 0) {
                continue;
            }

            if (strcasecmp(trim($content), 'proyek baru') === 0) {
                continue;
            }

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

    /**
     * Simpan hasil percakapan menjadi proyek dan tugas.
     * Tabel lama `pekerjaan` sudah tidak dipakai; satu-satunya penyimpanan
     * sekarang adalah Project + Task, yang juga menjadi sumber halaman
     * Pekerjaan Saya dan seluruh halaman admin.
     */
    private function simpanHasilPercakapan(Conversation $conversation, array $projectActivities): void
    {
        foreach ($projectActivities as $activity) {
            $projectName = $activity['project_company'] ?? 'Tidak disebutkan';
            $workDescription = $activity['work_description'] ?? $activity['summary'];

            $this->simpanKeTugas($conversation, $projectName, $workDescription);
        }
    }

    /**
     * Hasil percakapan dicatat ke Task Planning yang sudah di-assign.
     * Jika ada task Planning untuk user di proyek ini, update task tersebut;
     * jika tidak, buat task baru di proyek baku yang dipilih.
     */
    private function simpanKeTugas(Conversation $conversation, string $projectName, string $workDescription): void
    {
        $user = $conversation->user;

        $generik = ['baru', 'new', 'sebelumnya', 'proyek', 'project', 'tidak disebutkan'];
        if (mb_strlen(trim($projectName)) < 3 || in_array(mb_strtolower(trim($projectName)), $generik, true)) {
            $judulPercakapan = trim((string) $conversation->title);
            $projectName = ($judulPercakapan !== '' && ! in_array($judulPercakapan, ['Percakapan Baru', 'New Chat'], true))
                ? preg_replace('/^Proyek:\s*/i', '', $judulPercakapan)
                : 'Tanpa Proyek';
        }

        $project = Project::firstOrCreate(
            ['nama' => $projectName],
            [
                'mulai' => now()->startOfMonth(),
                'selesai' => now()->addMonth()->endOfMonth(),
                'created_by' => $user->id,
            ]
        );

        $objektif = $this->extractAnswerForType($conversation, 'objective');
        $harapan = $this->extractAnswerForType($conversation, 'expectation');
        $deliverable = $this->extractAnswerForType($conversation, 'deliverable');
        $detail = $this->extractAnswerForType($conversation, 'task_detail');
        $progress = $this->extractAnswerForType($conversation, 'progress');
        $estimasi = $this->extractAnswerForType($conversation, 'estimation');

        // Format deskripsi dengan semua informasi dari chat
        $deskripsiParts = [];
        
        if ($objektif) {
            $deskripsiParts[] = "Objektif: {$objektif}";
        }
        
        if ($harapan) {
            $deskripsiParts[] = "Harapan: {$harapan}";
        }
        
        if ($deliverable) {
            $deskripsiParts[] = "Hasil kerja (deliverable): {$deliverable}";
        }
        
        if ($detail) {
            $deskripsiParts[] = "Detail: {$detail}";
        }
        
        if ($progress) {
            $deskripsiParts[] = "Progress: {$progress}";
        }
        
        if ($estimasi) {
            $deskripsiParts[] = "Estimasi: {$estimasi}";
        }
        
        // Jika tidak ada informasi detail, gunakan work description
        if (empty($deskripsiParts)) {
            $deskripsi = $workDescription;
        } else {
            $deskripsi = implode("\n\n", $deskripsiParts);
        }

        // Log untuk debugging
        \Log::info('ChatController::simpanKeTugas', [
            'project_name' => $projectName,
            'objektif' => $objektif,
            'harapan' => $harapan,
            'deliverable' => $deliverable,
            'detail' => $detail,
            'progress' => $progress,
            'estimasi' => $estimasi,
            'deskripsi_final' => $deskripsi,
        ]);

        // Prioritas 1: Cari task Planning yang sudah di-assign admin untuk user di proyek ini
        // (task dengan status To Do atau In Progress yang belum selesai)
        $plannedTask = Task::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->whereIn('status', [Task::STATUS_TO_DO, Task::STATUS_IN_PROGRESS])
            ->orderBy('created_at', 'asc') // Ambil task paling lama (yang pertama di-assign)
            ->first();

        if ($plannedTask) {
            // Update task yang sudah ada dari admin
            $status = Task::STATUS_IN_PROGRESS;
            if ($progress && preg_match('/100%|selesai/iu', $progress)) {
                $status = Task::STATUS_DONE;
            }

            $progressPercentage = Task::extractProgressPercentage($progress);

            $plannedTask->update([
                'deskripsi' => $deskripsi,
                'objektif' => $objektif,
                'harapan' => $harapan,
                'deliverable' => $deliverable,
                'detail' => $detail,
                'progress_text' => $progress,
                'progress_percentage' => $progressPercentage,
                'estimasi' => $estimasi,
                'status' => $status,
                'selesai_pada' => $status === Task::STATUS_DONE ? now() : $plannedTask->selesai_pada,
            ]);

            return;
        }

        // Prioritas 2: Jika tidak ada task Planning, buat task baru
        $judul = \Illuminate\Support\Str::limit(
            trim($workDescription) ?: $projectName,
            120,
            ''
        );

        $sudahAda = Task::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('judul', $judul)
            ->exists();

        if ($sudahAda) {
            return;
        }

        $progressPercentage = Task::extractProgressPercentage($progress);

        Task::create([
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'objektif' => $objektif,
            'harapan' => $harapan,
            'deliverable' => $deliverable,
            'detail' => $detail,
            'progress_text' => $progress,
            'progress_percentage' => $progressPercentage,
            'estimasi' => $estimasi,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'created_by' => $user->id,
            'status' => Task::STATUS_IN_PROGRESS,
            'prioritas' => 'Sedang',
            'mulai' => now()->toDateString(),
            'selesai' => now()->addWeek()->toDateString(),
        ]);
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
            $content = rtrim($content) . "\n\nProyek: {$project}\nObjektif:\nHarapan:\nHasil kerja (deliverable):\nTask:\nDetail/Progress:\nEstimasi:";
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
    /** Jawaban langkah pertama yang berupa label pilihan lama, bukan nama proyek. */
    private function labelPilihanAwal(?string $message): bool
    {
        $t = mb_strtolower(trim((string) $message));

        return in_array($t, ['proyek baru', 'lanjut proyek sebelumnya', 'proyek sebelumnya'], true);
    }

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

    /**
     * Extract project name from conversation history
     */
    private function extractProjectNameFromHistory(Conversation $conversation): ?string
    {
        $storedProjectName = trim((string) ($conversation->metadata['project_name'] ?? ''));
        if ($storedProjectName !== '') {
            return $storedProjectName;
        }

        $messages = $conversation->messages()
            ->where('sender_type', 'user')
            ->orderBy('created_at', 'asc')
            ->pluck('content')
            ->all();

        return $this->extractProjectFromMessages($messages);
    }

    /**
     * Extract a specific field from conversation history (like objective)
     */
    private function extractFieldFromHistory(Conversation $conversation, string $field): ?string
    {
        return $this->extractAnswerForType($conversation, $field);
    }

    /**
     * Ambil jawaban user yang mengikuti pertanyaan AI dengan question_type tertentu.
     */
    private function extractAnswerForType(Conversation $conversation, string $questionType): ?string
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();

        $jawaban = null;
        for ($i = 0; $i < $messages->count() - 1; $i++) {
            $ai = $messages[$i];
            $user = $messages[$i + 1];
            if ($ai->sender_type !== 'ai' || $user->sender_type !== 'user') {
                continue;
            }
            if (($ai->metadata['question_type'] ?? null) === $questionType) {
                $jawaban = trim((string) $user->content);
            }
        }

        return $jawaban !== null && $jawaban !== '' ? $jawaban : null;
    }

    /**
     * Tampilkan ulang dropdown proyek baku Planning.
     */
    private function tampilkanDaftarProyek(Conversation $conversation): \Illuminate\Http\JsonResponse
    {
        $opsi = self::opsiProyekPlanning(auth()->user());

        if ($opsi === []) {
            $responseContent = 'Belum ada proyek baku di Planning. Minta admin menambahkan proyek terlebih dahulu.';
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => $responseContent,
                'step_number' => $conversation->current_step,
                'metadata' => [
                    'has_options' => false,
                    'question_type' => 'text_input',
                    'allow_custom' => false,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim',
                'ai_response' => [
                    'content' => $responseContent,
                    'type' => 'text',
                    'has_options' => false,
                    'options' => [],
                    'question_type' => 'text_input',
                    'allow_custom' => false,
                ],
            ]);
        }

        $responseContent = 'Baik. Pilih proyek Planning berikutnya yang sedang Anda kerjakan:';

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => $responseContent,
            'step_number' => $conversation->current_step,
            'metadata' => [
                'has_options' => true,
                'options' => $opsi,
                'question_type' => 'project_list',
                'allow_custom' => false,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'ai_response' => [
                'content' => $responseContent,
                'type' => 'question',
                'has_options' => true,
                'options' => $opsi,
                'question_type' => 'project_list',
                'allow_custom' => false,
            ],
        ]);
    }

    /**
     * Handle pemilihan proyek dari daftar Planning.
     */
    private function handleSelectedPreviousProject(Conversation $conversation, string $projectName): \Illuminate\Http\JsonResponse
    {
        $projectName = trim($projectName);
        $user = auth()->user();

        $metadata = $conversation->metadata ?? [];
        $metadata['project_name'] = $projectName;
        $conversation->update([
            'metadata' => $metadata,
            'title' => $this->generateConversationTitle($projectName),
        ]);

        $plannedTasks = self::opsiTaskPlanning($user, $projectName);

        // Objektif as-is: input bebas (bukan pilihan opsi)
        $responseContent = "Baik, proyek {$projectName}. Apa yang sedang Anda kerjakan pada proyek ini? (objektif as-is)";

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => $responseContent,
            'step_number' => $conversation->current_step,
            'metadata' => [
                'has_options' => false,
                'options' => [],
                'question_type' => 'objective',
                'expects' => 'objective_text',
                'project_name' => $projectName,
                'planned_tasks' => $plannedTasks,
                'allow_custom' => true,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'ai_response' => [
                'content' => $responseContent,
                'type' => 'question',
                'has_options' => false,
                'options' => [],
                'question_type' => 'objective',
                'allow_custom' => true,
            ],
        ]);
    }

    /**
     * Kompatibilitas sesi lama: input nama proyek bebas.
     */
    private function handleProjectName(Conversation $conversation, string $projectName): \Illuminate\Http\JsonResponse
    {
        return $this->handleSelectedPreviousProject($conversation, $projectName);
    }
}
