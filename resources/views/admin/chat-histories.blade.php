@extends('layouts.admin')

@section('title', 'Chat History')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Chat History</h1>
            <p class="text-gray-500 mt-1">Setiap pesan tersimpan sebagai satu baris di tabel chat_histories.</p>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conversation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pengirim</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pesan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($histories as $history)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-6 py-4 text-sm text-gray-900">#{{ $history->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $history->created_at->format('d M Y H:i:s') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $history->user->name }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="{{ route('admin.conversation.detail', $history->conversation_id) }}"
                                        class="text-indigo-600 hover:text-indigo-900">
                                        #{{ $history->conversation_id }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $history->sender_type === 'user' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ strtoupper($history->sender_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 min-w-[24rem] text-sm text-gray-900 whitespace-pre-wrap">
                                    {{ $history->content }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada history chat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $histories->links() }}
        </div>
    </div>
@endsection
