@extends('layouts.app')

@section('title', 'Chat - ' . $conversation->department->name)
@section('page-title', 'Chat')

@section('content')
    <div class="h-screen flex flex-col">
        <!-- Chat Header -->
        <div class="bg-white border-b px-6 py-4">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                        style="background-color: {{ $conversation->department->color }}20;">
                        <i class="fas fa-robot" style="color: {{ $conversation->department->color }};"></i>
                    </div>
                    <div>
                        <h2 class="font-semibold text-gray-900">
                            {{ $conversation->department->name === 'AI' ? 'AI Agent' : $conversation->department->name . ' AI Agent' }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            @if ($conversation->isActive())
                                <i class="fas fa-circle text-green-500 text-xs mr-1"></i>Aktif
                            @else
                                <i class="fas fa-check text-gray-500 text-xs mr-1"></i>Selesai
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages Container -->
        <div id="messages-container" class="flex-1 overflow-y-auto bg-gray-50 px-6 py-4">
            <div class="max-w-5xl mx-auto space-y-4">
                @foreach ($conversation->messages as $message)
                    <div class="flex {{ $message->isFromUser() ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="flex items-start space-x-2 max-w-xl {{ $message->isFromUser() ? 'flex-row-reverse space-x-reverse' : '' }}">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $message->isFromUser() ? 'bg-indigo-600' : 'bg-gray-300' }}">
                                <i class="fas {{ $message->isFromUser() ? 'fa-user' : 'fa-robot' }} text-white text-sm"></i>
                            </div>
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
            <div class="bg-white border-t px-6 py-4">
                <div class="max-w-5xl mx-auto">
                    <form id="message-form" class="flex space-x-4">
                        @csrf
                        <input type="text" id="message-input" placeholder="Ketik jawaban Anda..." required
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button type="submit" id="send-button"
                            class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-paper-plane mr-2"></i>Kirim
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="bg-yellow-50 border-t border-yellow-200 px-6 py-4">
                <div class="max-w-5xl mx-auto text-center text-yellow-800">
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

            function addMessage(content, isUser) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `flex ${isUser ? 'justify-end' : 'justify-start'}`;

                const now = new Date();
                const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

                messageDiv.innerHTML = `
            <div class="flex items-start space-x-2 max-w-xl ${isUser ? 'flex-row-reverse space-x-reverse' : ''}">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${isUser ? 'bg-indigo-600' : 'bg-gray-300'}">
                    <i class="fas ${isUser ? 'fa-user' : 'fa-robot'} text-white text-sm"></i>
                </div>
                <div class="bg-${isUser ? 'indigo-600' : 'white'} text-${isUser ? 'white' : 'gray-900'} rounded-lg px-4 py-3 shadow">
                    <p class="text-sm whitespace-pre-wrap">${content}</p>
                    <p class="text-xs mt-1 ${isUser ? 'text-indigo-200' : 'text-gray-400'}">
                        ${time}
                    </p>
                </div>
            </div>
        `;

                messagesContainer.querySelector('.space-y-4').appendChild(messageDiv);
                scrollToBottom();
            }

            messageForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const message = messageInput.value.trim();
                if (!message) return;

                // Disable input and button
                messageInput.disabled = true;
                sendButton.disabled = true;

                // Add user message to UI
                addMessage(message, true);

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

                            // Re-enable input and button
                            messageInput.disabled = false;
                            sendButton.disabled = false;
                            messageInput.focus();
                        }, 1000);
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                    alert('Terjadi kesalahan saat mengirim pesan');
                    messageInput.disabled = false;
                    sendButton.disabled = false;
                }
            });

            // Focus input on load
            messageInput.focus();
        </script>
    @endpush
@endsection
