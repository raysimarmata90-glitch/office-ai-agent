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

            0%,
            100% {
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

        #quick-options button:nth-child(1) {
            animation-delay: 0.05s;
        }

        #quick-options button:nth-child(2) {
            animation-delay: 0.1s;
        }

        #quick-options button:nth-child(3) {
            animation-delay: 0.15s;
        }

        #quick-options button:nth-child(4) {
            animation-delay: 0.2s;
        }

        #quick-options button:nth-child(5) {
            animation-delay: 0.25s;
        }

        #quick-options button:nth-child(6) {
            animation-delay: 0.3s;
        }

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
                        @php
                            $hasOptionsInMessage =
                                !$message->isFromUser() &&
                                isset($message->metadata['has_options']) &&
                                $message->metadata['has_options'];
                        @endphp
                        <div
                            class="{{ $message->isFromUser() ? 'max-w-md' : ($hasOptionsInMessage ? 'max-w-xl w-full' : 'max-w-xl') }}">
                            <div
                                class="bg-{{ $message->isFromUser() ? 'indigo-600' : 'white' }} text-{{ $message->isFromUser() ? 'white' : 'gray-900' }} rounded-lg px-4 py-3 shadow {{ !$hasOptionsInMessage ? 'inline-block' : '' }}">
                                <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>
                                <p class="text-xs mt-1 {{ $message->isFromUser() ? 'text-indigo-200' : 'text-gray-400' }}">
                                    {{ $message->created_at->format('H:i') }}
                                </p>
                            </div>

                            @if (
                                !$message->isFromUser() &&
                                    isset($message->metadata['has_options']) &&
                                    $message->metadata['has_options'] &&
                                    !empty($message->metadata['options']))
                                <!-- Options attached to AI message -->
                                <div class="mt-3 space-y-2">
                                    @php
                                        $displayOptions = collect($message->metadata['options'])
                                            ->reject(
                                                fn($option) => strcasecmp(trim((string) $option), 'something else') ===
                                                    0,
                                            )
                                            ->values();
                                    @endphp
                                    @foreach ($displayOptions as $index => $option)
                                        <button type="button"
                                            class="message-option w-full group flex items-start gap-3 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            data-value="{{ $option }}" onclick="selectOption(this)">
                                            <span
                                                class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-600 text-sm font-medium group-hover:bg-indigo-100 group-hover:text-indigo-700">
                                                {{ $index + 1 }}
                                            </span>
                                            <span class="flex-1 text-sm text-gray-800 group-hover:text-indigo-700 pt-0.5">
                                                {{ $option }}
                                            </span>
                                            <svg class="flex-shrink-0 w-5 h-5 text-gray-400 opacity-0 group-hover:opacity-100 group-hover:text-indigo-600 transition-opacity"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    @endforeach

                                    @if (in_array(
                                            $message->metadata['question_type'] ?? null,
                                            ['objective', 'expectation', 'current_task', 'task_detail', 'task_challenge', 'task_approach'],
                                            true))
                                        <!-- Something else option with inline input -->
                                        <div class="message-option-other-wrapper">
                                            <!-- Button state -->
                                            <button type="button"
                                                class="message-option-other w-full group flex items-center gap-3 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                onclick="activateInlineInput(this)">
                                                <svg class="flex-shrink-0 w-5 h-5 text-gray-400 group-hover:text-indigo-600"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                    </path>
                                                </svg>
                                                <span class="flex-1 text-sm text-gray-600 group-hover:text-indigo-700">
                                                    Something else
                                                </span>
                                            </button>

                                            <!-- Input state (hidden initially) -->
                                            <div class="inline-input-form hidden">
                                                <form class="flex items-center gap-2"
                                                    onsubmit="submitInlineInput(event, this)">
                                                    <div class="flex-1 relative">
                                                        <input type="text"
                                                            class="inline-input w-full px-4 py-3 pr-12 border-2 border-indigo-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-gray-900 placeholder-gray-400"
                                                            placeholder="Type your answer..." required>
                                                        <button type="submit"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-indigo-600 hover:text-indigo-700 focus:outline-none">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @elseif(!$message->isFromUser() && $loop->last && $conversation->isActive())
                                <!-- Fallback: Generate options for last message if no options exist -->
                                <div class="mt-3 space-y-2" id="fallback-options-{{ $message->id }}">
                                    <script>
                                        // Generate options on client side for existing messages
                                        document.addEventListener('DOMContentLoaded', function() {
                                                    const messageContent = @json($message->content);
                                                    const fallbackContainer = document.getElementById('fallback-options-{{ $message->id }}');

                                                    // Simple rule-based options for first question
                                                    if (messageContent.toLowerCase().includes('proyek') &&
                                                        messageContent.toLowerCase().includes('hari ini')) {
                                                        const options = ['Proyek Baru', 'Lanjut Proyek Sebelumnya'];

                                                        let html = '';
                                                        options.forEach((option, index) => {
                                                                html += `
                                                        <button type="button"
                                                            class="message-option w-full group flex items-start gap-3 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                            data-value="${option}"
                                                            onclick="selectOption(this)">
                                                            <span class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-600 text-sm font-medium group-hover:bg-indigo-100 group-hover:text-indigo-700">
                                                                    <button type="submit"
                                                                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-indigo-600 hover:text-indigo-700 focus:outline-none">
                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                `;

                                                                fallbackContainer.innerHTML = html;
                                                            }
                                                        });
                                    </script>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Message Input -->
        @if ($conversation->isActive())
            <div class="px-6 py-6 pb-8 border-t border-gray-200 bg-white" id="input-area">
                <div class="max-w-4xl mx-auto">
                    @php
                        $lastAiMessage = $conversation->messages()->where('sender_type', 'ai')->latest()->first();
                        $hasOptions =
                            $lastAiMessage &&
                            isset($lastAiMessage->metadata['has_options']) &&
                            $lastAiMessage->metadata['has_options'];
                    @endphp

                    <form id="message-form" class="flex items-center gap-3 {{ $hasOptions ? 'hidden' : '' }}">
                        @csrf
                        <div class="flex-1 relative">
                            <input type="text" id="message-input" placeholder="Type your answer..."
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-gray-900 placeholder-gray-400">
                            <button type="submit" id="send-button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-indigo-600 hover:text-indigo-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                    <div id="chat-validation-error" class="hidden mt-2 text-sm text-red-600" role="alert"></div>
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

            function isLikelyMeaningfulAnswer(value) {
                const normalized = value.trim();
                const letters = (normalized.match(/[A-Za-zÀ-ÿ]/g) || []).join('');
                const alphanumeric = normalized.replace(/[^A-Za-zÀ-ÿ0-9]/g, '');

                if (alphanumeric.length < 2 || letters.length < 2) return false;
                if (/(.)\1{2,}/i.test(normalized)) return false;
                if (/(qwerty|asdf|zxcv|qaz|wsx|edc)/i.test(normalized)) return false;

                if (letters.length >= 6) {
                    const uniqueLetters = new Set(letters.toLowerCase()).size;
                    const vowelCount = (letters.match(/[aeiou]/gi) || []).length;
                    if (uniqueLetters / letters.length < 0.35 || vowelCount / letters.length < 0.2) {
                        return false;
                    }
                }

                return true;
            }

            function conversationIsActive() {
                return {{ $conversation->isActive() ? 'true' : 'false' }};
            }

            function showValidationError(message, input = null) {
                const inlineForm = input ? input.closest('.inline-input-form') : null;
                const errorElement = inlineForm ?
                    inlineForm.querySelector('.inline-validation-error') :
                    document.getElementById('chat-validation-error');

                if (!errorElement) return;

                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
                if (input) input.setAttribute('aria-invalid', 'true');
            }

            function clearValidationError(input = null) {
                const inlineForm = input ? input.closest('.inline-input-form') : null;
                const errorElement = inlineForm ?
                    inlineForm.querySelector('.inline-validation-error') :
                    document.getElementById('chat-validation-error');

                if (!errorElement) return;

                errorElement.textContent = '';
                errorElement.classList.add('hidden');
                if (input) input.removeAttribute('aria-invalid');
            }

            function selectOption(button) {
                if (!conversationIsActive()) return;

                const value = button.getAttribute('data-value');

                // Disable all option buttons in this message
                const messageDiv = button.closest('.message-bubble');
                const allOptions = messageDiv.querySelectorAll('.message-option, .message-option-other');
                allOptions.forEach(opt => {
                    opt.disabled = true;
                    opt.classList.add('opacity-50', 'cursor-not-allowed');
                });

                submitMessage(value);
            }

            function showCustomInput() {
                if (!conversationIsActive()) return;

                // Show input form
                const messageForm = document.getElementById('message-form');
                messageForm.classList.remove('hidden');

                messageInput.focus();
                scrollToBottom();
            }

            function addMessage(content, isUser, hasOptions = false, options = [], questionType = null) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message-bubble flex ${isUser ? 'justify-end' : 'justify-start'}`;

                const now = new Date();
                const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

                let optionsHtml = '';
                if (!isUser && hasOptions && options.length > 0) {
                    optionsHtml = '<div class="mt-3 space-y-2">';

                    options.filter(option => option.trim().toLowerCase() !== 'something else').forEach((option, index) => {
                        optionsHtml += `
                            <button type="button"
                                class="message-option w-full group flex items-start gap-3 rounded-lg border-2 border-gray-200 bg-white px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                data-value="${escapeHtml(option)}"
                                onclick="selectOption(this)"
                                style="animation: fadeInUp 0.3s ease-out ${(index * 0.05)}s both">
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
                        `;
                    });

                    // Only add "Something else" for specific question types
                    const allowedTypes = ['objective', 'expectation', 'current_task', 'task_detail', 'task_challenge',
                        'task_approach'
                    ];
                    const hasCustomOption = options.some(option => option.trim().toLowerCase() === 'something else');
                    if ((questionType && allowedTypes.includes(questionType)) || hasCustomOption) {
                        optionsHtml += `
                        <div class="message-option-other-wrapper">
                            <!-- Button state -->
                            <button type="button"
                                class="message-option-other w-full group flex items-center gap-3 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-left transition hover:border-indigo-500 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                onclick="activateInlineInput(this)"
                                style="animation: fadeInUp 0.3s ease-out ${(options.length * 0.05)}s both">
                                <svg class="flex-shrink-0 w-5 h-5 text-gray-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                                <span class="flex-1 text-sm text-gray-600 group-hover:text-indigo-700">
                                    Something else
                                </span>
                            </button>

                            <!-- Input state (hidden initially) -->
                            <div class="inline-input-form hidden">
                                <div class="inline-validation-error hidden mb-2 text-sm text-red-600" role="alert"></div>
                                <form class="flex items-center gap-2" onsubmit="submitInlineInput(event, this)">
                                    <div class="flex-1 relative">
                                        <input type="text"
                                            class="inline-input w-full px-4 py-3 pr-12 border-2 border-indigo-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white text-gray-900 placeholder-gray-400"
                                            placeholder="Type your answer..."
                                            required>
                                        <button type="submit"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-indigo-600 hover:text-indigo-700 focus:outline-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        `;
                    }

                    optionsHtml += '</div>';
                }

                messageDiv.innerHTML = `
                    <div class="${isUser ? 'max-w-md' : (hasOptions ? 'max-w-xl w-full' : 'max-w-xl')}">
                        <div class="bg-${isUser ? 'indigo-600' : 'white'} text-${isUser ? 'white' : 'gray-900'} rounded-lg px-4 py-3 shadow-sm ${(!isUser && !hasOptions) || isUser ? 'inline-block' : ''}">
                            <p class="text-sm whitespace-pre-wrap">${escapeHtml(content)}</p>
                            <p class="text-xs mt-1 ${isUser ? 'text-indigo-200' : 'text-gray-400'}">
                                ${time}
                            </p>
                        </div>
                        ${optionsHtml}
                    </div>
                `;

                messagesContainer.querySelector('.space-y-4').appendChild(messageDiv);
                scrollToBottom();

                // Update input field visibility dynamically
                updateInputVisibility(hasOptions);
            }

            function updateInputVisibility(hasOptions) {
                const messageForm = document.getElementById('message-form');
                const messageInput = document.getElementById('message-input');

                if (!messageForm) return;

                if (hasOptions) {
                    // Hide input if AI has options
                    messageForm.classList.add('hidden');
                } else {
                    // Show input if AI expects text input
                    messageForm.classList.remove('hidden');
                    if (messageInput) {
                        messageInput.focus();
                    }
                }
            }

            function activateInlineInput(button) {
                if (!conversationIsActive()) return;

                const wrapper = button.closest('.message-option-other-wrapper');
                const buttonState = wrapper.querySelector('.message-option-other');
                const inputForm = wrapper.querySelector('.inline-input-form');
                const input = inputForm.querySelector('.inline-input');

                // Hide button, show input
                buttonState.classList.add('hidden');
                inputForm.classList.remove('hidden');

                // Focus input
                input.focus();

                // Scroll to bottom
                scrollToBottom();
            }

            function submitInlineInput(event, form) {
                event.preventDefault();
                if (!conversationIsActive()) return;

                const input = form.querySelector('.inline-input');
                const value = input.value.trim();

                clearValidationError(input);
                if (!value) {
                    showValidationError('Jawaban wajib diisi.', input);
                    input.focus();
                    return;
                }
                if (!isLikelyMeaningfulAnswer(value)) {
                    showValidationError('Jawaban belum cukup jelas. Silakan masukkan jawaban yang relevan.', input);
                    input.focus();
                    return;
                }

                // Disable the form
                input.disabled = true;
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.disabled = true;

                // Disable all options in this message
                const messageDiv = form.closest('.message-bubble');
                const allOptions = messageDiv.querySelectorAll('.message-option');
                allOptions.forEach(opt => {
                    opt.disabled = true;
                    opt.classList.add('opacity-50', 'cursor-not-allowed');
                });

                submitMessage(value);
            }

            async function submitMessage(message) {
                clearValidationError(messageInput);
                if (!message) {
                    showValidationError('Jawaban wajib diisi.', messageInput);
                    messageInput.focus();
                    return;
                }
                if (!isLikelyMeaningfulAnswer(message)) {
                    showValidationError('Jawaban belum cukup jelas. Silakan masukkan jawaban yang relevan.', messageInput);
                    if (messageInput) messageInput.focus();
                    return;
                }

                // Disable input and buttons (if exists)
                if (messageInput) messageInput.disabled = true;
                if (sendButton) sendButton.disabled = true;

                // Add user message to UI
                addMessage(message, true);

                // Add loading indicator
                const loadingDiv = document.createElement('div');
                loadingDiv.id = 'loading-indicator';
                loadingDiv.className = 'message-bubble flex justify-start';
                loadingDiv.innerHTML = `
                    <div class="max-w-xl w-full">
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

                // Clear input (if exists)
                if (messageInput) messageInput.value = '';

                try {
                    console.log('Sending message:', message);

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

                    console.log('Response status:', response.status);
                    const data = await response.json();
                    console.log('Response data:', data);

                    // Remove loading indicator
                    const loadingIndicator = document.getElementById('loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.remove();
                    }

                    if (data.success) {
                        // Check if response already includes AI message
                        if (data.ai_response && data.ai_response.content) {
                            // Use response directly (faster)
                            const hasOptions = data.ai_response.has_options || false;
                            const options = data.ai_response.options || [];
                            const questionType = data.ai_response.question_type || null;

                            addMessage(data.ai_response.content, false, hasOptions, options, questionType);

                            // Re-enable input and buttons (if exists)
                            if (messageInput) {
                                messageInput.disabled = false;
                                messageInput.focus();
                            }
                            if (sendButton) sendButton.disabled = false;
                        } else {
                            // Fallback: fetch messages
                            setTimeout(async () => {
                                const messagesResponse = await fetch(
                                    `/conversations/${conversationId}/messages`);
                                const messages = await messagesResponse.json();

                                const lastMessage = messages[messages.length - 1];
                                if (lastMessage && lastMessage.sender_type === 'ai') {
                                    const hasOptions = lastMessage.metadata && lastMessage.metadata.has_options;
                                    const options = hasOptions ? (lastMessage.metadata.options || []) : [];
                                    const questionType = lastMessage.metadata ? (lastMessage.metadata
                                        .question_type || null) : null;

                                    addMessage(lastMessage.content, false, hasOptions, options, questionType);
                                }

                                // Re-enable input and buttons
                                if (messageInput) {
                                    messageInput.disabled = false;
                                    messageInput.focus();
                                }
                                if (sendButton) sendButton.disabled = false;
                            }, 500);
                        }
                    } else if (data.validation_error && data.ai_response) {
                        // Handle validation error - show error message and keep user message
                        const hasOptions = data.ai_response.has_options || false;
                        const options = data.ai_response.options || [];
                        const questionType = data.ai_response.question_type || null;

                        addMessage(data.ai_response.content, false, hasOptions, options, questionType);

                        // Re-enable input and buttons
                        if (messageInput) {
                            messageInput.disabled = false;
                            messageInput.focus();
                        }
                        if (sendButton) sendButton.disabled = false;
                    } else {
                        // Other error
                        showValidationError(data.message || 'Terjadi kesalahan saat mengirim pesan.', messageInput);

                        // Re-enable input and buttons
                        if (messageInput) {
                            messageInput.disabled = false;
                            messageInput.focus();
                        }
                        if (sendButton) sendButton.disabled = false;
                    }
                } catch (error) {
                    console.error('Error sending message:', error);

                    // Remove loading indicator
                    const loadingIndicator = document.getElementById('loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.remove();
                    }

                    showValidationError('Terjadi kesalahan saat mengirim pesan: ' + error.message, messageInput);

                    // Re-enable input and buttons
                    if (messageInput) messageInput.disabled = false;
                    if (sendButton) sendButton.disabled = false;
                }
            }

            // Message form submit (only if form exists)
            if (messageForm) {
                messageForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    await submitMessage(messageInput.value.trim());
                });
            }

            // Focus input on load (only if exists)
            if (messageInput) messageInput.focus();
        </script>
    @endpush
@endsection
