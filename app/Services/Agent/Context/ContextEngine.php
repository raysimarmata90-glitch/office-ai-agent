<?php

namespace App\Services\Agent\Context;

use Illuminate\Support\Facades\DB;

/**
 * Context Engine
 * Handles context engineering and RAG (Retrieval-Augmented Generation)
 */
class ContextEngine
{
    protected array $contextSources = [];
    protected int $maxContextTokens = 4000;

    /**
     * Build enhanced context for the agent
     */
    public function buildContext(string $userInput, array $baseContext, array $conversationHistory): array
    {
        $enhancedContext = $baseContext;

        // 1. Add conversation history
        $enhancedContext['conversation_history'] = $this->formatConversationHistory($conversationHistory);

        // 2. Retrieve relevant context from database (RAG)
        $enhancedContext['retrieved_context'] = $this->retrieveRelevantContext($userInput, $baseContext);

        // 3. Add user profile context
        $enhancedContext['user_context'] = $this->getUserContext($baseContext['user_id'] ?? null);

        // 4. Calculate relevance score
        $enhancedContext['relevance_score'] = $this->calculateRelevanceScore(
            $userInput,
            $enhancedContext['retrieved_context']
        );

        // 5. Prune context if too large
        $enhancedContext = $this->pruneContext($enhancedContext);

        return $enhancedContext;
    }

    /**
     * Format conversation history for context
     */
    protected function formatConversationHistory(array $history): array
    {
        return array_map(function ($exchange) {
            return [
                'user' => $exchange['user_input'] ?? '',
                'assistant' => $exchange['agent_response'] ?? '',
                'timestamp' => $exchange['timestamp'] ?? null,
            ];
        }, array_slice($history, -5)); // Last 5 exchanges
    }

    /**
     * Retrieve relevant context using RAG techniques
     */
    protected function retrieveRelevantContext(string $query, array $context): array
    {
        $relevantContext = [];

        // Retrieve from previous conversations
        if (isset($context['user_id']) && isset($context['department_id'])) {
            $relevantContext['previous_conversations'] = $this->getPreviousConversations(
                $context['user_id'],
                $context['department_id'],
                $query
            );
        }

        // Retrieve from knowledge base (if exists)
        $relevantContext['knowledge_base'] = $this->searchKnowledgeBase($query, $context);

        // Retrieve department-specific templates
        $relevantContext['templates'] = $this->getRelevantTemplates(
            $context['department_id'] ?? null,
            $query
        );

        return $relevantContext;
    }

    /**
     * Get previous relevant conversations
     */
    protected function getPreviousConversations(int $userId, int $departmentId, string $query): array
    {
        try {
            // Search in messages for similar content
            $conversations = DB::table('messages')
                ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
                ->where('conversations.user_id', $userId)
                ->where('conversations.department_id', $departmentId)
                ->where('messages.sender_type', 'ai')
                ->where('messages.content', 'like', '%' . $this->extractKeywords($query) . '%')
                ->select('messages.content', 'messages.created_at')
                ->orderBy('messages.created_at', 'desc')
                ->limit(3)
                ->get()
                ->toArray();

            return array_map(fn($msg) => [
                'content' => $msg->content,
                'date' => $msg->created_at,
            ], $conversations);

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Search knowledge base for relevant information
     */
    protected function searchKnowledgeBase(string $query, array $context): array
    {
        // TODO: Implement vector search or semantic search
        // For now, return empty array
        return [];
    }

    /**
     * Get relevant question templates
     */
    protected function getRelevantTemplates(?int $departmentId, string $query): array
    {
        if (!$departmentId) {
            return [];
        }

        try {
            $templates = DB::table('question_templates')
                ->where('department_id', $departmentId)
                ->where('question_text', 'like', '%' . $this->extractKeywords($query) . '%')
                ->select('question_text', 'system_prompt', 'step_number')
                ->limit(2)
                ->get()
                ->toArray();

            return array_map(fn($t) => [
                'question' => $t->question_text,
                'prompt' => $t->system_prompt,
                'step' => $t->step_number,
            ], $templates);

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get user context and preferences
     */
    protected function getUserContext(?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        try {
            $user = DB::table('users')
                ->join('departments', 'users.department_id', '=', 'departments.id')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->where('users.id', $userId)
                ->select('users.name', 'departments.name as department', 'roles.name as role')
                ->first();

            if ($user) {
                return [
                    'name' => $user->name,
                    'department' => $user->department,
                    'role' => $user->role,
                ];
            }

        } catch (\Exception $e) {
            // Ignore errors
        }

        return [];
    }

    /**
     * Calculate relevance score for retrieved context
     */
    protected function calculateRelevanceScore(string $query, array $retrievedContext): float
    {
        $score = 0.0;
        $maxScore = 0.0;

        // Score based on previous conversations
        if (!empty($retrievedContext['previous_conversations'])) {
            $score += 0.3;
        }
        $maxScore += 0.3;

        // Score based on knowledge base
        if (!empty($retrievedContext['knowledge_base'])) {
            $score += 0.4;
        }
        $maxScore += 0.4;

        // Score based on templates
        if (!empty($retrievedContext['templates'])) {
            $score += 0.3;
        }
        $maxScore += 0.3;

        return $maxScore > 0 ? $score / $maxScore : 0.0;
    }

    /**
     * Prune context to fit token limits
     */
    protected function pruneContext(array $context): array
    {
        // Simple pruning: limit conversation history
        if (isset($context['conversation_history']) && count($context['conversation_history']) > 5) {
            $context['conversation_history'] = array_slice($context['conversation_history'], -5);
        }

        // Limit previous conversations
        if (isset($context['retrieved_context']['previous_conversations']) && 
            count($context['retrieved_context']['previous_conversations']) > 3) {
            $context['retrieved_context']['previous_conversations'] = 
                array_slice($context['retrieved_context']['previous_conversations'], 0, 3);
        }

        return $context;
    }

    /**
     * Extract keywords from query for search
     */
    protected function extractKeywords(string $query): string
    {
        // Remove common words and keep meaningful terms
        $stopWords = ['apa', 'adalah', 'bagaimana', 'cara', 'untuk', 'yang', 'di', 'ke', 'dari'];
        $words = explode(' ', strtolower($query));
        $keywords = array_filter($words, fn($w) => !in_array($w, $stopWords) && strlen($w) > 3);
        
        return implode(' ', array_slice($keywords, 0, 3));
    }
}
