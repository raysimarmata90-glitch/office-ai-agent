<?php

namespace App\Providers;

use App\Services\Agent\AgentOrchestrator;
use App\Services\Agent\SystemIntuition;
use App\Services\Agent\Context\ContextEngine;
use App\Services\Agent\ModelInterface;
use App\Services\Agent\OpenAIModel;
use App\Services\Agent\OptionGenerator;
use App\Services\Agent\IntentClassifier;
use App\Services\Agent\EntityExtractor;
use App\Services\Agent\Tools\ToolRegistry;
use App\Services\WorkActivityService;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Register System Intuition
        $this->app->singleton(SystemIntuition::class, function ($app) {
            return new SystemIntuition();
        });

        // Register Option Generator
        $this->app->singleton(OptionGenerator::class, function ($app) {
            return new OptionGenerator();
        });

        // Register Context Engine
        $this->app->singleton(ContextEngine::class, function ($app) {
            return new ContextEngine();
        });

        // Register Tool Registry
        $this->app->singleton(ToolRegistry::class, function ($app) {
            return new ToolRegistry();
        });

        // Register Model Interface
        $this->app->singleton(ModelInterface::class, function ($app) {
            return new OpenAIModel();
        });
        
        // ✨ NEW: Register Intent Classifier
        $this->app->singleton(IntentClassifier::class, function ($app) {
            return new IntentClassifier($app->make(ModelInterface::class));
        });
        
        // ✨ NEW: Register Entity Extractor
        $this->app->singleton(EntityExtractor::class, function ($app) {
            return new EntityExtractor($app->make(ModelInterface::class));
        });
        
        // ✨ NEW: Register WorkActivityService
        $this->app->singleton(WorkActivityService::class, function ($app) {
            return new WorkActivityService(
                $app->make(IntentClassifier::class),
                $app->make(EntityExtractor::class)
            );
        });

        // Register Agent Orchestrator
        $this->app->singleton(AgentOrchestrator::class, function ($app) {
            return new AgentOrchestrator(
                $app->make(SystemIntuition::class),
                $app->make(ToolRegistry::class),
                $app->make(ContextEngine::class),
                $app->make(ModelInterface::class)
            );
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}
