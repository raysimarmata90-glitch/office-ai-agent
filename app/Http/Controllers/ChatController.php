<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Department;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;
use App\Models\QuestionTemplate;
use App\Models\User;
use App\Services\Agent\AgentOrchestrator;
use App\Services\Agent\OptionGenerator;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected AgentOrchestrator $agent;
    protected OptionGenerator $optionGenerator;

    public function __construct(AgentOrchestrator $agent, OptionGenerator $optionGenerator)
    {
        $this->agent = $agent;
        $this->optionGenerator = $optionGenerator;
    }

    /** Jumlah riwayat yang dimuat per permintaan pada panel riwayat. */
    public const RIWAYAT_PER_HALAMAN = 50;

    /** Pesan pembuka agent — pertanyaan pertama selalu menanyakan proyek yang dikerjakan. */
    public const PERTANYAAN_AWAL = 'Halo! Senang bertemu dengan Anda. Apa proyek yang sedang Anda kerjakan hari ini?';

    public const PROMPT_AWAL = 'Sapa user dengan hangat, lalu gali proyek, objektif, harapan, task, dan estimasi durasi pengerjaan.';

    /**
     * Metadata pesan pembuka: pilihan cepat langkah pertama dikirim dari server
     * (lihat OptionGenerator) supaya klien tidak perlu menebak sendiri.
     */
    public static function metadataAwal(): array
    {
        return [
            'system_prompt' => self::PROMPT_AWAL,
            'has_options' => true,
            'options' => ['Proyek Baru', 'Lanjut Proyek Sebelumnya'],
            'question_type' => 'project_selection',
        ];
    }

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
            'metadata' => self::metadataAwal(),
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
            // Create first AI message with options
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => self::PERTANYAAN_AWAL,
                'step_number' => 1,
                'metadata' => self::metadataAwal(),
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
            ->latest()
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

        // Update conversation title with first user message (smart title)
        if ($conversation->current_step === 1 && $conversation->messages()->where('sender_type', 'user')->count() === 1) {
            // "Proyek Baru"/"Lanjut Proyek Sebelumnya" hanya label pilihan langkah
            // pertama, bukan nama proyek — judul menunggu jawaban berikutnya.
            if (! $this->labelPilihanAwal($message)) {
                $conversation->update(['title' => $this->generateConversationTitle($message)]);
            }
        } elseif (in_array(trim((string) $conversation->title), ['Percakapan Baru', 'New Chat'], true)
            && $this->isValidProjectName($message)) {
            $conversation->update(['title' => $this->generateConversationTitle($message)]);
        }

        // Check if user selected "Lanjut Proyek Sebelumnya"
        if (strtolower($message) === 'lanjut proyek sebelumnya') {
            return $this->handleContinuePreviousProject($conversation);
        }

        // Check if user selected "Proyek Baru"
        if (strtolower($message) === 'proyek baru') {
            return $this->handleNewProject($conversation);
        }

        // Check if previous message was showing project list (after "Lanjut Proyek Sebelumnya")
        if ($previousAiMessage &&
            isset($previousAiMessage->metadata['question_type']) &&
            $previousAiMessage->metadata['question_type'] === 'project_list') {
            // User selected a project from list - ask for objective and expectation
            return $this->handleSelectedPreviousProject($conversation, $message);
        }

        // Check if previous message was asking for project name (after "Proyek Baru")
        if ($previousAiMessage &&
            isset($previousAiMessage->metadata['expects']) &&
            $previousAiMessage->metadata['expects'] === 'project_name') {
            // User just entered project name
            return $this->handleProjectName($conversation, $message);
        }

        // Build context for agent
        $context = [
            'user_id' => auth()->id(),
            'conversation_id' => $conversation->id,
            'department_id' => $conversation->department_id,
            'department_code' => $conversation->department->code,
            'current_step' => $conversation->current_step,
            'conversation_history' => $this->conversationHistory($conversation, $userMessage->id),
            'project_name' => $this->extractProjectNameFromHistory($conversation),
            'objective' => $this->extractFieldFromHistory($conversation, 'objective'),
        ];

        // Process with Agent Orchestrator
        $agentResponse = $this->agent->process($validated['message'], $context);

        if ($agentResponse['success']) {
            $responseContent = $agentResponse['response']['content'];

            // Parse response to check if it has options
            $parsedResponse = $this->optionGenerator->parseResponse($responseContent);
            $parsedResponse = $this->ensureStructuredOptions($parsedResponse, $responseContent, [
                'department_code' => $conversation->department->code,
                'project_name' => $this->extractProjectNameFromHistory($conversation),
                'objective' => $this->extractFieldFromHistory($conversation, 'objective'),
                'current_task' => $this->extractFieldFromHistory($conversation, 'current_task'),
                'user_input' => $message,
                'conversation_id' => $conversation->id,
            ]);

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
        foreach ($options as $option) {
            if (strcasecmp($message, trim((string) $option)) === 0) {
                return null;
            }
        }

        // Check if this is a project name question
        $questionType = $previousAiMessage?->metadata['question_type'] ?? null;
        $expects = $previousAiMessage?->metadata['expects'] ?? null;
        $previousContent = $previousAiMessage?->content ?? '';
        
        // Strict validation for project name
        if ($expects === 'project_name' || 
            $questionType === 'text_input' ||
            stripos($previousContent, 'nama proyek') !== false) {
            if (!$this->isValidProjectName($message)) {
                return 'Maaf, saya memerlukan informasi yang lebih jelas dan spesifik. Apa nama proyek Anda?';
            }
            return null;
        }

        // Validation for "Something else" options
        if (in_array('Something else', $options) && !in_array($message, $options)) {
            if (!$this->isValidFreeTextAnswer($message, $questionType)) {
                $questionContext = $this->getQuestionContext($questionType);
                return "Maaf, saya memerlukan informasi yang lebih jelas dan spesifik tentang {$questionContext}. " 
                    . ($previousAiMessage?->content ?? 'Silakan jelaskan dengan lebih detail.');
            }
            return null;
        }

        // Remove general validation - accept all other inputs
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
        
        // If first word is generic and second is just a number/short word, reject
        if (count($words) === 2 && in_array($lowerWords[0], $genericWords)) {
            if (is_numeric($words[1]) || mb_strlen($words[1]) < 3) {
                return false;
            }
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
     * Validate free text answer for "Something else" option
     */
    private function isValidFreeTextAnswer(string $answer, ?string $questionType): bool
    {
        $answer = trim($answer);
        
        // Must be meaningful first
        if (!$this->isMeaningfulAnswer($answer)) {
            return false;
        }

        // Must have at least 3 words OR 10 characters for free text
        $wordCount = str_word_count($answer);
        if ($wordCount < 3 && mb_strlen($answer) < 10) {
            return false;
        }

        // Reject very short generic answers
        $shortGeneric = ['tes', 'test', 'coba', 'lain', 'other', 'etc', 'dll', 'lainnya'];
        if (in_array(mb_strtolower($answer), $shortGeneric)) {
            return false;
        }

        return true;
    }

    /**
     * Get question context for error message
     */
    private function getQuestionContext(string $questionType): string
    {
        return match($questionType) {
            'objective' => 'objektif proyek',
            'expectation' => 'harapan proyek',
            'current_task' => 'task yang dikerjakan',
            'task_detail' => 'detail task',
            'task_challenge' => 'kendala yang dihadapi',
            'task_approach' => 'pendekatan task',
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

        if (preg_match('/(.)\1{2,}/u', $message) === 1) {
            return false;
        }

        $lowerMessage = mb_strtolower($message);
        foreach (['qwerty', 'asdf', 'zxcv', 'qaz', 'wsx', 'edc'] as $pattern) {
            if (str_contains($lowerMessage, $pattern)) {
                return false;
            }
        }

        if (mb_strlen($letters) >= 6) {
            $uniqueLetters = count(array_unique(mb_str_split(mb_strtolower($letters))));
            if ($uniqueLetters / mb_strlen($letters) < 0.35) {
                return false;
            }
        }

        return true;
    }

    private function ensureStructuredOptions(array $parsedResponse, string $responseContent, array $context): array
    {
        if ($parsedResponse['has_options'] && !empty($parsedResponse['options'])) {
            // Remove "Something else" from all options (frontend will add inline input automatically)
            $parsedResponse['options'] = array_values(array_filter(
                $parsedResponse['options'],
                fn($opt) => strcasecmp(trim($opt), 'Something else') !== 0
            ));
            
            return $parsedResponse;
        }

        $question = mb_strtolower($responseContent);
        $type = match (true) {
            preg_match('/fokus utama|detail pertama|detail task/iu', $question) === 1 => 'task_detail',
            preg_match('/kendala|hambatan|masalah yang dihadapi/iu', $question) === 1 => 'task_challenge',
            preg_match('/sudah sesuai|sudah benar|konfirmasi|simpan sebagai catatan/iu', $question) === 1 => 'confirmation',
            preg_match('/prioritas.*pekerjaan|task.*prioritas|proyek.*prioritas/iu', $question) === 1 => 'priority',
            preg_match('/estimasi.*waktu|berapa.*waktu|durasi|berapa lama/iu', $question) === 1 => 'estimation',
            preg_match('/proyek lain/iu', $question) === 1 => 'other_project',
            preg_match('/objektif utama|tujuan utama/iu', $question) === 1 => 'objective',
            preg_match('/harapan|hasil yang diinginkan/iu', $question) === 1 => 'expectation',
            preg_match('/task.*kerjakan|pekerjaan.*kerjakan/iu', $question) === 1 => 'current_task',
            default => null,
        };

        if ($type === null) {
            return $parsedResponse;
        }

        $options = $this->optionGenerator->generateOptions($type, $context);
        
        // Remove "Something else" from generated options (frontend will add inline input)
        $options = array_values(array_filter(
            $options,
            fn($opt) => strcasecmp(trim($opt), 'Something else') !== 0
        ));

        return [
            'has_options' => !empty($options),
            'message' => $parsedResponse['message'] ?? $responseContent,
            'options' => $options,
            'type' => $type,
        ];
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
     * Hasil percakapan juga dicatat sebagai Task supaya muncul di halaman
     * "Pekerjaan Saya"; tabel pekerjaan lama tetap diisi untuk laporan admin.
     */
    private function simpanKeTugas(Conversation $conversation, string $projectName, string $workDescription): void
    {
        $user = $conversation->user;

        // "Baru"/"Sebelumnya" berasal dari label pilihan, bukan nama proyek sungguhan.
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

        $judul = \Illuminate\Support\Str::limit(trim($workDescription) ?: $projectName, 120, '');

        $sudahAda = Task::where('user_id', $user->id)
            ->where('project_id', $project->id)
            ->where('judul', $judul)
            ->exists();

        if ($sudahAda) {
            return;
        }

        Task::create([
            'judul' => $judul,
            'deskripsi' => $workDescription,
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
            $content = rtrim($content) . "\n\nProyek: {$project}\nObjektif:\nTarget:\nTask:\nEstimasi:";
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
    /** Jawaban langkah pertama yang berupa label pilihan, bukan nama proyek. */
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
        // This is a placeholder - you can implement more sophisticated extraction
        // based on metadata or conversation analysis
        $messages = $conversation->messages()
            ->where('sender_type', 'user')
            ->orderBy('created_at', 'asc')
            ->get();

        // Look for field in message metadata if stored
        foreach ($messages as $message) {
            if (isset($message->metadata[$field])) {
                return $message->metadata[$field];
            }
        }

        return null;
    }

    /**
     * Handle "Lanjut Proyek Sebelumnya" selection
     */
    private function handleContinuePreviousProject(Conversation $conversation): \Illuminate\Http\JsonResponse
    {
        // Sumbernya harus sama dengan dropdown proyek pada form Tugas Baru,
        // yaitu tabel projects — bukan tabel pekerjaan lama, yang isinya berbeda.
        $userId = auth()->id();

        $milikSaya = Project::whereHas('tasks', fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('updated_at')
            ->limit(6)
            ->pluck('nama');

        // Belum punya tugas sama sekali: tawarkan proyek yang tersedia.
        $previousProjects = ($milikSaya->isNotEmpty() ? $milikSaya : Project::orderBy('nama')->limit(6)->pluck('nama'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($previousProjects)) {
            // No previous projects found
            $responseContent = 'Saya belum menemukan proyek sebelumnya. Mari mulai dengan proyek baru. Apa nama proyeknya?';

            $aiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'ai',
                'content' => $responseContent,
                'step_number' => $conversation->current_step,
                'metadata' => [
                    'has_options' => false,
                    'question_type' => 'text_input',
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
                ],
            ]);
        }

        // Show previous projects as options
        $responseContent = 'Baik, ini proyek-proyek Anda sebelumnya. Pilih salah satu:';

        $aiMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => $responseContent,
            'step_number' => $conversation->current_step,
            'metadata' => [
                'has_options' => true,
                'options' => $previousProjects,
                'question_type' => 'project_list',
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'ai_response' => [
                'content' => $responseContent,
                'type' => 'question',
                'has_options' => true,
                'options' => $previousProjects,
                'question_type' => 'project_list',
            ],
        ]);
    }

    /**
     * Handle "Proyek Baru" selection
     */
    private function handleNewProject(Conversation $conversation): \Illuminate\Http\JsonResponse
    {
        // Ask for project name (text input, no options)
        $responseContent = 'Baik, proyek baru. Apa nama proyeknya?';

        $aiMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => $responseContent,
            'step_number' => $conversation->current_step,
            'metadata' => [
                'has_options' => false,
                'question_type' => 'text_input',
                'expects' => 'project_name',
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
                'question_type' => 'text_input',
            ],
        ]);
    }

    /**
     * Handle project name input and generate objective options
     */
    private function handleProjectName(Conversation $conversation, string $projectName): \Illuminate\Http\JsonResponse
    {
        $projectName = trim($projectName);

        $metadata = $conversation->metadata ?? [];
        $metadata['project_name'] = $projectName;
        $conversation->update(['metadata' => $metadata]);

        // Use OptionGenerator to get contextual objective options
        $optionGenerator = app(\App\Services\Agent\OptionGenerator::class);
        $objectiveOptions = $optionGenerator->generateOptions('objective', [
            'project_name' => $projectName,
        ]);

        $responseContent = "Baik, proyek {$projectName}. Apa objektif utama proyek ini?";

        $aiMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => $responseContent,
            'step_number' => $conversation->current_step,
            'metadata' => [
                'has_options' => true,
                'options' => $objectiveOptions,
                'question_type' => 'objective',
                'project_name' => $projectName,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'ai_response' => [
                'content' => $responseContent,
                'type' => 'question',
                'has_options' => true,
                'options' => $objectiveOptions,
                'question_type' => 'objective',
            ],
        ]);
    }

    /**
     * Handle selected previous project and ask for objective & expectation
     */
    private function handleSelectedPreviousProject(Conversation $conversation, string $projectName): \Illuminate\Http\JsonResponse
    {
        $projectName = trim($projectName);

        // Store project name in conversation metadata
        $metadata = $conversation->metadata ?? [];
        $metadata['project_name'] = $projectName;
        $conversation->update(['metadata' => $metadata]);

        // Use OptionGenerator to get contextual objective options
        $optionGenerator = app(\App\Services\Agent\OptionGenerator::class);
        $objectiveOptions = $optionGenerator->generateOptions('objective', [
            'project_name' => $projectName,
        ]);

        $responseContent = "Baik, lanjut proyek {$projectName}. Apa objektif utama proyek ini?";

        $aiMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'ai',
            'content' => $responseContent,
            'step_number' => $conversation->current_step,
            'metadata' => [
                'has_options' => true,
                'options' => $objectiveOptions,
                'question_type' => 'objective',
                'project_name' => $projectName,
                'is_continued_project' => true,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'ai_response' => [
                'content' => $responseContent,
                'type' => 'question',
                'has_options' => true,
                'options' => $objectiveOptions,
                'question_type' => 'objective',
            ],
        ]);
    }
}
