<?php

namespace App\Console\Commands;

use App\Services\TimelineValidationService;
use Illuminate\Console\Command;

class CheckOverdueProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and auto-block overdue projects and deliverables';

    protected TimelineValidationService $timelineService;

    /**
     * Create a new command instance.
     */
    public function __construct(TimelineValidationService $timelineService)
    {
        parent::__construct();
        $this->timelineService = $timelineService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking overdue projects...');

        // Check and block overdue projects
        $projectsResult = $this->timelineService->checkAndBlockOverdueProjects();
        
        $this->info("Checked {$projectsResult['total_checked']} projects");
        $this->warn("Blocked {$projectsResult['total_blocked']} overdue projects");

        if ($projectsResult['total_blocked'] > 0) {
            $this->table(
                ['ID', 'Client', 'Project', 'Due Date', 'Days Overdue'],
                array_map(function($project) {
                    return [
                        $project['id'],
                        $project['client'],
                        substr($project['project'], 0, 30),
                        $project['due_date'],
                        $project['days_overdue'],
                    ];
                }, $projectsResult['blocked_projects'])
            );
        }

        $this->info('');
        $this->info('Checking overdue deliverables...');

        // Check overdue deliverables
        $deliverablesResult = $this->timelineService->checkAndBlockOverdueDeliverables();
        
        $this->info("Checked {$deliverablesResult['total_checked']} deliverables");
        $this->warn("Found {$deliverablesResult['total_overdue']} overdue deliverables");

        if ($deliverablesResult['total_overdue'] > 0) {
            $this->table(
                ['ID', 'Project', 'Deliverable', 'Category', 'Due Date', 'Days Overdue'],
                array_map(function($deliverable) {
                    return [
                        $deliverable['id'],
                        substr($deliverable['project'], 0, 20),
                        substr($deliverable['deliverable'], 0, 25),
                        $deliverable['category'],
                        $deliverable['due_date'],
                        $deliverable['days_overdue'],
                    ];
                }, $deliverablesResult['overdue_deliverables'])
            );
        }

        $this->info('');
        $this->info('✓ Overdue check completed!');

        return 0;
    }
}

