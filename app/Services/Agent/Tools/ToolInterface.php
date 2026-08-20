<?php

namespace App\Services\Agent\Tools;

/**
 * Tool Interface
 * Interface for all agent tools (DB Query, MCP, External APIs)
 */
interface ToolInterface
{
    /**
     * Execute the tool with given context
     */
    public function execute(array $context): array;

    /**
     * Get tool name
     */
    public function getName(): string;

    /**
     * Get tool description
     */
    public function getDescription(): string;

    /**
     * Validate if tool can handle the context
     */
    public function canHandle(array $context): bool;
}
