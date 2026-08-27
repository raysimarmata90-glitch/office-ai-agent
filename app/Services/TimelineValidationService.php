<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDeliverable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk validasi timeline dan auto-blocking projects
 */
class TimelineValidationService
{
    /**
     * Check and auto-block overdue projects
     * 
     * @return array Summary of blocked projects
     */
    public function checkAndBlockOverdueProjects(): array
    {
        $now = Carbon::now();
        $blocked = [];

        // Get all active projects with due dates
        $projects = Project::where('is_archived', false)
            ->where('is_blocked', false)
            ->whereNotNull('due_date')
            ->where('status', '!=', 'Closed')
            ->get();

        foreach ($projects as $project) {
            if ($this->isOverdue($project)) {
                $project->is_blocked = true;
                $project->save();

                $blocked[] = [
                    'id' => $project->id,
                    'client' => $project->client_or_rd,
                    'project' => $project->key_deliverables,
                    'due_date' => $project->due_date->format('Y-m-d'),
                    'days_overdue' => $this->getDaysOverdue($project),
                ];

                Log::warning("Project auto-blocked due to overdue", [
                    'project_id' => $project->id,
                    'client' => $project->client_or_rd,
                    'due_date' => $project->due_date,
                ]);
            }
        }

        return [
            'checked_at' => $now->toDateTimeString(),
            'total_checked' => $projects->count(),
            'total_blocked' => count($blocked),
            'blocked_projects' => $blocked,
        ];
    }

    /**
     * Check and auto-block overdue deliverables
     * 
     * @return array Summary of blocked deliverables
     */
    public function checkAndBlockOverdueDeliverables(): array
    {
        $now = Carbon::now();
        $blocked = [];

        // Get all incomplete deliverables with due dates
        $deliverables = ProjectDeliverable::where('is_completed', false)
            ->whereNotNull('due_date')
            ->with('project')
            ->get();

        foreach ($deliverables as $deliverable) {
            if ($this->isDeliverableOverdue($deliverable)) {
                // Mark deliverable as problematic (we could add a field for this)
                // For now, we just log it
                
                $blocked[] = [
                    'id' => $deliverable->id,
                    'project' => $deliverable->project->client_or_rd ?? 'Unknown',
                    'deliverable' => $deliverable->deliverable_name,
                    'category' => $deliverable->category,
                    'due_date' => $deliverable->due_date->format('Y-m-d'),
                    'days_overdue' => $this->getDeliverableDaysOverdue($deliverable),
                ];

                Log::warning("Deliverable overdue detected", [
                    'deliverable_id' => $deliverable->id,
                    'project_id' => $deliverable->project_id,
                    'deliverable_name' => $deliverable->deliverable_name,
                    'due_date' => $deliverable->due_date,
                ]);
            }
        }

        return [
            'checked_at' => $now->toDateTimeString(),
            'total_checked' => $deliverables->count(),
            'total_overdue' => count($blocked),
            'overdue_deliverables' => $blocked,
        ];
    }

    /**
     * Check if project is overdue
     */
    public function isOverdue(Project $project): bool
    {
        if (!$project->due_date) {
            return false;
        }

        return Carbon::parse($project->due_date)->isPast() 
            && $project->status !== 'Closed';
    }

    /**
     * Check if deliverable is overdue
     */
    public function isDeliverableOverdue(ProjectDeliverable $deliverable): bool
    {
        if (!$deliverable->due_date) {
            return false;
        }

        return Carbon::parse($deliverable->due_date)->isPast() 
            && !$deliverable->is_completed;
    }

    /**
     * Get number of days a project is overdue
     */
    public function getDaysOverdue(Project $project): int
    {
        if (!$project->due_date || !$this->isOverdue($project)) {
            return 0;
        }

        return Carbon::parse($project->due_date)->diffInDays(Carbon::now(), false);
    }

    /**
     * Get number of days a deliverable is overdue
     */
    public function getDeliverableDaysOverdue(ProjectDeliverable $deliverable): int
    {
        if (!$deliverable->due_date || !$this->isDeliverableOverdue($deliverable)) {
            return 0;
        }

        return Carbon::parse($deliverable->due_date)->diffInDays(Carbon::now(), false);
    }

