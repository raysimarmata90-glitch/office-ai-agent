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
            <!-- Pekerjaan Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Projek</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pekerjaan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($pekerjaan as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-500 align-top whitespace-nowrap">
                                        {{ $pekerjaan->firstItem() + $loop->index }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 align-top min-w-[180px]">
                                        {{ $item->nama_projek }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 align-top min-w-[300px] max-w-[500px]">
                                        <div class="whitespace-pre-line break-words">{{ $item->pekerjaan }}</div>
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $item->status === 'on going' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $item->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}">
                                            {{ ucfirst($item->status ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                            {{ $item->kategori ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 align-top whitespace-nowrap">
                                        {{ $item->created_at?->format('d M Y H:i') ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
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
