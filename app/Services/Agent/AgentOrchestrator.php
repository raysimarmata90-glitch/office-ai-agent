<?php

namespace App\Services\Agent;

use App\Services\Agent\Tools\ToolRegistry;
use App\Services\Agent\Context\ContextEngine;
use Illuminate\Support\Facades\Log;

/**
 * Agent Orchestrator
 * Central agent that coordinates all components based on system intuition
 */
class AgentOrchestrator
{
    protected SystemIntuition $systemIntuition;
    protected ToolRegistry $toolRegistry;
    protected ContextEngine $contextEngine;
    protected ModelInterface $model;
    protected array $conversationContext = [];

    public function __construct(
        SystemIntuition $systemIntuition,
        ToolRegistry $toolRegistry,
        ContextEngine $contextEngine,
        ModelInterface $model
    ) {
        $this->systemIntuition = $systemIntuition;
        $this->toolRegistry = $toolRegistry;
        $this->contextEngine = $contextEngine;
        $this->model = $model;
    }

    /**
     * Process user input and generate response
     */
    public function process(string $userInput, array $context = []): array
    {
        try {
            // Step 1: Load system intuition (system prompt)
            $systemPrompt = $this->systemIntuition->getSystemPrompt($context['department_code'] ?? 'general');

            // Step 2: Enhance context with relevant information
            $enhancedContext = $this->contextEngine->buildContext(
                $userInput,
                $context,
                $context['conversation_history'] ?? $this->conversationContext
            );

            // Step 3: Determine if tools are needed
            $toolsNeeded = $this->analyzeToolRequirements($userInput, $enhancedContext);

            // Step 4: Execute tools if needed
            $toolResults = [];
            if (!empty($toolsNeeded)) {
                $toolResults = $this->executeTool($toolsNeeded, $enhancedContext);
                $enhancedContext['tool_results'] = $toolResults;
            }

            // Step 5: Generate response using model with structured output
            $response = $this->model->generate(
                $systemPrompt,
                $userInput,
                $enhancedContext
            );

            // Step 6: Validate and structure response
            $structuredResponse = $this->structureResponse($response, $context);

            // Step 7: Update conversation context
            $this->updateConversationContext($userInput, $structuredResponse);

            return [
                'success' => true,
                'response' => $structuredResponse,
                'tools_used' => array_keys($toolResults),
                'metadata' => [
                    'system_prompt_version' => $this->systemIntuition->getVersion(),
                    'context_score' => $enhancedContext['relevance_score'] ?? 0,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Agent Orchestrator Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Agent processing failed',
                'fallback_response' => $this->getFallbackResponse()
            ];
        }
    }

    public function generateWorkDescription(array $activity): ?string
    {
        try {
            $prompt = <<<PROMPT
Anda adalah pencatat pekerjaan karyawan. Ubah data aktivitas berikut menjadi satu deskripsi pekerjaan dalam bahasa Indonesia yang singkat, jelas, dan profesional.

Aturan:
- Tulis hanya deskripsi pekerjaan, tanpa label, pembuka, pertanyaan, atau Markdown.
- Fokus pada task yang dikerjakan dan hasil, status, atau estimasinya jika tersedia.
- Jangan menyalin semua kalimat mentah dan jangan menyebut data yang tidak ada.
- Maksimal dua kalimat.
PROMPT;

            $input = json_encode([
                'proyek' => $activity['project_company'] ?? null,
                'aktivitas' => $activity['activities'] ?? [],
                'ringkasan_awal' => $activity['summary'] ?? '',
            ], JSON_UNESCAPED_UNICODE);

            $description = trim($this->model->generate($prompt, $input, []));

            return $description !== '' ? $description : null;
        } catch (\Exception $e) {
            Log::warning('Work description generation failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Analyze what tools are needed for the request
     */
    protected function analyzeToolRequirements(string $input, array $context): array
    {
        $toolsNeeded = [];

        // Check if database query is needed
        if ($this->needsDatabaseQuery($input)) {
            $toolsNeeded[] = 'db_query';
        }

        // Check if MCP (Model Context Protocol) tools needed
        if ($this->needsMCPTools($input, $context)) {
            $toolsNeeded[] = 'mcp';
        }

        // Check if external API is needed
        if ($this->needsExternalAPI($input)) {
            $toolsNeeded[] = 'external_api';
        }

        return $toolsNeeded;
    }

    /**
     * Execute required tools
     */
    protected function executeTool(array $toolNames, array $context): array
    {
        $results = [];

        foreach ($toolNames as $toolName) {
            $tool = $this->toolRegistry->getTool($toolName);
            if ($tool) {
                $results[$toolName] = $tool->execute($context);
            }
        }

        return $results;
    }

    /**
     * Structure response according to JSON schema
     */
    protected function structureResponse(string $rawResponse, array $context): array
    {
        // Apply JSON schema validation and structuring
        return [
            'content' => $rawResponse,
            'type' => $this->detectResponseType($rawResponse),
            'confidence' => $this->calculateConfidence($rawResponse, $context),
            'next_action' => $this->suggestNextAction($rawResponse, $context),
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'department' => $context['department_code'] ?? null,
            ]
        ];
    }

    /**
     * Update conversation context for future turns
     */
    protected function updateConversationContext(string $input, array $response): void
    {
        $this->conversationContext[] = [
            'user_input' => $input,
            'agent_response' => $response['content'],
            'timestamp' => now(),
        ];

        // Keep only last 10 exchanges for context window management
        if (count($this->conversationContext) > 10) {
            array_shift($this->conversationContext);
        }
    }

    protected function needsDatabaseQuery(string $input): bool
    {
        $dbKeywords = ['data', 'history', 'riwayat', 'cari', 'search', 'tampilkan'];
        foreach ($dbKeywords as $keyword) {
            if (stripos($input, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function needsMCPTools(string $input, array $context): bool
    {
        // MCP tools for specific domain operations
        return isset($context['requires_mcp']) && $context['requires_mcp'];
    }

    protected function needsExternalAPI(string $input): bool
    {
        $apiKeywords = ['weather', 'cuaca', 'news', 'berita', 'price', 'harga'];
        foreach ($apiKeywords as $keyword) {
            if (stripos($input, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function detectResponseType(string $response): string
    {
        // Detect if response is question, answer, instruction, etc.
        if (preg_match('/\?$/', trim($response))) {
            return 'question';
        }
        return 'answer';
    }

    protected function calculateConfidence(string $response, array $context): float
    {
        // Simple confidence calculation based on response length and context
        $baseConfidence = 0.7;

        if (isset($context['tool_results']) && !empty($context['tool_results'])) {
            $baseConfidence += 0.2; // Higher confidence with tool results
        }

        return min($baseConfidence, 1.0);
    }

    protected function suggestNextAction(string $response, array $context): ?string
    {
        // Suggest what user should do next
        if (isset($context['next_step'])) {
            return $context['next_step'];
        }

        return null;
    }

    protected function getFallbackResponse(): string
    {
        return 'Maaf, terjadi kesalahan dalam memproses permintaan Anda. Silakan coba lagi.';
    }
}
