<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.ai.api_key');
        $this->apiUrl = config('services.ai.api_url');
        $this->model = config('services.ai.model');
    }

    /**
     * Generate AI response based on conversation context
     */
    public function generateResponse(string $systemPrompt, array $conversationHistory): ?string
    {
        try {
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt]
            ];

            // Add conversation history
            foreach ($conversationHistory as $msg) {
                $messages[] = [
                    'role' => $msg['sender_type'] === 'ai' ? 'assistant' : 'user',
                    'content' => $msg['content']
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            }

            Log::error('AI API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('AI Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Generate next question based on department and step
     */
    public function generateNextQuestion(string $departmentCode, int $stepNumber, ?string $previousAnswer = null): string
    {
        $systemPrompt = "Kamu adalah AI Assistant untuk departemen {$departmentCode}. " .
                       "Buatkan pertanyaan yang relevan untuk step {$stepNumber}.";

        if ($previousAnswer) {
            $systemPrompt .= " Jawaban sebelumnya: {$previousAnswer}";
        }

        $response = $this->generateResponse($systemPrompt, []);

        return $response ?? "Silakan lanjutkan dengan informasi tambahan.";
    }
}
