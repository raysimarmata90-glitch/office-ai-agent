<?php

namespace App\Services\Agent\Tools;

/**
 * Tool Registry
 * Manages registration and retrieval of agent tools
 */
class ToolRegistry
{
    protected array $tools = [];

    public function __construct()
    {
        $this->registerDefaultTools();
    }

    /**
     * Register a tool
     */
    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * Get a tool by name
     */
    public function getTool(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get all registered tools
     */
    public function getAllTools(): array
    {
        return $this->tools;
    }

    /**
     * Get tools that can handle specific context
     */
    public function getCompatibleTools(array $context): array
    {
        return array_filter($this->tools, fn($tool) => $tool->canHandle($context));
    }

    /**
     * Register default tools
     */
    protected function registerDefaultTools(): void
    {
        $this->register(new DatabaseQueryTool());
        $this->register(new MCPTool());
        $this->register(new ExternalAPITool());
    }
}
