<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Log;

/**
 * Intent Classifier
 * 
 * Mengklasifikasi intent user dari natural language input.
 * Best practice: gunakan LLM untuk understanding, bukan regex saja.
 */
class IntentClassifier
{
    protected ModelInterface $model;
    
    public function __construct(ModelInterface $model)
    {
        $this->model = $model;
    }
    
    /**
     * Klasifikasi intent dari user input
     * 
     * @param string $userInput Input user dalam natural language
     * @param array $context Konteks percakapan (question_type, conversation history, dll)
     * @return array ['intent' => string, 'confidence' => float, 'entities' => array]
     */
    public function classify(string $userInput, array $context = []): array
    {
        $questionType = $context['question_type'] ?? null;
        
        // Jika pertanyaan sedang menanyakan sesuatu yang spesifik, intent sudah jelas
        if ($questionType !== null) {
            return $this->classifyBasedOnQuestionType($userInput, $questionType, $context);
        }
        
        // Gunakan LLM untuk klasifikasi intent yang kompleks
        return $this->classifyWithLLM($userInput, $context);
    }
    
    /**
     * Klasifikasi berdasarkan question_type yang sudah diketahui
     */
    private function classifyBasedOnQuestionType(string $userInput, string $questionType, array $context): array
    {
        $options = $context['options'] ?? [];
        $allowCustom = $context['allow_custom'] ?? true;
        
        // Cek apakah user memilih dari opsi yang tersedia
        $selectedOption = null;
        foreach ($options as $option) {
            if (strcasecmp(trim($userInput), trim($option)) === 0) {
                $selectedOption = $option;
                break;
            }
        }
        
        // Jika pilih dari opsi, confidence tinggi
        if ($selectedOption !== null) {
            return [
                'intent' => "answer_{$questionType}",
                'confidence' => 0.95,
                'entities' => [
                    $questionType => $selectedOption,
                ],
                'is_from_options' => true,
            ];
        }
        
        // Jika custom input dan diperbolehkan
        if ($allowCustom && !empty($options) && $this->isMeaningfulCustomAnswer($userInput)) {
            return [
                'intent' => "answer_{$questionType}_custom",
                'confidence' => 0.80,
                'entities' => [
                    $questionType => $userInput,
                ],
                'is_from_options' => false,
            ];
        }
        
        // Free text answer (objective, deliverable custom, dll)
        if (empty($options) || $allowCustom) {
            return [
                'intent' => "answer_{$questionType}",
                'confidence' => 0.85,
                'entities' => [
                    $questionType => $userInput,
                ],
                'is_from_options' => false,
            ];
        }
        
        // Invalid input
        return [
            'intent' => 'invalid_input',
            'confidence' => 0.60,
            'entities' => [],
            'reason' => 'answer_not_in_options_and_custom_not_allowed',
        ];
    }
    
    /**
     * Klasifikasi menggunakan LLM untuk kasus kompleks
     */
    private function classifyWithLLM(string $userInput, array $context): array
    {
        $systemPrompt = <<<PROMPT
You are an intent classifier for a work activity tracking system.
Given a user input and conversation context, classify the intent and extract entities.

Available intents:
- select_project: User is selecting a project
- describe_objective: User is describing what they're working on (objektif as-is)
- describe_expectation: User is describing what's expected from them
- describe_deliverable: User is describing the deliverable/output
- describe_task: User is describing current task
- describe_progress: User is describing progress/details
- estimate_time: User is estimating time needed
- confirm: User is confirming something (ya, iya, betul, benar, sudah, ok)
- deny: User is denying/rejecting something (tidak, belum, bukan)
- other_project: User wants to work on another project
- no_more_work: User has no more work to report
- invalid_input: Meaningless or spam input

Return JSON:
{
  "intent": "intent_name",
  "confidence": 0.0-1.0,
  "entities": {
    "key": "value"
  },
  "reasoning": "brief explanation"
}
PROMPT;

        $contextStr = json_encode([
            'user_input' => $userInput,
            'conversation_history' => $context['conversation_history'] ?? [],
            'current_step' => $context['current_step'] ?? 1,
        ], JSON_UNESCAPED_UNICODE);
        
        try {
            $response = $this->model->generate($systemPrompt, $contextStr, []);
            $result = json_decode($response, true);
            
            if ($result === null || !isset($result['intent'])) {
                throw new \Exception('Invalid JSON response from LLM');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::warning('Intent classification failed', [
                'error' => $e->getMessage(),
                'input' => $userInput,
            ]);
            
            // Fallback: simple heuristic classification
            return $this->fallbackClassification($userInput);
        }
    }
    
    /**
     * Fallback classification menggunakan heuristic sederhana
     */
    private function fallbackClassification(string $userInput): array
    {
        $lower = mb_strtolower(trim($userInput));
        
        // Confirmation
        if (preg_match('/^(ya|iya|betul|benar|sudah|ok|oke|yes|yup|yep|correct)\b/i', $lower)) {
            return [
                'intent' => 'confirm',
                'confidence' => 0.85,
                'entities' => [],
            ];
        }
        
        // Denial
        if (preg_match('/^(tidak|belum|bukan|no|nope|nah)\b/i', $lower)) {
            return [
                'intent' => 'deny',
                'confidence' => 0.85,
                'entities' => [],
            ];
        }
        
        // No more work
        if (preg_match('/(tidak ada|belum ada|sudah (tidak|tak) ada|no more|sudah selesai|sudah cukup|cukup saja)/i', $lower)) {
            return [
                'intent' => 'no_more_work',
                'confidence' => 0.80,
                'entities' => [],
            ];
        }
        
        // Other project
        if (preg_match('/(proyek lain|projek lain|project lain|another project)/i', $lower)) {
            return [
                'intent' => 'other_project',
                'confidence' => 0.80,
                'entities' => [],
            ];
        }
        
        // Default: treat as free text answer
        return [
            'intent' => 'free_text_answer',
            'confidence' => 0.60,
            'entities' => [
                'text' => $userInput,
            ],
        ];
    }
    
    /**
     * Validasi apakah custom answer bermakna
     */
    private function isMeaningfulCustomAnswer(string $answer): bool
    {
        $answer = trim($answer);
        
        // Minimal 5 karakter
        if (mb_strlen($answer) < 5) {
            return false;
        }
        
        // Bukan kata-kata generik pendek
        $generic = ['test', 'tes', 'coba', 'lain', 'lainnya', 'other', 'etc', 'dll'];
        if (in_array(mb_strtolower($answer), $generic, true)) {
            return false;
        }
        
        // Bukan keyboard smash
        if (preg_match('/^(.)\1{4,}$/u', $answer)) {
            return false;
        }
        
        $lower = mb_strtolower($answer);
        $keyboardPatterns = ['qwerty', 'asdfgh', 'zxcvbn', 'qweasd'];
        foreach ($keyboardPatterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return false;
            }
        }
        
        return true;
    }
}
