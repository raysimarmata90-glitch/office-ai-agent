<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Office AI Agent')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'clean-light': '#F8F9FA',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'Courier New', 'monospace'],
                    },
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        .metric-card {
            transition: all 0.2s;
        }

        .metric-card:hover {
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            transform: translateY(-2px);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background-color: #f1f5f9;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Custom Modal Styles */
        .modal-overlay {
            background: rgba(249, 250, 251, 0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: opacity 0.3s ease;
        }

        .modal-content {
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>

<body class="bg-[#F8F9FA] font-sans min-h-screen">
    @auth
        <!-- Fixed Sidebar -->
        <aside class="fixed left-0 top-0 h-full w-[248px] bg-white border-r border-gray-200 shadow-sm z-40 hidden lg:block">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="h-16 flex items-center px-6 border-b border-gray-200">
                    <span class="text-lg font-bold text-gray-900">AI Agent</span>
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto custom-scrollbar">
                    <form method="POST" action="{{ route('conversations.start') }}">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <button type="submit"
                            class="w-full flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-plus w-5 text-center mr-3"></i>
                            <span>Chat Baru</span>
                        </button>
                    </form>

                    <a href="{{ route('pekerjaan.index') }}"
                        class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('pekerjaan.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fas fa-briefcase w-5 text-center mr-3"></i>
                        <span>Pekerjaan Saya</span>
                    </a>

                    <!-- Conversations History -->
                    <div class="pt-6">
                        <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Riwayat
                        </p>
                        @php
                            $userConversations = auth()->user()->conversations()
                                ->with(['messages' => function($query) {
                                    $query->latest()->limit(1);
                                }])
                                ->whereHas('messages', function($query) {
                                    $query->where('sender_type', 'user');
                                })
                                ->latest('updated_at')
                                ->take(10)
                                ->get();
                        @endphp
                        
                        @if($userConversations->count() > 0)
                            <div class="space-y-1">
                                @foreach($userConversations as $conv)
                                    <div class="group relative">
                                        <a href="{{ route('chat.show', $conv->id) }}"
                                            class="flex items-start px-4 py-2.5 text-sm rounded-lg transition-colors hover:bg-gray-100 {{ request()->route('id') == $conv->id ? 'bg-gray-100' : '' }}">
                                            <i class="fas fa-message w-5 text-center mr-3 text-gray-500 mt-0.5 flex-shrink-0"></i>
                                            <div class="flex-1 min-w-0 pr-6">
                                                <p class="text-gray-900 truncate font-medium text-sm mb-0.5">{{ $conv->title }}</p>
                                                @if($conv->messages->isNotEmpty())
                                                    <p class="text-xs text-gray-500 truncate">
                                                        {{ Str::limit($conv->messages->first()->content, 35) }}
                                                    </p>
                                                @endif
                                            </div>
                                        </a>
                                        <form method="POST" action="{{ route('conversations.destroy', $conv->id) }}"
                                            class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 transition-opacity delete-conversation-form"
                                            data-conversation-title="{{ $conv->title }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded delete-conversation-btn"
                                                title="Hapus percakapan">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="px-4 text-xs text-gray-500 italic">Belum ada percakapan</p>
                        @endif
                    </div>
                </nav>

                <!-- User Info -->
                <div class="p-4 border-t border-gray-200">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ auth()->user()->department->name ?? 'No Dept' }}
                            </p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors flex items-center justify-center">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area with Left Padding for Sidebar -->
        <div class="lg:pl-[248px]">
            <!-- Main Content -->
            <main class="min-h-screen">
                @if (session('success'))
                    <div class="mx-5 lg:mx-8 mt-6">
                        <div
                            class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    @else
        <!-- Guest View -->
        <main>
            @yield('content')
        </main>
    @endauth

    <!-- Custom Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center modal-overlay">
        <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden border border-gray-200">
            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="text-xl font-semibold text-gray-900">Hapus obrolan</h3>
            </div>
            
            <!-- Modal Body -->
            <div class="px-6 py-6">
                <p class="text-gray-700 text-base">
                    Apakah Anda yakin ingin menghapus obrolan ini?
                </p>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-6 py-4 flex gap-3 justify-end bg-gray-50">
                <button type="button" id="cancelDelete"
                    class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors border border-gray-300">
                    Batalkan
                </button>
                <button type="button" id="confirmDelete"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-[#dc2626] rounded-lg hover:bg-[#b91c1c] focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors">
                    Hapus
                </button>
            </div>
        </div>
    </div>

    <script>
        // Custom Delete Modal Handler
        let currentFormToSubmit = null;

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('deleteModal');
            const cancelBtn = document.getElementById('cancelDelete');
            const confirmBtn = document.getElementById('confirmDelete');

            // Handle delete button clicks
            document.querySelectorAll('.delete-conversation-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.closest('.delete-conversation-form');
                    
                    currentFormToSubmit = form;
                    showModal();
                });
            });

            // Cancel button
            cancelBtn.addEventListener('click', function() {
                hideModal();
                currentFormToSubmit = null;
            });

            // Confirm delete button
            confirmBtn.addEventListener('click', function() {
                if (currentFormToSubmit) {
                    currentFormToSubmit.submit();
                }
                hideModal();
            });

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    hideModal();
                    currentFormToSubmit = null;
                }
            });

            // Close modal on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    hideModal();
                    currentFormToSubmit = null;
                }
            });

            function showModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            function hideModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
