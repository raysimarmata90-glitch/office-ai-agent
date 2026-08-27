<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\WorkActivity;
use App\Services\Agent\IntentClassifier;
use App\Services\Agent\EntityExtractor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * WorkActivityService
 * 
 * Service untuk mengelola work activities dengan proper NLU.
 * Best practice: pisahkan business logic dari controller.
 */
class WorkActivityService
{
    protected IntentClassifier $intentClassifier;
    protected EntityExtractor $entityExtractor;
    
    public function __construct(
        IntentClassifier $intentClassifier,
        EntityExtractor $entityExtractor
    ) {
        $this->intentClassifier = $intentClassifier;
        $this->entityExtractor = $entityExtractor;
    }
    
    /**
     * Process user answer dan update WorkActivity
     * 
     * @param Conversation $conversation
     * @param Message $userMessage User message yang baru
     * @param Message|null $previousAiMessage AI message sebelumnya (untuk context)
     * @return array ['work_activity' => WorkActivity, 'entities' => array]
     */
    public function processUserAnswer(
        Conversation $conversation,
        Message $userMessage,
        ?Message $previousAiMessage = null
    ): array {
        $questionType = $previousAiMessage?->metadata['question_type'] ?? null;
        $options = $previousAiMessage?->metadata['options'] ?? [];
        $allowCustom = $previousAiMessage?->metadata['allow_custom'] ?? true;
        
        // Classify intent
        $classification = $this->intentClassifier->classify($userMessage->content, [
            'question_type' => $questionType,
            'options' => $options,
            'allow_custom' => $allowCustom,
            'conversation_history' => $this->getConversationHistory($conversation),
            'current_step' => $conversation->current_step,
        ]);
        
        // Update message dengan intent classification
        $userMessage->update([
            'intent' => $classification['intent'],
            'intent_confidence' => $classification['confidence'],
        ]);
        
        // Extract entities jika intent valid
        $entities = [];
        if ($classification['confidence'] >= 0.7 && $questionType !== null) {
            $entities = $this->entityExtractor->extract(
                $userMessage->content,
                $questionType,
                [
                    'options' => $options,
                    'planned_tasks' => $this->getPlannedTasks($conversation),
                    'conversation_id' => $conversation->id,
                ]
            );
            
            // Update message dengan entities
            $userMessage->update(['entities' => $entities]);
        }
        
        // Get or create WorkActivity untuk conversation ini
        $workActivity = $this->getOrCreateWorkActivity($conversation);
        
        // Update WorkActivity berdasarkan question_type
        if (!empty($entities)) {
            $this->updateWorkActivityWithEntities($workActivity, $entities, $questionType);
        }
        
        return [
            'work_activity' => $workActivity->fresh(),
            'entities' => $entities,
            'classification' => $classification,
        ];
    }
    
