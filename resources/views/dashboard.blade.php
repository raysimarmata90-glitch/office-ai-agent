@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard ' . $user->department->name)

@section('content')
    <div class="px-5 py-8 lg:px-8 lg:py-10 space-y-8">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Selamat datang, {{ $user->name }}!</h1>
                    <p class="text-indigo-100 text-lg">Bagaimana saya bisa membantu Anda hari ini?</p>
                </div>
                <div class="hidden md:block">
                </div>
            </div>
        </div>

        <!-- Start New Conversation -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-plus-circle mr-2" style="color: {{ $user->department->color }};"></i>Mulai Percakapan
                    Baru
                </h2>
                <p class="text-gray-600 text-sm">Mulai sesi dengan AI Agent untuk mendapatkan bantuan dan insight</p>
            </div>

            @foreach ($departments as $dept)
                <form method="POST" action="{{ route('conversations.start') }}">
                    @csrf
                    <input type="hidden" name="department_id" value="{{ $dept->id }}">
                    <button type="submit"
                        class="w-full p-6 border-2 rounded-xl hover:shadow-md transition-all duration-200"
                        style="border-color: {{ $dept->color }}40;"
                        onmouseover="this.style.borderColor='{{ $dept->color }}'; this.style.backgroundColor='{{ $dept->color }}05';"
                        onmouseout="this.style.borderColor='{{ $dept->color }}40'; this.style.backgroundColor='transparent';">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-14 h-14 rounded-xl flex items-center justify-center"
                                    style="background-color: {{ $dept->color }}20;">
                                    <i class="fas fa-robot text-2xl" style="color: {{ $dept->color }};"></i>
                                </div>
                                <div class="ml-5 text-left">
                                    <h3 class="text-lg font-bold text-gray-900 mb-0.5">{{ $dept->name }} Agent</h3>
                                    <p class="text-sm text-gray-500">{{ $dept->description }}</p>
                                </div>
                            </div>
                            <div class="flex items-center font-medium" style="color: {{ $dept->color }};">
                                <span class="mr-2 text-sm">Mulai Chat</span>
                                <i class="fas fa-arrow-right"></i>
                            </div>
                        </div>
                    </button>
                </form>
            @endforeach
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- My Work -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-briefcase mr-2" style="color: {{ $user->department->color }};"></i>Pekerjaan Saya
                    </h2>
                    @if ($pekerjaan->count() > 0)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $pekerjaan->count() }} pekerjaan
                        </span>
                    @endif
                </div>

                @if ($pekerjaan->count() > 0)
                    <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar">
                        @foreach ($pekerjaan->take(5) as $item)
                            <div class="p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-semibold text-gray-900 text-sm">{{ $item->nama_projek }}</h3>
                                    <span class="text-xs text-gray-500 font-mono whitespace-nowrap ml-2">
                                        {{ $item->created_at?->format('d M Y') ?? '-' }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 line-clamp-2">{{ $item->pekerjaan }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-briefcase text-4xl mb-3" style="color: {{ $user->department->color }}40;"></i>
                        <p class="font-medium">Belum ada pekerjaan yang tercatat.</p>
                    </div>
                @endif
            </div>

            <!-- Recent Conversations -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-history mr-2" style="color: {{ $user->department->color }};"></i>Riwayat
                        Percakapan
                    </h2>
                    @if ($conversations->count() > 0)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $conversations->count() }} percakapan
                        </span>
                    @endif
                </div>

                @if ($conversations->count() > 0)
                    <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar">
                        @foreach ($conversations->take(5) as $conv)
                            <div
                                class="relative p-4 border border-gray-200 rounded-lg hover:border-gray-300 hover:shadow-sm transition-all duration-200 group">
                                <div class="flex items-start justify-between">
                                    <a href="{{ route('chat.show', $conv->id) }}" class="flex-1 min-w-0 mr-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="font-semibold text-gray-900 truncate text-sm">{{ $conv->title }}
                                            </h3>
                                            @if ($conv->status === 'active')
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 ring-1 ring-green-200 ml-2">
                                                    <span
                                                        class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 bg-green-500"></span>
                                                    Aktif
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-50 text-gray-700 ring-1 ring-gray-200 ml-2">
                                                    <span
                                                        class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 bg-gray-500"></span>
                                                    Selesai
                                                </span>
                                            @endif
                                        </div>

                                        @if ($conv->messages->count() > 0)
                                            @php
                                                $lastMessage = $conv->messages->last();
                                            @endphp
                                            <p class="text-xs text-gray-600 line-clamp-2 mb-2">
                                                {{ Str::limit($lastMessage->content, 80) }}
                                            </p>
                                        @endif

                                        <div class="flex items-center text-xs text-gray-400 space-x-3">
                                            <span><i class="fas fa-message mr-1"></i>{{ $conv->messages->count() }}</span>
                                            <span class="font-mono">{{ $conv->updated_at->diffForHumans() }}</span>
                                        </div>
                                    </a>

                                    <form method="POST" action="{{ route('conversations.destroy', $conv->id) }}"
                                        class="flex-shrink-0"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus percakapan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg"
                                            title="Hapus percakapan">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3" style="color: {{ $user->department->color }}40;"></i>
                        <p class="font-medium mb-2">Belum ada percakapan</p>
                        <p class="text-sm text-gray-400">Mulai percakapan baru dengan {{ $user->department->name }} Agent!
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
