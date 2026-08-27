<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Model Implementation
 */
class OpenAIModel implements ModelInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.ai.api_key');
        $this->apiUrl = config('services.ai.api_url', 'https://api.openai.com/v1/chat/completions');
        $this->model = config('services.ai.model', 'gpt-4');
    }

    /**
     * Generate response from OpenAI
     */
    public function generate(string $systemPrompt, string $userInput, array $context): string
    {
        try {
            $messages = $this->buildMessages($systemPrompt, $userInput, $context);

            $response = Http::retry(2, 1000)
                ->connectTimeout(15)
                ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->removeMarkdown($data['choices'][0]['message']['content'] ?? '');
            }

            Log::error('OpenAI API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('OpenAI API request failed');

        } catch (\Exception $e) {
            Log::error('OpenAI Model Exception', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function removeMarkdown(string $content): string
    {
        $content = preg_replace('/```(?:\w+)?\s*|```/i', '', $content);
        $content = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $content);
        $content = preg_replace('/^\s*>\s?/m', '', $content);
        $content = preg_replace('/^\s*[-*+]\s+/m', '', $content);
        $content = preg_replace('/^\s*\d+[.)]\s+/m', '', $content);
        $content = preg_replace('/\[([^\]]+)\]\([^)]*\)/', '$1', $content);
        $content = preg_replace('/\*\*([^*]+)\*\*|__([^_]+)__/', '$1$2', $content);
        $content = preg_replace('/(?<!\w)[*_]([^*_]+)[*_](?!\w)/', '$1', $content);
        $content = preg_replace('/`([^`]+)`/', '$1', $content);
        $content = preg_replace("/\n{3,}/", "\n\n", $content);

        return trim($content);
    }

    /**
     * Generate structured response with JSON schema
     */
    public function generateStructured(string $systemPrompt, string $userInput, array $context, array $schema): array
    {
        try {
            $messages = $this->buildMessages($systemPrompt, $userInput, $context);

            // Add JSON schema instruction
            $messages[] = [
                'role' => 'system',
                'content' => 'Respond in JSON format according to this schema: ' . json_encode($schema)
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'response_format' => ['type' => 'json_object']
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '{}';
                return json_decode($content, true) ?? [];
            }

            Log::error('OpenAI Structured API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('OpenAI structured API request failed');

        } catch (\Exception $e) {
            Log::error('OpenAI Structured Model Exception', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build messages array for API request
     */
    protected function buildMessages(string $systemPrompt, string $userInput, array $context): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Add conversation history
        if (isset($context['conversation_history']) && is_array($context['conversation_history'])) {
            foreach ($context['conversation_history'] as $exchange) {
                if (!empty($exchange['user'])) {
                    $messages[] = ['role' => 'user', 'content' => $exchange['user']];
                }
                if (!empty($exchange['assistant'])) {
                    $messages[] = ['role' => 'assistant', 'content' => $exchange['assistant']];
                }
            }
        }

        // Add Planning context (proyek baku + task assigned)
        $planningBits = [];
        if (! empty($context['project_name'])) {
            $planningBits[] = 'Proyek terpilih: ' . $context['project_name'];
        }
        if (! empty($context['planned_tasks'])) {
            $planningBits[] = 'Task Planning yang di-assign (WAJIB dipakai untuk current_task): '
                . implode(' | ', $context['planned_tasks']);
        }
        if (! empty($context['planned_projects'])) {
            $planningBits[] = 'Daftar proyek baku Planning: ' . implode(' | ', $context['planned_projects']);
        }
        foreach (['objective', 'expectation', 'deliverable', 'current_task'] as $field) {
            if (! empty($context[$field])) {
                $planningBits[] = ucfirst($field) . ': ' . $context[$field];
            }
        }
        if ($planningBits !== []) {
            $messages[] = [
                'role' => 'system',
                'content' => "Konteks Planning saat ini:\n- " . implode("\n- ", $planningBits),
            ];
        }

        // Add retrieved context as system message
        if (isset($context['retrieved_context']) && !empty($context['retrieved_context'])) {
            $contextStr = $this->formatRetrievedContext($context['retrieved_context']);
            if ($contextStr) {
                $messages[] = [
                    'role' => 'system',
                    'content' => "Relevant context:\n" . $contextStr
                ];
            }
        }

        // Add current user input
        $messages[] = ['role' => 'user', 'content' => $userInput];

        return $messages;
    }

    /**
     * Format retrieved context for inclusion in messages
     */
    protected function formatRetrievedContext(array $retrievedContext): string
    {
        $formatted = [];

        // Format previous conversations
        if (!empty($retrievedContext['previous_conversations'])) {
            $formatted[] = "Previous relevant conversations:";
            foreach ($retrievedContext['previous_conversations'] as $conv) {
                $formatted[] = "- " . substr($conv['content'], 0, 200);
            }
        }

        // Format templates
        if (!empty($retrievedContext['templates'])) {
            $formatted[] = "\nRelevant question templates:";
            foreach ($retrievedContext['templates'] as $template) {
                $formatted[] = "- Step {$template['step']}: {$template['question']}";
            }
        }

        return implode("\n", $formatted);
    }

    /**
     * Get model information
     */
    public function getModelInfo(): array
    {
        return [
            'provider' => 'OpenAI',
            'model' => $this->model,
            'api_url' => $this->apiUrl,
        ];
    }

    /**
     * Estimate token count (rough estimation)
     */
    public function estimateTokens(string $text): int
    {
        // Rough estimation: ~4 characters per token for English
        // ~2-3 for Indonesian with mixed English
        return (int) ceil(strlen($text) / 3);
    }
}
