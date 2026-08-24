@extends('layouts.app')

@section('title', 'Pekerjaan Saya')
@section('page-title', 'Pekerjaan Saya')

@section('content')
    <div class="px-5 py-8 lg:px-8 lg:py-10">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                <i class="fas fa-briefcase mr-3" style="color: {{ $user->department->color }};"></i>Pekerjaan Saya
            </h1>
            <p class="text-gray-600">Daftar semua pekerjaan yang telah Anda catat</p>
        </div>

        @if ($pekerjaan->count() > 0)
            <!-- Pekerjaan Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @foreach ($pekerjaan as $item)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3 flex-1 min-w-0">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                    style="background-color: {{ $user->department->color }}20;">
                                    <i class="fas fa-folder text-lg" style="color: {{ $user->department->color }};"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-gray-900 truncate">{{ $item->nama_projek }}</h3>
                                    <p class="text-xs text-gray-500">{{ $item->division ?? 'No Division' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-sm text-gray-700 mb-4 line-clamp-3 leading-relaxed">
                            {{ $item->pekerjaan }}
                        </p>

                        <!-- Footer -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                            <div class="flex items-center space-x-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $item->status === 'on going' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $item->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $item->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                    {{ $item->kategori }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 font-mono">
                                {{ $item->created_at?->format('d M Y') ?? '-' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $pekerjaan->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center"
                        style="background-color: {{ $user->department->color }}20;">
                        <i class="fas fa-briefcase text-3xl" style="color: {{ $user->department->color }};"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Belum Ada Pekerjaan</h3>
                    <p class="text-gray-600 mb-6">Mulai percakapan dengan AI Agent untuk mencatat pekerjaan Anda</p>
                    <form method="POST" action="{{ route('conversations.start') }}" class="inline-block">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ $user->department_id }}">
                        <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg text-white font-medium hover:opacity-90 transition-opacity"
                            style="background-color: {{ $user->department->color }};">
                            <i class="fas fa-plus mr-2"></i>
                            Mulai Chat Baru
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
