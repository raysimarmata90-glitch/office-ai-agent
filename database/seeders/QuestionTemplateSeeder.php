<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\QuestionTemplate;
use Illuminate\Database\Seeder;

class QuestionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // AI Department Questions
        $aiDept = Department::where('code', 'ai')->first();
        $aiQuestions = [
            [
                'step_number' => 1,
                'question_text' => 'Apa model AI yang ingin Anda gunakan untuk project ini?',
                'system_prompt' => 'Kamu adalah AI Assistant yang membantu memilih model AI yang tepat.',
                'order' => 1,
            ],
            [
                'step_number' => 2,
                'question_text' => 'Berapa estimasi budget untuk infrastruktur AI?',
                'system_prompt' => 'Kamu adalah AI Assistant yang membantu menghitung budget AI infrastructure.',
                'order' => 2,
            ],
            [
                'step_number' => 3,
                'question_text' => 'Apa use case utama yang ingin diselesaikan?',
                'system_prompt' => 'Kamu adalah AI Assistant yang membantu mendefinisikan use case AI.',
                'order' => 3,
            ],
        ];

        foreach ($aiQuestions as $question) {
            QuestionTemplate::create(array_merge($question, ['department_id' => $aiDept->id]));
        }

        // Platform Department Questions
        $platformDept = Department::where('code', 'platform')->first();
        $platformQuestions = [
            [
                'step_number' => 1,
                'question_text' => 'Teknologi stack apa yang akan digunakan?',
                'system_prompt' => 'Kamu adalah Platform Engineer yang membantu memilih technology stack.',
                'order' => 1,
            ],
            [
                'step_number' => 2,
                'question_text' => 'Berapa jumlah user yang diharapkan?',
                'system_prompt' => 'Kamu adalah Platform Engineer yang membantu merencanakan scalability.',
                'order' => 2,
            ],
            [
                'step_number' => 3,
                'question_text' => 'Apa requirement keamanan untuk platform ini?',
                'system_prompt' => 'Kamu adalah Platform Engineer yang fokus pada security requirements.',
                'order' => 3,
            ],
        ];

        foreach ($platformQuestions as $question) {
            QuestionTemplate::create(array_merge($question, ['department_id' => $platformDept->id]));
        }

        // Business Analysis Department Questions
        $baDept = Department::where('code', 'ba')->first();
        $baQuestions = [
            [
                'step_number' => 1,
                'question_text' => 'Apa tujuan bisnis utama dari project ini?',
                'system_prompt' => 'Kamu adalah Business Analyst yang membantu mendefinisikan business goals.',
                'order' => 1,
            ],
            [
                'step_number' => 2,
                'question_text' => 'Siapa target user atau customer utama?',
                'system_prompt' => 'Kamu adalah Business Analyst yang membantu mengidentifikasi target audience.',
                'order' => 2,
            ],
            [
                'step_number' => 3,
                'question_text' => 'Apa metrik kesuksesan yang akan diukur?',
                'system_prompt' => 'Kamu adalah Business Analyst yang membantu mendefinisikan KPIs.',
                'order' => 3,
            ],
        ];

        foreach ($baQuestions as $question) {
            QuestionTemplate::create(array_merge($question, ['department_id' => $baDept->id]));
        }
    }
}
