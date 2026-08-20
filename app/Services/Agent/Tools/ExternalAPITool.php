<?php

namespace App\Services\Agent\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * External API Tool
 * Handle calls to external APIs (weather, news, etc.)
 */
class ExternalAPITool implements ToolInterface
{
    protected array $supportedAPIs = [
        'weather',
        'news',
        'exchange_rate',
    ];

    public function execute(array $context): array
    {
        try {
            $apiType = $context['api_type'] ?? null;

            if (!$apiType || !in_array($apiType, $this->supportedAPIs)) {
                return [
                    'success' => false,
                    'error' => 'Unsupported or missing API type'
                ];
            }

            switch ($apiType) {
                case 'weather':
                    return $this->getWeather($context['location'] ?? 'Jakarta');
                
                case 'news':
                    return $this->getNews($context['category'] ?? 'technology');
                
                case 'exchange_rate':
                    return $this->getExchangeRate($context['currency'] ?? 'USD');
                
                default:
                    return [
                        'success' => false,
                        'error' => 'API type not implemented'
                    ];
            }

        } catch (\Exception $e) {
            Log::error('ExternalAPITool Error', [
                'message' => $e->getMessage(),
                'context' => $context
            ]);

            return [
                'success' => false,
                'error' => 'External API call failed'
            ];
        }
    }

    public function getName(): string
    {
        return 'external_api';
    }

    public function getDescription(): string
    {
        return 'Call external APIs for weather, news, and other real-time data';
    }

    public function canHandle(array $context): bool
    {
        return isset($context['api_type']) && 
               in_array($context['api_type'], $this->supportedAPIs);
    }

    /**
     * Get weather information (placeholder)
     */
    protected function getWeather(string $location): array
    {
        // TODO: Integrate with actual weather API
        // For now, return mock data
        
        return [
            'success' => true,
            'data' => [
                'location' => $location,
                'temperature' => '28°C',
                'condition' => 'Partly Cloudy',
                'humidity' => '70%',
                'message' => 'Weather API integration pending'
            ]
        ];
    }

    /**
     * Get news (placeholder)
     */
    protected function getNews(string $category): array
    {
        // TODO: Integrate with actual news API
        // For now, return mock data
        
        return [
            'success' => true,
            'data' => [
                'category' => $category,
                'articles' => [],
                'message' => 'News API integration pending'
            ]
        ];
    }

    /**
     * Get exchange rate (placeholder)
     */
    protected function getExchangeRate(string $currency): array
    {
        // TODO: Integrate with actual exchange rate API
        // For now, return mock data
        
        return [
            'success' => true,
            'data' => [
                'base' => 'IDR',
                'target' => $currency,
                'rate' => $currency === 'USD' ? 15800 : 0,
                'message' => 'Exchange rate API integration pending'
            ]
        ];
    }
}
