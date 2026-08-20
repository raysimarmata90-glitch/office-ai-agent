@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard Admin')

@section('content')
    <div class="px-5 py-8 lg:px-8 lg:py-10 space-y-8">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Selamat datang, {{ $user->name }}!</h1>
                    <p class="text-indigo-100 text-lg">Panel kontrol administrasi Office AI Agent</p>
                </div>
                <div class="hidden md:block">
                    <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-halved text-5xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-5 metric-card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 mb-1 font-sans">Total Pekerjaan</p>
                        <p class="text-3xl font-bold font-mono text-gray-900">{{ $pekerjaan->total() }}</p>
                        <div class="mt-2 flex items-center text-xs font-sans">
                            <span class="text-gray-500">Semua staff</span>
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 bg-indigo-100 text-indigo-700">
                        <i class="fas fa-briefcase text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-5 metric-card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 mb-1 font-sans">On Going</p>
                        <p class="text-3xl font-bold font-mono text-gray-900">
                            {{ $pekerjaan->where('status', 'on going')->count() }}</p>
                        <div class="mt-2 flex items-center text-xs font-sans">
                            <span class="text-amber-600 font-medium">
                                <i class="fas fa-circle text-xs mr-1"></i>Berlangsung
                            </span>
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 bg-amber-100 text-amber-700">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-5 metric-card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 mb-1 font-sans">Completed</p>
                        <p class="text-3xl font-bold font-mono text-gray-900">
                            {{ $pekerjaan->where('status', 'completed')->count() }}</p>
                        <div class="mt-2 flex items-center text-xs font-sans">
                            <span class="text-emerald-600 font-medium">
                                <i class="fas fa-check-circle text-xs mr-1"></i>Selesai
                            </span>
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 bg-emerald-100 text-emerald-700">
                        <i class="fas fa-check text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-lg p-5 metric-card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-600 mb-1 font-sans">Highest Priority</p>
                        <p class="text-3xl font-bold font-mono text-gray-900">
                            {{ $pekerjaan->where('kategori', 'Highest')->count() }}</p>
                        <div class="mt-2 flex items-center text-xs font-sans">
                            <span class="text-red-600 font-medium">
                                <i class="fas fa-angles-up text-xs mr-1"></i>Prioritas
                            </span>
                        </div>
                    </div>
                    <div
                        class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 bg-red-100 text-red-700">
                        <i class="fas fa-exclamation text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-briefcase mr-2 text-indigo-600"></i>Daftar Pekerjaan Staff
                </h2>
                <p class="text-gray-600 mt-1 text-sm">Ringkasan pekerjaan dari percakapan yang telah selesai.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Divisi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Nama Projek</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Pekerjaan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Prioritas</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($pekerjaan as $item)
                            <tr class="align-top hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-mono text-gray-500 whitespace-nowrap">
                                    {{ $pekerjaan->firstItem() + $loop->index }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                    {{ $item->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                        <i class="fas fa-building mr-1.5 text-xs"></i>
                                        {{ $item->division ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                    {{ $item->nama_projek }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 min-w-[300px] max-w-[400px]">
                                    <div class="whitespace-pre-line line-clamp-3">{{ $item->pekerjaan }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form id="pekerjaan-update-{{ $item->id }}" method="POST"
                                        action="{{ route('admin.pekerjaan.update', $item) }}">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    <div class="space-y-2">
                                        @if ($item->status === 'on going')
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 bg-amber-500"></span>
                                                On Going
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                                <span
                                                    class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 bg-emerald-500"></span>
                                                Completed
                                            </span>
                                        @endif
                                        <select form="pekerjaan-update-{{ $item->id }}" name="status"
                                            class="block w-full text-xs border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="on going" @selected($item->status === 'on going')>On Going</option>
                                            <option value="completed" @selected($item->status === 'completed')>Completed</option>
                                        </select>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $categoryStyles = [
                                            'Highest' => ['bg-red-50 text-red-700 ring-red-200', 'fa-angles-up', 'bg-red-500'],
                                            'High' => ['bg-rose-50 text-rose-600 ring-rose-200', 'fa-angle-up', 'bg-rose-500'],
                                            'Medium' => ['bg-orange-50 text-orange-600 ring-orange-200', 'fa-minus', 'bg-orange-500'],
                                            'Low' => ['bg-blue-50 text-blue-600 ring-blue-200', 'fa-angle-down', 'bg-blue-500'],
                                            'Lowest' => ['bg-gray-100 text-gray-600 ring-gray-200', 'fa-angles-down', 'bg-gray-500'],
                                        ];
                                        [$categoryClass, $categoryIcon, $categoryDot] =
                                            $categoryStyles[$item->kategori] ?? $categoryStyles['Medium'];
                                    @endphp
                                    <div class="space-y-2">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full ring-1 {{ $categoryClass }}">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 {{ $categoryDot }}"></span>
                                            <i class="fas {{ $categoryIcon }} mr-1"></i>{{ $item->kategori ?? 'Medium' }}
                                        </span>
                                        <select form="pekerjaan-update-{{ $item->id }}" name="kategori"
                                            class="block w-full text-xs border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach (['Highest', 'High', 'Medium', 'Low', 'Lowest'] as $category)
                                                <option value="{{ $category }}" @selected($item->kategori === $category)>
                                                    {{ $category }}</option>
                                            @endforeach
                                        </select>
                                        <button form="pekerjaan-update-{{ $item->id }}" type="submit"
                                            class="w-full px-3 py-1.5 text-xs text-white bg-indigo-600 hover:bg-indigo-700 rounded-md font-medium transition-colors">
                                            <i class="fas fa-save mr-1"></i>Simpan
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-mono text-gray-500 whitespace-nowrap">
                                    {{ $item->created_at?->format('d M Y') ?? '-' }}<br>
                                    <span class="text-xs text-gray-400">{{ $item->created_at?->format('H:i') ?? '' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
                                    <p class="font-medium">Belum ada data pekerjaan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pekerjaan->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $pekerjaan->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
