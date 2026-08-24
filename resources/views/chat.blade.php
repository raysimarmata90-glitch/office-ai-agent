@extends('layouts.app')

@section('title', 'Chat - ' . $conversation->department->name)
@section('page-title', 'Chat')

@section('content')
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.3s ease-out;
        }

        .animate-pulse-slow {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        #quick-options button {
            animation: fadeInUp 0.3s ease-out;
            animation-fill-mode: both;
        }

        #quick-options button:nth-child(1) { animation-delay: 0.05s; }
        #quick-options button:nth-child(2) { animation-delay: 0.1s; }
        #quick-options button:nth-child(3) { animation-delay: 0.15s; }
        #quick-options button:nth-child(4) { animation-delay: 0.2s; }
        #quick-options button:nth-child(5) { animation-delay: 0.25s; }
        #quick-options button:nth-child(6) { animation-delay: 0.3s; }

        .message-bubble {
            animation: fadeInUp 0.4s ease-out;
        }
    </style>

    <div class="h-screen flex flex-col bg-gradient-to-b from-gray-50 to-white">
        <!-- Messages Container -->
        <div id="messages-container" class="flex-1 overflow-y-auto px-6 py-8 pb-24">
            <div class="max-w-4xl mx-auto space-y-4 mt-12">
                @foreach ($conversation->messages as $message)
                    <div class="message-bubble flex {{ $message->isFromUser() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-xl">
                            <div
                                class="bg-{{ $message->isFromUser() ? 'indigo-600' : 'white' }} text-{{ $message->isFromUser() ? 'white' : 'gray-900' }} rounded-lg px-4 py-3 shadow">
                                <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>
                                <p class="text-xs mt-1 {{ $message->isFromUser() ? 'text-indigo-200' : 'text-gray-400' }}">
                                    {{ $message->created_at->format('H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Message Input -->
        @if ($conversation->isActive())
            <div class="px-6 py-6 pb-8 border-t border-gray-200 bg-white">
                <div class="max-w-4xl mx-auto">
                    <!-- Question Progress -->
                    <div id="question-progress" class="hidden mb-3 flex items-center justify-between text-sm text-gray-500">
                        <span id="progress-text">1 of 3</span>
                        <button type="button" id="skip-button" 
                            class="text-gray-600 hover:text-gray-800 transition-colors">
                            Skip
                        </button>
                    </div>

                    <!-- Quick Options Container -->
                    <div id="quick-options" class="hidden mb-4 space-y-2"></div>

                    <!-- Direct Answer Form -->
                    <form id="message-form" class="flex items-center gap-3">
                        @csrf
                        <div id="custom-answer-wrapper" class="hidden flex-1 relative">
                            <input type="text" id="message-input" placeholder="Or reply directly..."
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-gray-900 placeholder-gray-400">
                            <button type="submit" id="send-button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-indigo-600 hover:text-indigo-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="px-6 py-6 pb-8">
                <div class="max-w-4xl mx-auto text-center text-yellow-800 bg-yellow-50 rounded-lg py-4">
                    <i class="fas fa-info-circle mr-2"></i>
                    Percakapan ini sudah selesai.
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            const conversationId = {{ $conversation->id }};
            const messagesContainer = document.getElementById('messages-container');
            const messageForm = document.getElementById('message-form');
            const messageInput = document.getElementById('message-input');
            const sendButton = document.getElementById('send-button');
            const quickOptions = document.getElementById('quick-options');
            const customAnswerWrapper = document.getElementById('custom-answer-wrapper');
            const questionProgress = document.getElementById('question-progress');
            const progressText = document.getElementById('progress-text');
            const skipButton = document.getElementById('skip-button');

            let currentQuestionIndex = 0;
            let totalQuestions = 0;

            // Scroll to bottom on load
            scrollToBottom();

            function scrollToBottom() {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            function escapeHtml(value) {
                return value.replace(/[&<>'"]/g, character => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#039;',
                    '"': '&quot;'
                } [character]));
            }

            function optionsForQuestion(content) {
                const question = content.toLowerCase();

                // ===== PROPOSAL SKILL - Langkah 1: Klasifikasi Proposal =====
                
                // Jenis Proposal
                if (/(jenis|tipe|kategori).*proposal|proposal.*(jenis|tipe|kategori)/i.test(question)) {
                    return [
                        'Business Proposal',
                        'Project Proposal',
                        'Research Proposal',
                        'Event Proposal',
                        'Partnership Proposal',
                        'Internal Company Proposal'
                    ];
                }

                // Tujuan Proposal
                if (/(tujuan|dibuat untuk|peruntukan|keperluan).*proposal|proposal.*(tujuan|dibuat untuk)/i.test(question)) {
                    return [
                        'Meminta approval',
                        'Meminta budget',
                        'Meminta resource',
                        'Menawarkan solusi',
                        'Menawarkan kerja sama',
                        'Mengajukan proyek',
                        'Mengajukan penelitian',
                        'Melakukan improvement'
                    ];
                }

                // Kompleksitas Proposal
                if (/(kompleksitas|tingkat kompleks|scope.*proposal)/i.test(question)) {
                    return [
                        'Simple — scope kecil, stakeholder terbatas, risiko rendah',
                        'Medium — beberapa stakeholder, resource, timeline, dan risiko',
                        'Complex — banyak stakeholder, dampak besar, budget signifikan'
                    ];
                }

                // ===== PROPOSAL SKILL - Langkah 2: Target Audience =====

                // Primary Audience
                if (/(primary audience|target audience|audience|pihak.*membaca|target.*pembaca|siapa.*membaca)/i.test(question)) {
                    return [
                        'Direksi / Executive',
                        'Management / Manager',
                        'Client',
                        'Sponsor',
                        'Investor',
                        'Technical Team',
                        'Internal Team',
                        'Dosen / Akademik'
                    ];
                }

                // Kedalaman Proposal
                if (/(kedalaman|proposal depth|tingkat detail|level.*proposal)/i.test(question)) {
                    return [
                        'Executive Level — strategic value, business impact, investment',
                        'Management Level — problem, solution, scope, timeline, budget',
                        'Operational Level — detail requirement, implementation, technical scope'
                    ];
                }

                // ===== PROPOSAL SKILL - Langkah 3: Gali Informasi =====

                // Background / Business Context
                if (/(background|konteks|latar belakang|situasi.*saat ini)/i.test(question) && !/(sudah|benar|sesuai)/i.test(question)) {
                    return [
                        'Ada masalah dalam proses bisnis saat ini',
                        'Perlu digitalisasi sistem manual',
                        'Ada opportunity untuk improvement',
                        'Kebutuhan dari stakeholder',
                        'Respon terhadap perubahan market'
                    ];
                }

                // Problem Statement
                if (/(masalah|problem|kendala|issue|tantangan).*(?:apa|yang)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
                    return [
                        'Proses manual memakan waktu lama',
                        'Data tidak terintegrasi',
                        'Biaya operasional tinggi',
                        'Kualitas hasil tidak konsisten',
                        'Tidak ada visibility real-time'
                    ];
                }

                // Objectives
                if (/(objektif|tujuan|goal|target.*capai|hasil.*ingin)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
                    return [
                        'Meningkatkan efisiensi operasional',
                        'Mengurangi biaya operasional',
                        'Meningkatkan kualitas output',
                        'Mempercepat proses bisnis',
                        'Meningkatkan customer satisfaction'
                    ];
                }

                // Methodology / Approach
                if (/(metodologi|approach|cara.*kerja|bagaimana.*dikerjakan)/i.test(question)) {
                    return [
                        'Agile / Scrum',
                        'Waterfall',
                        'Hybrid Approach',
                        'Phased Implementation',
                        'Proof of Concept dulu'
                    ];
                }

                // Timeline
                if (/(timeline|jadwal|waktu.*pengerjaan|berapa lama|durasi.*proyek)/i.test(question)) {
                    return [
                        '1-2 bulan',
                        '3-4 bulan',
                        '5-6 bulan',
                        '6-12 bulan',
                        'Lebih dari 1 tahun'
                    ];
                }

                // Budget Range
                if (/(budget|anggaran|biaya|estimasi.*cost)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
                    return [
                        'Kurang dari Rp 50 juta',
                        'Rp 50-100 juta',
                        'Rp 100-500 juta',
                        'Rp 500 juta - 1 miliar',
                        'Lebih dari Rp 1 miliar'
                    ];
                }

                // Resources Needed
                if (/(resource|sumber daya|tim.*butuh|kebutuhan.*tim)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
                    return [
                        'Tim internal saja',
                        'Tim internal + vendor',
                        'Full outsource ke vendor',
                        'Consultant + tim internal',
                        'Mixed team (internal + external)'
                    ];
                }

                // Deliverables Type
                if (/(deliverable|output|hasil.*konkret|yang.*dihasilkan)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
                    return [
                        'Software / Aplikasi',
                        'Dokumen / Report',
                        'Dashboard / Analytics',
                        'System / Infrastructure',
                        'Training / Knowledge Transfer'
                    ];
                }

                // Risk Level
                if (/(risk|risiko|potential.*issue|tantangan.*proyek)/i.test(question) && !/(sudah|sesuai)/i.test(question)) {
                    return [
                        'Low Risk — minimal impact jika gagal',
                        'Medium Risk — perlu mitigation plan',
                        'High Risk — perlu executive oversight'
                    ];
                }

                // ===== PROPOSAL SKILL - Konfirmasi =====

                // Konfirmasi Klasifikasi / Audience / Pemahaman
                if (/(klasifikasi|audience|pemahaman|struktur|outline).*sudah (sesuai|benar)/i.test(question)) {
                    return ['Ya, sudah sesuai', 'Belum sesuai, perlu revisi'];
                }

                // Konfirmasi Umum
                if (/(sudah sesuai|sudah benar|apakah.*benar|konfirmasi|setuju.*dengan)/i.test(question)) {
                    return ['Ya, sudah sesuai', 'Belum sesuai'];
                }

                // ===== PROPOSAL SKILL - Format Output =====

                // Format Output
                if (/(format.*(?:keluaran|output|dokumen|proposal)|output.*format|bentuk.*dokumen)/i.test(question)) {
                    return [
                        'Markdown',
                        'Microsoft Word (.docx)',
                        'PowerPoint (.pptx)',
                        'PDF'
                    ];
                }

                // ===== PROJECT TRACKING (Original) =====

                // Prioritas
                if (/(prioritas|prioritas utama|fokus.*utama)/i.test(question) && !/format/i.test(question)) {
                    return ['Ya, ini prioritas utama saya', 'Bukan prioritas utama'];
                }

                // Proyek Lain
                if (/(proyek lain|project.*lain|pekerjaan.*lain)/i.test(question)) {
                    return ['Ya, ada proyek lain', 'Tidak, tidak ada proyek lain'];
                }

                // Estimasi Durasi (untuk tracking proyek)
                if (/(estimasi|berapa lama|durasi|waktu.*selesai).*(?:task|pekerjaan|ini)/i.test(question) && !/(proyek|proposal)/i.test(question)) {
                    return [
                        '1-2 hari',
                        '3-5 hari',
                        '1-2 minggu',
                        '2-4 minggu',
                        'Lebih dari 1 bulan'
                    ];
                }

                // Tidak ada options yang cocok
                return [];
            }

            function showOptions(content) {
                if (!quickOptions || !conversationIsActive()) return;

                const options = optionsForQuestion(content);
                if (options.length === 0) {
                    // No predefined options - show direct input
                    quickOptions.innerHTML = '';
                    quickOptions.classList.add('hidden');
                    questionProgress.classList.add('hidden');
                    customAnswerWrapper.classList.remove('hidden');
                    messageInput.required = true;
                    messageInput.focus();
                    return;
                }

                // Update progress
                currentQuestionIndex++;
                totalQuestions = Math.max(totalQuestions, currentQuestionIndex);
                progressText.textContent = `${currentQuestionIndex} of ${totalQuestions}`;
                questionProgress.classList.remove('hidden');

                // Build numbered options
                quickOptions.innerHTML = options.map((option, index) => `
                    <button type="button" 
                        data-value="${escapeHtml(option)}"
                        class="quick-option group relative flex items-start gap-3 w-full rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <span class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-600 text-sm font-medium group-hover:bg-indigo-100 group-hover:text-indigo-700">
                            ${index + 1}
                        </span>
                        <span class="flex-1 text-sm text-gray-800 group-hover:text-indigo-700 pt-0.5">
                            ${escapeHtml(option)}
                        </span>
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-400 opacity-0 group-hover:opacity-100 group-hover:text-indigo-600 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                `).join('');

                // Add "Something else" option
                quickOptions.insertAdjacentHTML('beforeend', `
                    <button type="button" id="other-option" 
                        class="quick-option group relative flex items-center gap-3 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <svg class="flex-shrink-0 w-5 h-5 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        <span class="flex-1 text-sm text-gray-600 group-hover:text-indigo-700">
                            Something else
                        </span>
                    </button>
                `);

                quickOptions.classList.remove('hidden');
                customAnswerWrapper.classList.add('hidden');

                // Attach click handlers
                quickOptions.querySelectorAll('.quick-option').forEach(button => {
                    button.addEventListener('click', () => {
                        if (button.id === 'other-option') {
                            // Show custom input
                            customAnswerWrapper.classList.remove('hidden');
                            messageInput.required = true;
                            messageInput.focus();
                            // Keep options visible but fade them
                            quickOptions.style.opacity = '0.5';
                            return;
                        }

                        const value = button.getAttribute('data-value');
                        submitMessage(value);
                    });
                });
            }

            function conversationIsActive() {
                return {{ $conversation->isActive() ? 'true' : 'false' }};
            }

            function addMessage(content, isUser) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message-bubble flex ${isUser ? 'justify-end' : 'justify-start'}`;

                const now = new Date();
                const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

                messageDiv.innerHTML = `
            <div class="max-w-xl">
                <div class="bg-${isUser ? 'indigo-600' : 'white'} text-${isUser ? 'white' : 'gray-900'} rounded-lg px-4 py-3 shadow-sm">
                    <p class="text-sm whitespace-pre-wrap">${escapeHtml(content)}</p>
                    <p class="text-xs mt-1 ${isUser ? 'text-indigo-200' : 'text-gray-400'}">
                        ${time}
                    </p>
                </div>
            </div>
        `;

                messagesContainer.querySelector('.space-y-4').appendChild(messageDiv);
                if (!isUser) showOptions(content);
                scrollToBottom();
            }

            async function submitMessage(message) {
                if (!message) return;

                // Disable input and buttons
                messageInput.disabled = true;
                sendButton.disabled = true;
                skipButton.disabled = true;
                quickOptions.classList.add('hidden');
                questionProgress.classList.add('hidden');
                customAnswerWrapper.classList.add('hidden');
                quickOptions.style.opacity = '1';

                // Add user message to UI
                addMessage(message, true);

                // Add loading indicator
                const loadingDiv = document.createElement('div');
                loadingDiv.id = 'loading-indicator';
                loadingDiv.className = 'message-bubble flex justify-start';
                loadingDiv.innerHTML = `
                    <div class="max-w-xl">
                        <div class="bg-white text-gray-900 rounded-lg px-4 py-3 shadow-sm">
                            <div class="flex space-x-2">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse-slow"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse-slow" style="animation-delay: 0.2s"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-pulse-slow" style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </div>
                `;
                messagesContainer.querySelector('.space-y-4').appendChild(loadingDiv);
                scrollToBottom();

                // Clear input
                messageInput.value = '';

                try {
                    const response = await fetch(`/conversations/${conversationId}/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            message
                        }),
                    });

                    const data = await response.json();

                    // Remove loading indicator
                    const loadingIndicator = document.getElementById('loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.remove();
                    }

                    if (data.success) {
                        // Wait a bit for AI response to be saved
                        setTimeout(async () => {
                            // Fetch new messages
                            const messagesResponse = await fetch(
                                `/conversations/${conversationId}/messages`);
                            const messages = await messagesResponse.json();

                            // Get the last message (AI response)
                            const lastMessage = messages[messages.length - 1];
                            if (lastMessage && lastMessage.sender_type === 'ai') {
                                addMessage(lastMessage.content, false);
                            }

                            // Re-enable input and buttons
                            messageInput.disabled = false;
                            sendButton.disabled = false;
                            skipButton.disabled = false;
                            messageInput.focus();
                        }, 1000);
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    
                    // Remove loading indicator
                    const loadingIndicator = document.getElementById('loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.remove();
                    }
                    
                    alert('Terjadi kesalahan saat mengirim pesan');
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                    skipButton.disabled = false;
                }
            }

            // Skip button handler
            skipButton.addEventListener('click', () => {
                submitMessage('Skip');
            });

            // Message form submit
            messageForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await submitMessage(messageInput.value.trim());
            });

            const initialAiMessages = messagesContainer.querySelectorAll('.justify-start .bg-white p:first-child');
            if (initialAiMessages.length) {
                showOptions(initialAiMessages[initialAiMessages.length - 1].textContent);
            }

            // Focus input on load
            if (messageInput) messageInput.focus();
        </script>
    @endpush
@endsection
