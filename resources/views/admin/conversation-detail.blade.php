@extends('layouts.admin')

@section('title', 'Conversation Detail #' . $conversation->id)

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('admin.conversations') }}" class="text-indigo-600 hover:text-indigo-800">
                <i class="fas fa-arrow-left mr-1"></i>Back to Conversations
            </a>
        </div>

        <!-- Conversation Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Conversation #{{ $conversation->id }}</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">User</p>
                    <p class="font-medium">{{ $conversation->user->name }}</p>
                    <p class="text-sm text-gray-500">{{ $conversation->user->email }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Department</p>
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold"
                        style="background-color: {{ $conversation->department->color }}20; color: {{ $conversation->department->color }}">
                        {{ $conversation->department->name }}
                    </span>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span
                        class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $conversation->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ ucfirst($conversation->status) }}
                    </span>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Progress</p>
                    <p class="font-medium">Step {{ $conversation->current_step }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Created At</p>
                    <p class="font-medium">{{ $conversation->created_at->format('d M Y H:i') }}</p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">Last Updated</p>
                    <p class="font-medium">{{ $conversation->updated_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>

        @if (!empty($conversation->metadata['daily_activity']))
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-clipboard-check mr-2"></i>Daily Activity JSON
                </h2>
                <pre class="bg-gray-900 text-green-300 rounded-lg p-4 overflow-x-auto text-sm">{{ json_encode($conversation->metadata['daily_activity'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        <!-- Messages -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">
                <i class="fas fa-comments mr-2"></i>Messages ({{ $conversation->messages->count() }})
            </h2>

            <div class="space-y-4">
                @foreach ($conversation->messages as $message)
                    <div class="flex {{ $message->isFromUser() ? 'justify-end' : 'justify-start' }}">
                        <div
                            class="flex items-start space-x-2 max-w-xl {{ $message->isFromUser() ? 'flex-row-reverse space-x-reverse' : '' }}">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $message->isFromUser() ? 'bg-indigo-600' : 'bg-gray-300' }}">
                                <i
                                    class="fas {{ $message->isFromUser() ? 'fa-user' : 'fa-robot' }} text-white text-sm"></i>
                            </div>
                            <div
                                class="bg-{{ $message->isFromUser() ? 'indigo-600' : 'gray-100' }} text-{{ $message->isFromUser() ? 'white' : 'gray-900' }} rounded-lg px-4 py-3 shadow">
                                <div class="flex items-center justify-between mb-1">
                                    <span
                                        class="text-xs font-semibold {{ $message->isFromUser() ? 'text-indigo-200' : 'text-gray-500' }}">
                                        {{ $message->isFromUser() ? 'User' : 'AI Agent' }} - Step
                                        {{ $message->step_number }}
                                    </span>
                                </div>
                                <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>
                                <p class="text-xs mt-1 {{ $message->isFromUser() ? 'text-indigo-200' : 'text-gray-400' }}">
                                    {{ $message->created_at->format('d M Y H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
