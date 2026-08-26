<?php

namespace App\Services\Agent;

/**
 * Option Generator
 * Generates dynamic contextual options for chatbot responses
 */
class OptionGenerator
{
    /**
     * Generate options based on question type and context
     * 
     * CRITICAL PRIORITY LOGIC:
     * 1. ALWAYS use department_code if available in context - this ensures BA gets BA options, AI gets AI options, etc.
     * 2. NEVER let project name keywords (e.g., "BPJS", "Bank") override department-specific options
     * 3. Domain detection from project name is ONLY used as fallback when department_code is null/missing
     * 
     * Examples:
     * - BA user + "Projek Business Analyst BPJS" → BA options (Proposal, Requirements, etc.)
     * - AI user + "Projek Keuangan Bank DKI" → AI options (Model Training, Feature Engineering, etc.)
     * - Platform user + "Projek Healthcare BPJS" → Platform options (Infrastructure, CI/CD, etc.)
     * 
     * @param string $type The question type (objective, expectation, current_task, etc.)
     * @param array $context Must contain 'department_code' for correct option generation
     * @return array List of contextual options for the question
     */
    public function generateOptions(string $type, array $context = []): array
    {
        // Get department code from context - this is the PRIMARY source of truth
        $departmentCode = $context['department_code'] ?? null;
        
        return match($type) {
            'project_selection' => $this->projectSelectionOptions($context),
            'objective' => $this->objectiveOptions($context, $departmentCode),
            'expectation' => $this->expectationOptions($context, $departmentCode),
            'current_task' => $this->currentTaskOptions($context, $departmentCode),
            'task_detail' => $this->taskDetailOptions($context, $departmentCode),
            'task_challenge' => $this->taskChallengeOptions($context, $departmentCode),
            'task_approach' => $this->taskApproachOptions($context, $departmentCode),
            'estimation' => $this->estimationOptions($context),
            'priority' => $this->priorityOptions($context),
            'other_project' => ['Ya, ada proyek lain', 'Tidak ada proyek lain'],
            'confirmation' => ['Iya', 'Tidak'],
            'method_clarification' => $this->methodClarificationOptions($context),
            default => [],
        };
    }

    /**
     * Detect if project name suggests a specific domain
     */
    private function detectProjectDomain(string $projectName): string
    {
        $projectLower = strtolower($projectName);

        // Healthcare/BPJS
        if (preg_match('/\b(bpjs|hospital|kesehatan|health|medis|klinik|rumah sakit)\b/i', $projectLower)) {
            return 'healthcare';
        }

        // Banking/Finance
        if (preg_match('/\b(bank|bca|mandiri|bni|bri|finance|fintech|payment|pembayaran)\b/i', $projectLower)) {
            return 'finance';
        }

        // E-commerce/Retail
        if (preg_match('/\b(ecommerce|e-commerce|retail|toko|shop|market|tokopedia|shopee|bukalapak|mayora|indofood)\b/i', $projectLower)) {
            return 'ecommerce';
        }

        // Smart City/IoT
        if (preg_match('/\b(smart city|iot|sensor|jakarta|traffic|transportasi|parkir|waste)\b/i', $projectLower)) {
            return 'smartcity';
        }

        // Education
        if (preg_match('/\b(education|pendidikan|sekolah|kampus|universitas|lms|e-learning)\b/i', $projectLower)) {
            return 'education';
        }

        // Manufacturing
        if (preg_match('/\b(manufacturing|pabrik|produksi|factory|quality control|qc)\b/i', $projectLower)) {
            return 'manufacturing';
        }

        // Logistics
        if (preg_match('/\b(logistics|logistik|delivery|pengiriman|warehouse|gudang|supply chain)\b/i', $projectLower)) {
            return 'logistics';
        }

        // Telecommunications
        if (preg_match('/\b(telco|telecom|telkom|indosat|xl|network|jaringan)\b/i', $projectLower)) {
            return 'telecommunications';
        }

        return 'general';
    }

    private function projectSelectionOptions(array $context): array
    {
        // Simplified - only 2 main options
        return ['Proyek Baru', 'Lanjut Proyek Sebelumnya'];
    }

