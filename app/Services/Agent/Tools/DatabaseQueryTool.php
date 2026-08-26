<?php

namespace App\Services\Agent\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Database Query Tool
 * Allows agent to query database for information
 */
class DatabaseQueryTool implements ToolInterface
{
    protected array $allowedTables = [
        'conversations',
        'messages',
        'question_templates',
        'departments',
        'users',
    ];

    public function execute(array $context): array
    {
        try {
            $queryType = $context['query_type'] ?? 'auto';
            $userId = $context['user_id'] ?? null;
            $departmentId = $context['department_id'] ?? null;

            switch ($queryType) {
                case 'conversations':
                    return $this->queryConversations($userId, $departmentId);
                
                case 'messages':
                    return $this->queryMessages($context['conversation_id'] ?? null);
                
                case 'templates':
                    return $this->queryTemplates($departmentId);
                
                case 'user_stats':
                    return $this->queryUserStats($userId);
                
                default:
                    return $this->autoQuery($context);
            }

        } catch (\Exception $e) {
            Log::error('DatabaseQueryTool Error', [
                'message' => $e->getMessage(),
                'context' => $context
            ]);

            return [
                'success' => false,
                'error' => 'Database query failed'
            ];
        }
    }

    public function getName(): string
    {
        return 'db_query';
    }

    public function getDescription(): string
    {
        return 'Query database for historical data, conversations, templates, and user information';
    }

    public function canHandle(array $context): bool
    {
        return isset($context['requires_database']) || 
               isset($context['user_id']) || 
               isset($context['conversation_id']);
    }

    /**
     * Query conversations
     */
    protected function queryConversations(?int $userId, ?int $departmentId): array
    {
        $query = DB::table('conversations')
            ->join('departments', 'conversations.department_id', '=', 'departments.id')
            ->select(
                'conversations.id',
                'conversations.title',
                'conversations.status',
                'conversations.current_step',
                'departments.name as department',
                'conversations.created_at'
            );

        if ($userId) {
            $query->where('conversations.user_id', $userId);
        }

        if ($departmentId) {
            $query->where('conversations.department_id', $departmentId);
        }

        $conversations = $query->orderBy('conversations.created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'success' => true,
            'data' => $conversations,
            'count' => count($conversations)
        ];
    }

    /**
     * Query messages
     */
    protected function queryMessages(?int $conversationId): array
    {
        if (!$conversationId) {
            return ['success' => false, 'error' => 'Conversation ID required'];
        }

        $messages = DB::table('messages')
            ->where('conversation_id', $conversationId)
            ->select('id', 'sender_type', 'content', 'step_number', 'created_at')
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();

        return [
            'success' => true,
            'data' => $messages,
            'count' => count($messages)
        ];
    }

    /**
     * Query templates
     */
    protected function queryTemplates(?int $departmentId): array
    {
        $query = DB::table('question_templates')
            ->select('id', 'question_text', 'step_number', 'expected_response_type');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $templates = $query->orderBy('step_number', 'asc')
            ->get()
            ->toArray();

        return [
            'success' => true,
            'data' => $templates,
            'count' => count($templates)
        ];
    }

    /**
     * Query user statistics
     */
    protected function queryUserStats(?int $userId): array
    {
        if (!$userId) {
            return ['success' => false, 'error' => 'User ID required'];
        }

        $stats = [
            'total_conversations' => DB::table('conversations')
                ->where('user_id', $userId)
                ->count(),
            
            'active_conversations' => DB::table('conversations')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->count(),
            
            'completed_conversations' => DB::table('conversations')
                ->where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
            
            'total_messages' => DB::table('messages')
                ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
                ->where('conversations.user_id', $userId)
                ->where('messages.sender_type', 'user')
                ->count(),
        ];

        return [
            'success' => true,
            'data' => $stats
        ];
    }

    /**
     * Auto query based on context
     */
    protected function autoQuery(array $context): array
    {
        // Determine what to query based on available context
        if (isset($context['conversation_id'])) {
            return $this->queryMessages($context['conversation_id']);
        }

        if (isset($context['user_id'])) {
            return $this->queryUserStats($context['user_id']);
        }

        return [
            'success' => false,
            'error' => 'Insufficient context for auto query'
        ];
    }
}
