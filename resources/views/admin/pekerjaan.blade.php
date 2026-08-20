@extends('layouts.admin')

@section('title', 'Pekerjaan Staff')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">
                Pekerjaan Staff
            </h1>

            <p class="mt-1 text-gray-500">
                Data ini dibuat dari hasil percakapan aktivitas harian yang sudah selesai.
            </p>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User ID
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Division
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama Projek
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pekerjaan
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal
                            </th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($pekerjaan as $item)
                            <tr class="hover:bg-gray-50">

                                {{-- ID --}}
                                <td class="px-6 py-4 text-sm text-gray-900 align-top whitespace-nowrap">
                                    {{ $item->id }}
                                </td>

                                {{-- User ID --}}
                                <td class="px-6 py-4 text-sm text-gray-500 align-top whitespace-nowrap">
                                    {{ $item->user_id }}
                                </td>

                                {{-- Name --}}
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 align-top whitespace-nowrap">
                                    {{ $item->name }}
                                </td>

                                {{-- Division --}}
                                <td class="px-6 py-4 text-sm text-gray-900 align-top whitespace-nowrap">
                                    {{ $item->division ?? '-' }}
                                </td>

                                {{-- Nama Projek --}}
                                <td class="px-6 py-4 text-sm text-gray-900 align-top min-w-[180px]">
                                    {{ $item->nama_projek }}
                                </td>

                                {{-- Pekerjaan --}}
                                <td class="px-6 py-4 text-sm text-gray-900 align-top min-w-[300px] max-w-[500px]">
                                    <div class="whitespace-pre-line break-words">
                                        {{ $item->pekerjaan }}
                                    </div>
                                </td>

                                {{-- Tanggal --}}
                                <td class="px-6 py-4 text-sm text-gray-500 align-top whitespace-nowrap">
                                    {{ $item->created_at?->format('d M Y H:i') ?? '-' }}
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada data pekerjaan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if ($pekerjaan->hasPages())
            <div class="mt-6">
                {{ $pekerjaan->links() }}
            </div>
        @endif

    </div>
@endsection