    private function objectiveOptions(array $context, ?string $departmentCode = null): array
    {
        // CRITICAL: ALWAYS prioritize department-specific options if department code exists
        // This ensures BA gets BA options, AI gets AI options, etc.
        // Domain detection is ONLY used as fallback when no department code is available
        if ($departmentCode !== null) {
            return $this->getDepartmentSpecificObjectives($departmentCode);
        }

        // Fallback to domain detection only if no department code
        $projectName = $context['project_name'] ?? '';
        $domain = $this->detectProjectDomain($projectName);

        $options = match($domain) {
            'healthcare' => $this->shuffleOptions([
                'Model Prediksi Klaim Kesehatan',
                'Segmentasi Peserta BPJS',
                'Deteksi Fraud/Anomali Klaim',
                'Dashboard Analytics BPJS',
                'Optimisasi Proses Klaim',
                'Prediksi Utilisasi Layanan',
                'Analisis Pola Penyakit',
            ], 4),
            'finance' => $this->shuffleOptions([
                'Credit Scoring Model',
                'Fraud Detection System',
                'Customer Segmentation',
                'Transaction Monitoring',
                'Risk Assessment',
                'Loan Default Prediction',
                'Investment Portfolio Optimization',
            ], 4),
            'ecommerce' => $this->shuffleOptions([
                'Tracking Pengunjung Toko',
                'Analisis Perilaku Konsumen',
                'Sistem Rekomendasi Produk',
                'Inventory Forecasting',
                'Customer Churn Prediction',
                'Price Optimization',
                'Demand Forecasting',
            ], 4),
            'smartcity' => $this->shuffleOptions([
                'Traffic Flow Prediction',
                'Waste Management Optimization',
                'Smart Parking System',
                'Air Quality Monitoring',
                'Public Transport Analytics',
                'Energy Consumption Prediction',
                'Crime Hotspot Detection',
            ], 4),
            'education' => $this->shuffleOptions([
                'Student Performance Prediction',
                'Personalized Learning Path',
                'Dropout Risk Analysis',
                'Course Recommendation System',
                'Auto Grading System',
                'Learning Analytics Dashboard',
            ], 4),
            'manufacturing' => $this->shuffleOptions([
                'Predictive Maintenance',
                'Quality Control Automation',
                'Production Optimization',
                'Defect Detection System',
                'Supply Chain Forecasting',
                'Equipment Failure Prediction',
            ], 4),
            'logistics' => $this->shuffleOptions([
                'Route Optimization',
                'Delivery Time Prediction',
                'Warehouse Management System',
                'Demand Forecasting',
                'Vehicle Tracking System',
                'Last-Mile Delivery Optimization',
            ], 4),
            'telecommunications' => $this->shuffleOptions([
                'Network Anomaly Detection',
                'Churn Prediction',
                'Customer Sentiment Analysis',
                'Call Quality Monitoring',
                'Bandwidth Optimization',
                'Fault Prediction System',
            ], 4),
            default => $this->shuffleOptions([
                'Automation & Efficiency',
                'Data Analytics & Insights',
                'Predictive Modeling',
                'Process Optimization',
                'Decision Support System',
                'Real-time Monitoring',
            ], 4),
        };

        // Frontend will add inline input "Something else" automatically
        return $options;
    }

    private function expectationOptions(array $context, ?string $departmentCode = null): array
    {
        // CRITICAL: ALWAYS prioritize department-specific options if department code exists
        // This ensures BA gets BA expectations, AI gets AI expectations, etc.
        // Pattern matching is ONLY used as fallback when no department code is available
        if ($departmentCode !== null) {
            $objective = strtolower($context['objective'] ?? '');
            return $this->getDepartmentSpecificExpectations($departmentCode, $objective);
        }

        // Fallback to pattern matching only if no department code
        $objective = strtolower($context['objective'] ?? '');
        $options = [];

        // AI/ML focused expectations
        if (preg_match('/\b(model|prediksi|prediction|detection|segmentasi|classification)\b/i', $objective)) {
            $options = $this->shuffleOptions([
                'Akurasi Model >90%',
                'Akurasi Model >85%',
                'Precision & Recall Seimbang',
                'Model Bisa Production-Ready',
                'Inference Time <100ms',
                'Inference Time <500ms',
                'F1-Score >0.85',
                'F1-Score >0.90',
                'Low False Positive Rate',
                'Model Generalize dengan Baik',
            ], 4);
        }
        // System/Dashboard focused
        else if (preg_match('/\b(dashboard|system|platform|aplikasi|monitoring)\b/i', $objective)) {
            $options = $this->shuffleOptions([
                'Implementasi dalam 3 Bulan',
                'Implementasi dalam 6 Bulan',
                'User-Friendly Interface',
                'Real-time Data Update',
                'Real-time dengan Latency <1s',
                'Scalable Architecture',
                'High Availability 99.9%',
                'High Availability 99.5%',
                'Mobile-Responsive Design',
                'API-Ready untuk Integrasi',
            ], 4);
        }
        // Optimization focused
        else if (preg_match('/\b(optimasi|optimization|efisiensi|efficiency)\b/i', $objective)) {
            $options = $this->shuffleOptions([
                'Efisiensi Proses 50%',
                'Efisiensi Proses 30%',
                'Cost Reduction 30%',
                'Cost Reduction 50%',
                'ROI Positif dalam 6 Bulan',
                'ROI Positif dalam 1 Tahun',
                'Throughput Meningkat 2x',
                'Throughput Meningkat 3x',
                'Resource Usage Turun 40%',
                'Automation 70% Manual Task',
            ], 4);
        }
        // Default general expectations (mixed)
        else {
            $options = $this->shuffleOptions([
                'Prototype untuk Demo',
                'Proof of Concept (PoC)',
                'Minimum Viable Product (MVP)',
                'Full Production Deployment',
                'Pilot Project untuk 1 Divisi',
                'Beta Testing dengan User Terbatas',
                'Implementasi Bertahap',
                'Quick Win dalam 1 Bulan',
                'Scalable untuk Future Growth',
                'Integration dengan System Existing',
            ], 4);
        }

        // Frontend will add inline input "Something else" automatically
        return $options;
    }