    /**
     * Get or create WorkActivity untuk conversation
     */
    public function getOrCreateWorkActivity(Conversation $conversation): WorkActivity
    {
        $workActivity = WorkActivity::firstOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'user_id' => $conversation->user_id,
                'project_name' => $this->extractProjectNameFromConversation($conversation),
                'raw_answers' => [],
            ]
        );
        
        return $workActivity;
    }
    
    /**
     * Update WorkActivity dengan entities yang diekstrak
     */
    protected function updateWorkActivityWithEntities(
        WorkActivity $workActivity,
        array $entities,
        string $questionType
    ): void {
        $updates = [];
        $rawAnswers = $workActivity->raw_answers ?? [];
        
        switch ($questionType) {
            case 'objective':
                $updates['objective'] = $entities['objective_text'] ?? null;
                $updates['objective_parsed'] = $entities['parsed'] ?? null;
                $rawAnswers['objective'] = $entities['objective_text'] ?? null;
                break;
            
            case 'expectation':
                $updates['expectation'] = $entities['expectation_text'] ?? null;
                $updates['expectation_from_options'] = $entities['is_from_options'] ?? false;
                $rawAnswers['expectation'] = $entities['expectation_text'] ?? null;
                break;
            
            case 'deliverable':
                $updates['deliverable'] = $entities['deliverable_text'] ?? null;
                $updates['deliverable_from_options'] = $entities['is_from_options'] ?? false;
                $updates['deliverable_parsed'] = $entities['parsed'] ?? null;
                $rawAnswers['deliverable'] = $entities['deliverable_text'] ?? null;
                break;
            
            case 'current_task':
                $updates['current_task'] = $entities['task_name'] ?? null;
                $updates['task_from_planning'] = $entities['is_from_planning'] ?? false;
                $rawAnswers['current_task'] = $entities['task_name'] ?? null;
                break;
            
            case 'task_detail':
            case 'progress':
                $updates['progress_detail'] = $entities['progress_text'] ?? null;
                $updates['progress_percentage'] = $entities['percentage'] ?? null;
                $updates['is_complete'] = $entities['is_complete'] ?? false;
                $rawAnswers['progress'] = $entities['progress_text'] ?? null;
                break;
            
            case 'estimation':
                $updates['estimation_text'] = $entities['estimation_text'] ?? null;
                $updates['estimation_duration'] = $entities['duration'] ?? null;
                $rawAnswers['estimation'] = $entities['estimation_text'] ?? null;
                break;
            
            case 'priority':
                $updates['priority_level'] = $entities['priority_level'] ?? null;
                $rawAnswers['priority'] = $entities['priority_text'] ?? null;
                break;
        }
        
        if (!empty($updates)) {
            $updates['raw_answers'] = $rawAnswers;
            $workActivity->update($updates);
        }
    }
    
    /**
     * Mark work activity sebagai complete
     */
    public function completeWorkActivity(WorkActivity $workActivity): void
    {
        if ($workActivity->isComplete()) {
            $workActivity->update([
                'completed_at' => now(),
            ]);
        }
    }
    
    /**
     * Get conversation history untuk context
     */
    protected function getConversationHistory(Conversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();
        
        $history = [];
        $pendingUser = null;
        
        foreach ($messages as $message) {
            if ($message->isFromUser()) {
                $pendingUser = $message->content;
            } elseif ($pendingUser !== null) {
                $history[] = [
                    'user' => $pendingUser,
                    'ai' => $message->content,
                ];
                $pendingUser = null;
            }
        }
        
        return $history;
    }
    
    /**
     * Get planned tasks untuk user dan project
     */
    protected function getPlannedTasks(Conversation $conversation): array
    {
        // Implementasi: ambil dari controller atau extract dari conversation
        // Untuk sementara return empty array
        return [];
    }
    
    /**
     * Extract project name dari conversation history
     */
    protected function extractProjectNameFromConversation(Conversation $conversation): string
    {
        // Cek metadata conversation
        if (!empty($conversation->metadata['project_name'])) {
            return $conversation->metadata['project_name'];
        }
        
        // Cek title conversation
        $title = $conversation->title;
        if ($title && !in_array($title, ['Percakapan Baru', 'New Chat'], true)) {
            return preg_replace('/^Proyek:\s*/i', '', $title);
        }
        
        // Cek dari messages
        $firstUserMessage = $conversation->messages()
            ->where('sender_type', 'user')
            ->orderBy('created_at')
            ->first();
        
        if ($firstUserMessage) {
            return $firstUserMessage->content;
        }
        
        return 'Tidak disebutkan';
    }
    
    /**
     * Generate work summary dari WorkActivity
     */
    public function generateWorkSummary(WorkActivity $workActivity): string
    {
        return $workActivity->getSummary();
    }
    
    /**
     * Get all work activities untuk user dengan filtering
     */
    public function getUserWorkActivities(
        int $userId,
        ?string $projectName = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = WorkActivity::where('user_id', $userId)
            ->with(['conversation', 'user'])
            ->orderByDesc('created_at');
        
        if ($projectName) {
            $query->where('project_name', $projectName);
        }
        
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        return $query->get();
    }
    
    /**
     * Get work activity statistics untuk user
     */
    public function getUserWorkStats(int $userId, ?string $period = 'month'): array
    {
        $query = WorkActivity::where('user_id', $userId);
        
        // Filter by period
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
            default:
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
        }
        
        $activities = $query->get();
        
        return [
            'total_activities' => $activities->count(),
            'completed_activities' => $activities->where('is_complete', true)->count(),
            'total_projects' => $activities->pluck('project_name')->unique()->count(),
            'total_hours_estimated' => $activities->sum(function ($activity) {
                return $activity->getEstimationInHours() ?? 0;
            }),
            'activities_by_project' => $activities->groupBy('project_name')->map->count(),
            'activities_by_priority' => $activities->whereNotNull('priority_level')
                ->groupBy('priority_level')
                ->map->count(),
        ];
    }
}