    /**
     * Get all blocked projects
     */
    public function getBlockedProjects(): array
    {
        return Project::where('is_blocked', true)
            ->where('is_archived', false)
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function($project) {
                return [
                    'id' => $project->id,
                    'client' => $project->client_or_rd,
                    'project' => $project->key_deliverables,
                    'status' => $project->status,
                    'pic' => $project->pic,
                    'due_date' => $project->due_date?->format('Y-m-d'),
                    'days_overdue' => $this->getDaysOverdue($project),
                    'blocked_at' => $project->updated_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    /**
     * Get all overdue deliverables
     */
    public function getOverdueDeliverables(): array
    {
        return ProjectDeliverable::where('is_completed', false)
            ->whereNotNull('due_date')
            ->with('project')
            ->get()
            ->filter(function($deliverable) {
                return $this->isDeliverableOverdue($deliverable);
            })
            ->map(function($deliverable) {
                return [
                    'id' => $deliverable->id,
                    'project' => $deliverable->project->client_or_rd ?? 'Unknown',
                    'deliverable' => $deliverable->deliverable_name,
                    'category' => $deliverable->category,
                    'pic' => $deliverable->pic,
                    'due_date' => $deliverable->due_date->format('Y-m-d'),
                    'days_overdue' => $this->getDeliverableDaysOverdue($deliverable),
                    'completion_percentage' => $deliverable->completion_percentage,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Unblock a project (admin action)
     */
    public function unblockProject(int $projectId, ?string $reason = null): bool
    {
        $project = Project::find($projectId);
        
        if (!$project) {
            return false;
        }

        $project->is_blocked = false;
        $project->save();

        Log::info("Project manually unblocked", [
            'project_id' => $projectId,
            'client' => $project->client_or_rd,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStatistics(): array
    {
        $totalProjects = Project::where('is_archived', false)->count();
        $blockedProjects = Project::where('is_blocked', true)
            ->where('is_archived', false)
            ->count();
        
        $totalDeliverables = ProjectDeliverable::count();
        $overdueDeliverables = ProjectDeliverable::where('is_completed', false)
            ->whereNotNull('due_date')
            ->get()
            ->filter(function($deliverable) {
                return $this->isDeliverableOverdue($deliverable);
            })
            ->count();

        $upcomingDue = Project::where('is_archived', false)
            ->where('is_blocked', false)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [Carbon::now(), Carbon::now()->addDays(7)])
            ->count();

        return [
            'total_projects' => $totalProjects,
            'blocked_projects' => $blockedProjects,
            'blocked_percentage' => $totalProjects > 0 
                ? round(($blockedProjects / $totalProjects) * 100, 2) 
                : 0,
            'total_deliverables' => $totalDeliverables,
            'overdue_deliverables' => $overdueDeliverables,
            'upcoming_due_projects' => $upcomingDue, // Projects due in next 7 days
        ];
    }

    /**
     * Validate timeline for a new task/deliverable
     * Returns warning if timeline seems unrealistic
     */
    public function validateNewTimeline(int $estimatedDays, string $category): array
    {
        $warnings = [];

        // Category-specific timeline expectations
        $expectedDays = match($category) {
            'TECH' => ['min' => 3, 'max' => 90, 'typical' => 14],
            'COMMERCIAL' => ['min' => 1, 'max' => 60, 'typical' => 7],
            'LEGAL' => ['min' => 7, 'max' => 180, 'typical' => 30],
            'EKSPANSI' => ['min' => 14, 'max' => 180, 'typical' => 60],
            default => ['min' => 1, 'max' => 90, 'typical' => 14],
        };

        if ($estimatedDays < $expectedDays['min']) {
            $warnings[] = "Estimasi waktu sangat pendek untuk kategori {$category}. Minimum yang disarankan: {$expectedDays['min']} hari.";
        }

        if ($estimatedDays > $expectedDays['max']) {
            $warnings[] = "Estimasi waktu sangat panjang untuk kategori {$category}. Maksimum yang disarankan: {$expectedDays['max']} hari.";
        }

        return [
            'valid' => empty($warnings),
            'warnings' => $warnings,
            'recommended_days' => $expectedDays['typical'],
        ];
    }
}