    private function currentTaskOptions(array $context, ?string $departmentCode = null): array
    {
        // CRITICAL: ALWAYS prioritize department-specific options if department code exists
        // This ensures each department gets relevant task options based on their role
        // Pattern matching is ONLY used as fallback when no department code is available
        if ($departmentCode !== null) {
            $objective = strtolower($context['objective'] ?? '');
            return $this->getDepartmentSpecificTasks($departmentCode, $objective);
        }

        // Fallback to pattern matching only if no department code
        $objective = strtolower($context['objective'] ?? '');
        $options = [];

        // AI/ML pipeline tasks
        if (preg_match('/\b(model|ai|ml|machine learning|deep learning|prediction)\b/i', $objective)) {
            $options = $this->shuffleOptions([
                'Data Collection & Cleaning',
                'Data Preprocessing',
                'Exploratory Data Analysis (EDA)',
                'Feature Engineering',
                'Feature Selection',
                'Model Training & Tuning',
                'Hyperparameter Tuning',
                'Model Evaluation',
                'Testing & Validation',
                'Cross-Validation',
                'Model Deployment',
                'Model Optimization',
                'Performance Monitoring',
                'A/B Testing Model',
            ], 4);
        }
        // Dashboard/Frontend tasks
        else if (preg_match('/\b(dashboard|ui|interface|frontend|visualization)\b/i', $objective)) {
            $options = $this->shuffleOptions([
                'UI/UX Design',
                'Wireframing & Mockup',
                'Frontend Development',
                'Component Development',
                'Data Visualization',
                'Chart & Graph Implementation',
                'Interactive Components',
                'Responsive Design',
                'User Testing',
                'Usability Testing',
                'Performance Optimization',
                'Accessibility Implementation',
            ], 4);
        }
        // Backend/System tasks
        else if (preg_match('/\b(backend|api|system|database|server)\b/i', $objective)) {
            $options = $this->shuffleOptions([
                'Backend API Development',
                'RESTful API Design',
                'Database Schema Design',
                'Query Optimization',
                'API Integration',
                'Third-party Integration',
                'Authentication & Authorization',
                'Security Implementation',
                'Performance Optimization',
                'Caching Strategy',
                'Load Testing',
                'API Documentation',
            ], 4);
        }
        // Default software development tasks
        else {
            $options = $this->shuffleOptions([
                'Requirement Analysis',
                'System Design',
                'Architecture Design',
                'Implementation/Coding',
                'Code Review',
                'Unit Testing',
                'Integration Testing',
                'Bug Fixing',
                'Refactoring',
                'Documentation',
                'Technical Documentation',
                'Deployment Preparation',
            ], 4);
        }

        // Frontend will add inline input "Something else" automatically
        return $options;
    }

    private function estimationOptions(array $context): array
    {
        $variations = [
            ['1-2 Hari', '3-5 Hari', '1 Minggu', '2 Minggu', '1 Bulan'],
            ['Hari Ini', '2-3 Hari', 'Minggu Ini', 'Bulan Ini', '>1 Bulan'],
            ['<3 Hari', '3-7 Hari', '1-2 Minggu', '2-4 Minggu', '>1 Bulan'],
        ];

        $index = ($context['conversation_id'] ?? 0) % count($variations);
        return $variations[$index];
    }

    private function priorityOptions(array $context): array
    {
        return [
            'Ya, prioritas utama',
            'Ya, tapi ada task parallel',
            'Tidak, ini task sekunder',
            'Menunggu dependency',
        ];
    }

    private function methodClarificationOptions(array $context): array
    {
        $input = strtolower($context['user_input'] ?? '');

        if (preg_match('/\b(tracking|track|monitor)\b/i', $input)) {
            return [
                'Computer Vision & Kamera',
                'RFID & Sensor',
                'Mobile App Check-in',
                'QR Code Scanning',
                'Facial Recognition',
            ];
        }

        if (preg_match('/\b(analisis|analysis|analytics)\b/i', $input)) {
            return [
                'Descriptive Analytics',
                'Predictive Analytics',
                'Prescriptive Analytics',
                'Real-time Analytics',
                'Batch Processing Analytics',
            ];
        }

        return [];
    }

