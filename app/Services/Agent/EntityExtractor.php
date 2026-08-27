<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Log;

/**
 * Entity Extractor
 * 
 * Mengekstrak entities terstruktur dari natural language input.
 * Best practice: parse semantic meaning, bukan hanya keyword matching.
 */
class EntityExtractor
{
    protected ModelInterface $model;
    
    public function __construct(ModelInterface $model)
    {
        $this->model = $model;
    }
    
    /**
     * Ekstrak entities dari user input
     * 
     * @param string $userInput Input user
     * @param string $questionType Tipe pertanyaan (objective, expectation, deliverable, dll)
     * @param array $context Konteks percakapan
     * @return array Entities yang diekstrak
     */
    public function extract(string $userInput, string $questionType, array $context = []): array
    {
        switch ($questionType) {
            case 'objective':
                return $this->extractObjective($userInput, $context);
            
            case 'expectation':
                return $this->extractExpectation($userInput, $context);
            
            case 'deliverable':
                return $this->extractDeliverable($userInput, $context);
            
            case 'current_task':
                return $this->extractCurrentTask($userInput, $context);
            
            case 'task_detail':
            case 'progress':
                return $this->extractProgress($userInput, $context);
            
            case 'estimation':
                return $this->extractEstimation($userInput, $context);
            
            case 'priority':
                return $this->extractPriority($userInput, $context);
            
            default:
                return ['raw_text' => $userInput];
        }
    }
    
    /**
     * Ekstrak objektif pekerjaan (as-is)
     */
    private function extractObjective(string $userInput, array $context): array
    {
        // Objektif adalah deskripsi bebas - simpan as is dengan sedikit cleaning
        $objective = trim($userInput);
        
        // Ekstrak komponen objektif menggunakan LLM
        $parsed = $this->parseObjectiveWithLLM($objective, $context);
        
        return [
            'objective_text' => $objective,
            'parsed' => $parsed,
            'question_type' => 'objective',
        ];
    }
    
    /**
     * Ekstrak harapan (expectation)
     * PENTING: Terima input bebas user, jangan paksa pilih dari opsi
     */
    private function extractExpectation(string $userInput, array $context): array
    {
        $expectation = trim($userInput);
        
        // Cek apakah dari opsi yang disediakan
        $options = $context['options'] ?? [];
        $isFromOptions = false;
        
        foreach ($options as $option) {
            if (strcasecmp($expectation, trim($option)) === 0) {
                $isFromOptions = true;
                break;
            }
        }
        
        return [
            'expectation_text' => $expectation,
            'is_from_options' => $isFromOptions,
            'question_type' => 'expectation',
        ];
    }
    
    /**
     * Ekstrak deliverable/hasil kerja
     * PENTING: Terima input bebas user untuk deliverable custom
     */
    private function extractDeliverable(string $userInput, array $context): array
    {
        $deliverable = trim($userInput);
        
        // Cek apakah dari opsi yang disediakan
        $options = $context['options'] ?? [];
        $isFromOptions = false;
        
        foreach ($options as $option) {
            if (strcasecmp($deliverable, trim($option)) === 0) {
                $isFromOptions = true;
                break;
            }
        }
        
        // Parse deliverable type dan details
        $parsed = $this->parseDeliverableWithLLM($deliverable, $context);
        
        return [
            'deliverable_text' => $deliverable,
            'is_from_options' => $isFromOptions,
            'parsed' => $parsed,
            'question_type' => 'deliverable',
        ];
    }
    
    /**
     * Ekstrak current task
     */
    private function extractCurrentTask(string $userInput, array $context): array
    {
        $task = trim($userInput);
        
        // Cek apakah dari Planning tasks
        $plannedTasks = $context['planned_tasks'] ?? [];
        $isFromPlanning = false;
        
        foreach ($plannedTasks as $plannedTask) {
            if (strcasecmp($task, trim($plannedTask)) === 0) {
                $isFromPlanning = true;
                break;
            }
        }
        
        return [
            'task_name' => $task,
            'is_from_planning' => $isFromPlanning,
            'question_type' => 'current_task',
        ];
    }
    
