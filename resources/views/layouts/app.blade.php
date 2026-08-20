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
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fas fa-home w-5 text-center mr-3"></i>
                        <span>Dashboard</span>
                    </a>

                    <form method="POST" action="{{ route('conversations.start') }}">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <button type="submit"
                            class="w-full flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('chat.*') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <i class="fas fa-comments w-5 text-center mr-3"></i>
                            <span>Chat Baru</span>
                        </button>
                    </form>

                    <div class="pt-6">
                        <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            Riwayat
                        </p>
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
            <!-- Fixed Navbar -->
            <header
                class="fixed top-0 right-0 left-0 lg:left-[248px] h-16 bg-white border-b border-gray-200 shadow-sm z-30">
                <div class="h-full px-5 lg:px-8 flex items-center justify-between">
                    <!-- Mobile Menu Button -->
                    <button class="lg:hidden text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <!-- Page Title -->
                    <div class="flex items-center space-x-4">
                        <h1 class="text-lg font-semibold text-gray-900">@yield('page-title', 'Dashboard')</h1>
                    </div>

                    <!-- Right Side -->
                    <div class="flex items-center space-x-4">
                        @if (auth()->user()->department)
                            <span class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium"
                                style="background-color: {{ auth()->user()->department->color }}20; color: {{ auth()->user()->department->color }};">
                                <i class="fas fa-building mr-2"></i>{{ auth()->user()->department->name }}
                            </span>
                        @endif
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="pt-16 min-h-screen">
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

    @stack('scripts')
</body>

</html>