    /**
     * Generate task detail options based on the current task
     */
    private function taskDetailOptions(array $context, ?string $departmentCode = null): array
    {
        // CRITICAL: ALWAYS prioritize department-specific options if department code exists
        // This ensures task details are relevant to the user's actual department role
        // Pattern matching is ONLY used as fallback when no department code is available
        if ($departmentCode !== null) {
            $currentTask = strtolower($context['current_task'] ?? $context['user_input'] ?? '');
            return $this->getDepartmentSpecificTaskDetails($departmentCode, $currentTask);
        }

        // Fallback to pattern matching only if no department code
        $currentTask = strtolower($context['current_task'] ?? $context['user_input'] ?? '');
        $options = [];

        // For Pricing/Revenue Optimization tasks
        if (preg_match('/\b(price|pricing|harga|revenue optimization)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'Algoritma Dynamic Pricing',
                'Feature Engineering untuk Pricing',
                'Data Collection Harga Kompetitor',
                'Model Testing & Validation',
                'Integration dengan System Existing',
                'Rule-based Pricing Logic',
                'Machine Learning Model Selection',
                'A/B Testing Strategy',
                'Pricing Constraints & Business Rules',
                'Real-time Pricing Engine',
            ], 4);
        }
        // For Model Development/Training tasks
        else if (preg_match('/\b(model development|training|machine learning|deep learning)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'Algoritma Selection (RF, XGBoost, Neural Network)',
                'Feature Engineering',
                'Hyperparameter Tuning',
                'Cross-validation Strategy',
                'Model Architecture Design',
                'Training Pipeline Setup',
                'Model Evaluation Metrics',
                'Overfitting Prevention',
                'Data Augmentation',
                'Transfer Learning',
            ], 4);
        }
        // For Data Collection/Preprocessing
        else if (preg_match('/\b(data collection|data cleaning|preprocessing|feature engineering)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'Data Source Identification',
                'Data Quality Assessment',
                'Missing Value Handling',
                'Outlier Detection & Treatment',
                'Feature Extraction',
                'Feature Scaling/Normalization',
                'Data Transformation',
                'Data Integration dari Multiple Sources',
                'Data Validation Rules',
                'Automated Data Pipeline',
            ], 4);
        }
        // For Testing & Validation
        else if (preg_match('/\b(testing|validation|evaluation)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'Unit Testing',
                'Integration Testing',
                'Performance Testing',
                'User Acceptance Testing (UAT)',
                'A/B Testing',
                'Model Accuracy Validation',
                'Edge Case Testing',
                'Load Testing',
                'Regression Testing',
                'Security Testing',
            ], 4);
        }
        // For Deployment/Integration
        else if (preg_match('/\b(deployment|integration|production)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'CI/CD Pipeline Setup',
                'Docker Containerization',
                'API Development',
                'Database Integration',
                'Monitoring & Logging Setup',
                'Performance Optimization',
                'Security Implementation',
                'Rollback Strategy',
                'Load Balancing',
                'Documentation',
            ], 4);
        }
        // For Dashboard/UI tasks
        else if (preg_match('/\b(dashboard|ui|interface|frontend)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'UI/UX Design',
                'Component Development',
                'Data Visualization Charts',
                'Responsive Design',
                'Real-time Data Update',
                'User Interaction Flow',
                'Performance Optimization',
                'Cross-browser Testing',
                'Accessibility Implementation',
                'API Integration',
            ], 4);
        }
        // Generic task detail options
        else {
            $options = $this->shuffleOptions([
                'Planning & Design',
                'Implementation/Coding',
                'Testing & Debugging',
                'Documentation',
                'Code Review',
                'Performance Optimization',
                'Refactoring',
                'Integration dengan System Lain',
                'Troubleshooting',
                'Knowledge Sharing',
            ], 4);
        }

        // Frontend will add inline input "Something else" automatically
        return $options;
    }

    /**
     * Generate task challenge options based on context
     */
    private function taskChallengeOptions(array $context, ?string $departmentCode = null): array
    {
        // CRITICAL: ALWAYS prioritize department-specific options if department code exists
        // This ensures challenges are relevant to the user's department context
        // Pattern matching is ONLY used as fallback when no department code is available
        if ($departmentCode !== null) {
            $currentTask = strtolower($context['current_task'] ?? $context['user_input'] ?? '');
            return $this->getDepartmentSpecificChallenges($departmentCode, $currentTask);
        }

        // Fallback to pattern matching only if no department code
        $currentTask = strtolower($context['current_task'] ?? $context['user_input'] ?? '');
        $options = [];

        // Challenges for technical/development tasks
        if (preg_match('/\b(development|coding|implementation|integration)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'Kompleksitas Teknis Tinggi',
                'Data Quality Issues',
                'Integration dengan System Legacy',
                'Performance Bottleneck',
                'Limited Resources (Hardware/Tools)',
                'Dokumentasi Tidak Lengkap',
                'Dependencies pada Tim Lain',
                'Tight Deadline',
                'Scope Creep',
            ], 4);
        }
        // Challenges for data/ML tasks
        else if (preg_match('/\b(data|model|machine learning|training)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'Data Tidak Cukup/Incomplete',
                'Data Quality Buruk',
                'Imbalanced Dataset',
                'Feature Engineering Sulit',
                'Model Tidak Converge',
                'Overfitting/Underfitting',
                'Computational Resources Terbatas',
                'Long Training Time',
                'Sulitnya Interpretasi Model',
            ], 4);
        }
        // Generic challenges
        else {
            $options = [
                'Keterbatasan Waktu',
                'Keterbatasan Resource',
                'Kompleksitas Teknis',
                'Koordinasi Tim',
            ];
        }

        // Frontend will add inline input "Something else" automatically
        return $options;
    }

    /**
     * Generate task approach options
     */
    private function taskApproachOptions(array $context, ?string $departmentCode = null): array
    {
        // CRITICAL: ALWAYS prioritize department-specific options if department code exists
        // This ensures approaches are relevant to the user's department methodology
        // Pattern matching is ONLY used as fallback when no department code is available
        if ($departmentCode !== null) {
            $currentTask = strtolower($context['current_task'] ?? $context['user_input'] ?? '');
            return $this->getDepartmentSpecificApproaches($departmentCode, $currentTask);
        }

        // Fallback to pattern matching only if no department code
        $currentTask = strtolower($context['current_task'] ?? $context['user_input'] ?? '');
        $options = [];

        // Approach for model/ML tasks
        if (preg_match('/\b(model|machine learning|training|algorithm)\b/i', $currentTask)) {
            $options = $this->shuffleOptions([
                'Incremental Development',
                'Rapid Prototyping',
                'Baseline Model First, then Iterate',
                'Multiple Model Comparison',
                'Transfer Learning Approach',
                'Ensemble Methods',
                'AutoML Tools',
                'Manual Tuning & Optimization',
                'Research-based Approach',
                'Agile/Iterative Development',
            ], 4);
        }
        // Generic approaches
        else {
            $options = $this->shuffleOptions([
                'Agile/Iterative',
                'Waterfall/Sequential',
                'Prototype First',
                'Test-Driven Development (TDD)',
                'Pair Programming',
                'Solo Development',
                'Research & Experiment',
                'Follow Best Practices',
            ], 4);
        }

        // Frontend will add inline input "Something else" automatically
        return $options;
    }

    /**
     * Shuffle and return first N options
     */
    private function shuffleOptions(array $options, int $count = 5): array
    {
        // Remove "Something else" if it exists in the options (we'll add it manually later)
        $options = array_filter($options, fn($opt) => strcasecmp($opt, 'Something else') !== 0);
        
        shuffle($options);
        return array_slice($options, 0, $count);
    }

    /**
     * Parse AI response and detect if it contains JSON options
     */
    public function parseResponse(string $response): array
    {
        // Try to extract JSON from response
        if (preg_match('/\{[^}]*"message"[^}]*"options"[^}]*\}/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'has_options' => true,
                    'message' => $json['message'] ?? $response,
                    'options' => $json['options'] ?? [],
                    'type' => $json['type'] ?? 'unknown',
                ];
            }
        }

        return [
            'has_options' => false,
            'message' => $response,
            'options' => [],
            'type' => 'text',
        ];
    }

    /**
     * Get department-specific objectives
     */
    private function getDepartmentSpecificObjectives(string $departmentCode): array
    {
        return match($departmentCode) {
            'ai' => $this->shuffleOptions([
                'Pengembangan Model Machine Learning',
                'Implementasi Deep Learning',
                'Natural Language Processing (NLP)',
                'Computer Vision System',
                'Predictive Analytics',
                'Model Optimization & Tuning',
                'AI Research & Experimentation',
                'MLOps Implementation',
                'Sentiment Analysis',
                'Recommendation System',
                'Time Series Forecasting',
                'Anomaly Detection',
                'Speech Recognition',
                'Image Classification',
                'Text Generation',
            ], 4),
            'platform' => $this->shuffleOptions([
                'Infrastructure Setup & Configuration',
                'CI/CD Pipeline Development',
                'Container Orchestration (Kubernetes)',
                'Cloud Migration & Optimization',
                'Monitoring & Logging System',
                'Security & Compliance Implementation',
                'Performance Optimization',
                'Disaster Recovery Planning',
                'Load Balancing Setup',
                'Database Infrastructure',
                'Microservices Architecture',
                'API Gateway Implementation',
                'Service Mesh Setup',
                'Auto-Scaling Configuration',
                'Backup & Restore Strategy',
            ], 4),
            'ba' => $this->shuffleOptions([
                'Requirements Gathering & Analysis',
                'Business Process Analysis',
                'Proposal Development',
                'Stakeholder Management',
                'Data Analysis & Reporting',
                'System Documentation',
                'Gap Analysis',
                'Process Improvement Initiative',
                'Feasibility Study',
                'Business Case Development',
                'ROI Analysis',
                'User Story Mapping',
                'Process Automation Planning',
                'Change Management',
                'Market Research',
            ], 4),
            'td' => $this->shuffleOptions([
                'Sprint Planning & Execution',
                'Project Timeline Management',
                'Resource Allocation & Planning',
                'Risk Assessment & Mitigation',
                'Delivery Coordination',
                'Team Performance Tracking',
                'Agile Implementation',
                'Quality Assurance Planning',
                'Release Management',
                'Backlog Prioritization',
                'Cross-Team Coordination',
                'Velocity Optimization',
                'Technical Debt Management',
                'Delivery Pipeline Setup',
                'Team Capacity Planning',
            ], 4),
            default => $this->shuffleOptions([
                'Automation & Efficiency',
                'Data Analytics & Insights',
                'Predictive Modeling',
                'Process Optimization',
                'Decision Support System',
                'Real-time Monitoring',
            ], 4),
        };
    }

    /**
     * Get department-specific expectations
     */
    private function getDepartmentSpecificExpectations(string $departmentCode, string $objective): array
    {
        return match($departmentCode) {
            'ai' => $this->shuffleOptions([
                'Model Accuracy >90%',
                'Model Accuracy >85%',
                'Training Time <4 Hours',
                'Training Time <8 Hours',
                'Inference Time <100ms',
                'Inference Time <50ms',
                'F1-Score >0.85',
                'F1-Score >0.90',
                'Low False Positive Rate <5%',
                'Production-Ready Model',
                'Scalable Model Architecture',
                'Model Size <100MB',
                'Precision >90%',
                'Recall >85%',
                'AUC-ROC >0.9',
            ], 4),
            'platform' => $this->shuffleOptions([
                'Zero Downtime Deployment',
                '99.9% Uptime (Three Nines)',
                '99.99% Uptime (Four Nines)',
                'Auto-Scaling Ready',
                'Cost Reduction 30%',
                'Cost Reduction 50%',
                'Deployment Time <15 Minutes',
                'Deployment Time <5 Minutes',
                'Full CI/CD Automation',
                'Monitoring Dashboard Complete',
                'Security Compliance Met',
                'Response Time <200ms',
                'Build Time <10 Minutes',
                'Container Start Time <30s',
                'Disaster Recovery RTO <1 Hour',
            ], 4),
            'ba' => $this->shuffleOptions([
                'Approval dalam 2 Minggu',
                'Approval dalam 1 Bulan',
                'Stakeholder Buy-in 100%',
                'Stakeholder Buy-in >80%',
                'Proposal Accepted',
                'Budget Approval Granted',
                'Clear Requirements Document',
                'ROI Positive dalam 6 Bulan',
                'ROI Positive dalam 1 Tahun',
                'Process Efficiency +40%',
                'Process Efficiency +30%',
                'Comprehensive Documentation',
                'User Satisfaction >85%',
                'Cost Savings 25%',
                'Time Savings 50%',
            ], 4),
            'td' => $this->shuffleOptions([
                'On-Time Delivery',
                'Early Delivery (Ahead of Schedule)',
                'Zero Critical Bugs',
                'Bug Rate <5%',
                'Team Velocity +20%',
                'Team Velocity +30%',
                'Sprint Success Rate >90%',
                'Sprint Success Rate >95%',
                'Customer Satisfaction >4.5/5',
                'Customer Satisfaction >4.0/5',
                'Resource Utilization 85%',
                'Resource Utilization >90%',
                'Delivery within Budget',
                'Quality Gate Passed 100%',
                'Code Coverage >80%',
            ], 4),
            default => $this->shuffleOptions([
                'Prototype untuk Demo',
                'Proof of Concept (PoC)',
                'Minimum Viable Product (MVP)',
                'Full Production Deployment',
                'Pilot Project untuk 1 Divisi',
                'Beta Testing dengan User Terbatas',
                'Implementasi Bertahap',
                'Quick Win dalam 1 Bulan',
                'Scalable untuk Future Growth',
                'Integration dengan System Existing',
            ], 4),
        };
    }

    /**
     * Get department-specific tasks
     */
    private function getDepartmentSpecificTasks(string $departmentCode, string $objective): array
    {
        return match($departmentCode) {
            'ai' => $this->shuffleOptions([
                'Data Collection & Preprocessing',
                'Exploratory Data Analysis (EDA)',
                'Feature Engineering & Selection',
                'Model Selection & Architecture Design',
                'Training & Hyperparameter Tuning',
                'Model Evaluation & Validation',
                'Model Deployment & Serving',
                'Performance Monitoring',
                'Research & Experimentation',
                'Data Labeling & Annotation',
                'Model Versioning',
                'A/B Testing Setup',
                'Pipeline Optimization',
                'Documentation & Reporting',
            ], 4),
            'platform' => $this->shuffleOptions([
                'Infrastructure Provisioning',
                'CI/CD Pipeline Setup & Configuration',
                'Container Configuration & Management',
                'Load Balancer Configuration',
                'Monitoring Tools Integration',
                'Security Hardening & Audit',
                'Backup & Recovery Setup',
                'Performance Testing & Tuning',
                'Network Configuration',
                'Database Setup & Optimization',
                'Certificate Management',
                'Log Aggregation Setup',
                'Alert Configuration',
                'Infrastructure as Code Development',
            ], 4),
            'ba' => $this->shuffleOptions([
                'Stakeholder Interview & Workshop',
                'Requirements Documentation',
                'Business Case Development',
                'Proposal Writing & Presentation',
                'Data Collection & Analysis',
                'Process Mapping & Flowchart',
                'Gap Analysis & Recommendation',
                'Presentation Preparation',
                'User Research & Survey',
                'Cost-Benefit Analysis',
                'Risk Assessment',
                'Competitive Analysis',
                'User Acceptance Testing Planning',
                'Change Impact Analysis',
            ], 4),
            'td' => $this->shuffleOptions([
                'Sprint Planning & Setup',
                'Backlog Refinement & Prioritization',
                'Resource Planning & Assignment',
                'Risk Assessment & Planning',
                'Progress Tracking & Reporting',
                'Team Coordination & Standup',
                'Quality Assurance Review',
                'Delivery Preparation & Release',
                'Retrospective Facilitation',
                'Velocity Analysis',
                'Blocker Resolution',
                'Stakeholder Communication',
                'Timeline Adjustment',
                'Capacity Planning',
            ], 4),
            default => $this->shuffleOptions([
                'Requirement Analysis',
                'System Design',
                'Implementation/Coding',
                'Testing & Debugging',
                'Documentation',
                'Deployment Preparation',
            ], 4),
        };
    }

    /**
     * Get department-specific task details
     */
    private function getDepartmentSpecificTaskDetails(string $departmentCode, string $currentTask): array
    {
        return match($departmentCode) {
            'ai' => $this->shuffleOptions([
                'Algorithm Selection (RF, XGBoost, Neural Net)',
                'Data Quality Check & Validation',
                'Feature Importance Analysis',
                'Model Architecture Design',
                'Loss Function Selection',
                'Evaluation Metrics Setup',
                'Cross-Validation Strategy',
                'GPU/TPU Configuration',
                'Batch Size Tuning',
                'Learning Rate Optimization',
                'Data Augmentation',
                'Transfer Learning Setup',
                'Model Ensemble Strategy',
                'Overfitting Prevention',
            ], 4),
            'platform' => $this->shuffleOptions([
                'Infrastructure as Code (Terraform/Ansible)',
                'Container Image Building & Optimization',
                'Network Configuration & Subnetting',
                'Security Group & Firewall Setup',
                'Log Aggregation Setup (ELK/Splunk)',
                'Alert Configuration & Routing',
                'Scaling Policy Definition',
                'Backup Strategy & Testing',
                'SSL/TLS Certificate Management',
                'DNS Configuration',
                'Storage Configuration',
                'Service Mesh Implementation',
                'Secret Management',
                'Load Testing Configuration',
            ], 4),
            'ba' => $this->shuffleOptions([
                'Stakeholder Identification & Mapping',
                'Data Gathering & Validation',
                'Process Flow Documentation',
                'Cost-Benefit Analysis',
                'Risk Analysis & Mitigation Plan',
                'Timeline Estimation & Milestones',
                'Success Metrics Definition (KPIs)',
                'Presentation Slides & Materials',
                'User Interview Script',
                'Survey Design & Distribution',
                'Requirement Prioritization (MoSCoW)',
                'Use Case Documentation',
                'Business Model Canvas',
                'Value Proposition Design',
            ], 4),
            'td' => $this->shuffleOptions([
                'Task Breakdown & User Stories',
                'Story Point Estimation (Planning Poker)',
                'Dependency Mapping & Critical Path',
                'Resource Assignment & Availability',
                'Milestone Definition & Tracking',
                'Blocker Identification & Resolution',
                'Team Capacity Planning',
                'Acceptance Criteria Review',
                'Sprint Goal Definition',
                'Velocity Calculation',
                'Burndown Chart Analysis',
                'Risk Register Update',
                'Definition of Done (DoD)',
                'Team Retrospective Facilitation',
            ], 4),
            default => $this->shuffleOptions([
                'Planning & Design',
                'Implementation/Coding',
                'Testing & Debugging',
                'Documentation',
                'Code Review',
                'Performance Optimization',
            ], 4),
        };
    }

    /**
     * Get department-specific challenges
     */
    private function getDepartmentSpecificChallenges(string $departmentCode, string $currentTask): array
    {
        return match($departmentCode) {
            'ai' => $this->shuffleOptions([
                'Data Quality Issues (Missing/Inconsistent)',
                'Imbalanced Dataset Problem',
                'Model Overfitting',
                'Model Underfitting',
                'Long Training Time (>24 Hours)',
                'Limited Computational Resources',
                'Feature Selection Complexity',
                'Model Interpretability Issues',
                'Deployment Complexity',
                'Data Privacy Concerns',
                'Insufficient Training Data',
                'Hyperparameter Tuning Challenges',
                'Production Latency Issues',
                'Model Drift Detection',
            ], 4),
            'platform' => $this->shuffleOptions([
                'Legacy System Compatibility',
                'Network Latency Issues',
                'Security Vulnerabilities',
                'Resource Constraints (CPU/Memory)',
                'Configuration Complexity',
                'High Availability Requirements',
                'Cost Management & Budget',
                'Compliance Requirements',
                'Vendor Lock-in Concerns',
                'Data Migration Challenges',
                'Downtime Window Limitations',
                'Performance Bottlenecks',
                'Integration Complexity',
                'Documentation Gaps',
            ], 4),
            'ba' => $this->shuffleOptions([
                'Stakeholder Alignment Issues',
                'Incomplete Requirements',
                'Budget Constraints',
                'Timeline Pressure',
                'Data Unavailability',
                'Conflicting Priorities',
                'Change Resistance from Users',
                'Communication Gaps',
                'Scope Creep',
                'Unclear Success Metrics',
                'Multiple Stakeholder Interests',
                'Regulatory Constraints',
                'Vendor Dependencies',
                'Technical Feasibility Concerns',
            ], 4),
            'td' => $this->shuffleOptions([
                'Resource Bottlenecks',
                'Scope Creep',
                'Technical Dependencies',
                'Team Capacity Issues',
                'Priority Changes',
                'Cross-Team Coordination',
                'Quality vs Speed Tradeoff',
                'External Dependencies',
                'Technical Debt Accumulation',
                'Knowledge Gaps in Team',
                'Unclear Requirements',
                'Communication Overhead',
                'Testing Environment Issues',
                'Integration Delays',
            ], 4),
            default => $this->shuffleOptions([
                'Keterbatasan Waktu',
                'Keterbatasan Resource',
                'Kompleksitas Teknis',
                'Koordinasi Tim',
            ], 4),
        };
    }

    /**
     * Get department-specific approaches
     */
    private function getDepartmentSpecificApproaches(string $departmentCode, string $currentTask): array
    {
        return match($departmentCode) {
            'ai' => $this->shuffleOptions([
                'Incremental Model Development',
                'Transfer Learning from Pre-trained Models',
                'Ensemble Methods (Bagging/Boosting)',
                'AutoML Tools (H2O/Auto-sklearn)',
                'Baseline then Iterate',
                'Multiple Model Comparison',
                'Research-based Approach',
                'Experiment-Driven Development',
                'Cross-Validation Strategy',
                'Feature Engineering First',
                'Data-Centric Approach',
                'Model-Centric Approach',
                'Hyperparameter Optimization (Optuna)',
                'Active Learning',
            ], 4),
            'platform' => $this->shuffleOptions([
                'Infrastructure as Code (IaC)',
                'GitOps Workflow',
                'Blue-Green Deployment',
                'Canary Deployment',
                'Incremental Rollout',
                'Test-Driven Infrastructure',
                'Automated Testing & Validation',
                'Monitoring-First Approach',
                'Immutable Infrastructure',
                'Container-First Strategy',
                'Microservices Architecture',
                'Service Mesh Pattern',
                'Configuration Management',
                'Disaster Recovery Testing',
            ], 4),
            'ba' => $this->shuffleOptions([
                'Stakeholder-Centric Approach',
                'Data-Driven Analysis',
                'Iterative Refinement',
                'Collaborative Workshops',
                'Agile Documentation',
                'Prototype-First Approach',
                'Incremental Validation',
                'Best Practice Benchmarking',
                'User-Centered Design',
                'Design Thinking',
                'Lean Canvas Approach',
                'SWOT Analysis',
                'Process Mining',
                'Gap Analysis Framework',
            ], 4),
            'td' => $this->shuffleOptions([
                'Agile/Scrum Methodology',
                'Kanban Approach',
                'Waterfall with Milestones',
                'Hybrid Approach (Scrumban)',
                'Incremental Delivery',
                'Risk-First Planning',
                'MVP-Focused Approach',
                'Iterative Development',
                'Continuous Integration',
                'Feature Flag Strategy',
                'Parallel Development Tracks',
                'Time-Boxed Sprints',
                'Rolling Wave Planning',
                'Critical Path Method',
            ], 4),
            default => $this->shuffleOptions([
                'Agile/Iterative',
                'Waterfall/Sequential',
                'Prototype First',
                'Test-Driven Development',
            ], 4),
        };
    }
}
