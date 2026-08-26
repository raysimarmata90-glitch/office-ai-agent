<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - Office AI Agent</title>
    @include('partials.favicon')
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body class="bg-[#F8F9FA] font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo Section -->
        <div class="text-center mb-8">
            <img src="{{ asset('img/logo-inaai.webp') }}" alt="INAai"
                 class="h-16 w-auto mx-auto mb-4 select-none" draggable="false">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Buat Akun Baru</h1>
            <p class="text-gray-600">Bergabung dengan Office AI Agent</p>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle mr-2 mt-0.5"></i>
                        <div class="flex-1">
                            <ul class="list-disc list-inside text-sm space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Lengkap
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            autofocus
                            class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            placeholder="John Doe">
                    </div>
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                            class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            placeholder="nama@perusahaan.com">
                    </div>
                </div>

                <!-- Department Field -->
                <div>
                    <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Departemen
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-building text-gray-400"></i>
                        </div>
                        <select id="department_id" name="department_id" required
                            class="block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            <option value="">Pilih Departemen</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                            class="block w-full pl-10 pr-11 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            placeholder="Minimal 8 karakter">
                        <button type="button" data-lihat-sandi="password" tabindex="-1"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                            title="Tampilkan password" aria-label="Tampilkan password" aria-pressed="false">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                        Konfirmasi Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            class="block w-full pl-10 pr-11 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            placeholder="Ulangi password">
                        <button type="button" data-lihat-sandi="password_confirmation" tabindex="-1"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                            title="Tampilkan password" aria-label="Tampilkan password" aria-pressed="false">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-3 px-4 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-200 shadow-sm hover:shadow-md mt-6">
                    <i class="fas fa-user-plus mr-2"></i>
                    Daftar Sekarang
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                        Masuk di sini
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                © 2026 Office AI Agent. All rights reserved.
            </p>
        </div>
    </div>

    <script>
    // Toggle lihat/sembunyi password.
    document.querySelectorAll('[data-lihat-sandi]').forEach(function (b) {
        b.addEventListener('click', function () {
            var input = document.getElementById(b.dataset.lihatSandi);
            if (!input) return;
            var lihat = input.type === 'password';
            input.type = lihat ? 'text' : 'password';
            // Icon: tersembunyi = eye-slash, terlihat = eye
            b.querySelector('i').className = lihat ? 'fas fa-eye' : 'fas fa-eye-slash';
            b.title = lihat ? 'Sembunyikan password' : 'Tampilkan password';
            b.setAttribute('aria-label', b.title);
            b.setAttribute('aria-pressed', lihat ? 'true' : 'false');
            input.focus();
        });
    });
    </script>
</body>

</html>
