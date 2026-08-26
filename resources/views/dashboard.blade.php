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
    </div>
@endsection