    /**
     * Ekstrak progress/detail pekerjaan
     */
    private function extractProgress(string $userInput, array $context): array
    {
        $progressText = trim($userInput);
        
        // Ekstrak persentase jika ada
        $percentage = null;
        if (preg_match('/(\d+)\s*%/', $progressText, $matches)) {
            $percentage = (int) $matches[1];
        }
        
        // Ekstrak status completion
        $isComplete = preg_match('/(selesai|complete|done|100%|finished)/i', $progressText) === 1;
        
        return [
            'progress_text' => $progressText,
            'percentage' => $percentage,
            'is_complete' => $isComplete,
            'question_type' => 'progress',
        ];
    }
    
    /**
     * Ekstrak estimasi waktu
     */
    private function extractEstimation(string $userInput, array $context): array
    {
        $estimationText = trim($userInput);
        
        // Parse durasi dalam berbagai format
        $duration = $this->parseDuration($estimationText);
        
        return [
            'estimation_text' => $estimationText,
            'duration' => $duration,
            'question_type' => 'estimation',
        ];
    }
    
    /**
     * Ekstrak prioritas
     */
    private function extractPriority(string $userInput, array $context): array
    {
        $priorityText = trim($userInput);
        
        // Normalize priority level
        $priorityLevel = null;
        $lower = mb_strtolower($priorityText);
        
        if (preg_match('/(tinggi|high|urgent|penting)/i', $lower)) {
            $priorityLevel = 'Tinggi';
        } elseif (preg_match('/(sedang|medium|normal)/i', $lower)) {
            $priorityLevel = 'Sedang';
        } elseif (preg_match('/(rendah|low)/i', $lower)) {
            $priorityLevel = 'Rendah';
        }
        
        return [
            'priority_text' => $priorityText,
            'priority_level' => $priorityLevel,
            'question_type' => 'priority',
        ];
    }
    
    /**
     * Parse objektif menggunakan LLM untuk ekstrak komponen
     */
    private function parseObjectiveWithLLM(string $objective, array $context): array
    {
        $systemPrompt = <<<PROMPT
Anda adalah parser objektif pekerjaan. Ekstrak komponen berikut dari objektif user:
- what: Apa yang dikerjakan?
- why: Tujuan/alasan (jika disebutkan)
- scope: Ruang lingkup (jika disebutkan)

Return JSON:
{
  "what": "...",
  "why": "...",
  "scope": "..."
}

Jika tidak disebutkan, beri null.
PROMPT;

        try {
            $response = $this->model->generate($systemPrompt, $objective, []);
            return json_decode($response, true) ?? ['what' => $objective];
        } catch (\Exception $e) {
            Log::warning('Objective parsing failed', ['error' => $e->getMessage()]);
            return ['what' => $objective];
        }
    }
    
    /**
     * Parse deliverable menggunakan LLM
     */
    private function parseDeliverableWithLLM(string $deliverable, array $context): array
    {
        $systemPrompt = <<<PROMPT
Anda adalah parser deliverable/hasil kerja. Ekstrak informasi berikut:
- type: Jenis deliverable (Dokumen, Prototype, Modul, Dataset, Dashboard, dll)
- description: Deskripsi singkat
- format: Format file/output jika disebutkan (PDF, Excel, Web, API, dll)

Return JSON:
{
  "type": "...",
  "description": "...",
  "format": "..."
}

Jika tidak disebutkan, beri null.
PROMPT;

        try {
            $response = $this->model->generate($systemPrompt, $deliverable, []);
            return json_decode($response, true) ?? ['description' => $deliverable];
        } catch (\Exception $e) {
            Log::warning('Deliverable parsing failed', ['error' => $e->getMessage()]);
            return ['description' => $deliverable];
        }
    }
    
    /**
     * Parse durasi dari teks
     */
    private function parseDuration(string $text): ?array
    {
        $duration = null;
        
        // Format: "X jam", "X hari", "X minggu", dll
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(jam|hour|h)/i', $text, $matches)) {
            $duration = [
                'value' => (float) str_replace(',', '.', $matches[1]),
                'unit' => 'hours',
            ];
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(hari|day|d)/i', $text, $matches)) {
            $duration = [
                'value' => (float) str_replace(',', '.', $matches[1]),
                'unit' => 'days',
            ];
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(minggu|week|w)/i', $text, $matches)) {
            $duration = [
                'value' => (float) str_replace(',', '.', $matches[1]),
                'unit' => 'weeks',
            ];
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(bulan|month|m)/i', $text, $matches)) {
            $duration = [
                'value' => (float) str_replace(',', '.', $matches[1]),
                'unit' => 'months',
            ];
        }
        
        return $duration;
    }
}
