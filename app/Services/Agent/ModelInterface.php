<?php

namespace App\Services\Agent;

/**
 * Model Interface
 * Interface for LLM providers (OpenAI, Anthropic, etc.)
 */
interface ModelInterface
{
    /**
     * Generate response from the model
     */
    public function generate(string $systemPrompt, string $userInput, array $context): string;

    /**
     * Generate structured response with JSON schema
     */
    public function generateStructured(string $systemPrompt, string $userInput, array $context, array $schema): array;

    /**
     * Get model name and version
     */
    public function getModelInfo(): array;

    /**
     * Estimate token count
     */
    public function estimateTokens(string $text): int;
}
