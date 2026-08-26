<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') · INaAI Project</title>
@include('partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@include('partials.inaai-style')
@stack('style')
</head>
<body>
<div class="shell">
@include('partials.user-sidebar')
<div class="main">
<div class="topbar">
<div style="flex:1;min-width:0">
<div class="page-title">@yield('page-title', 'Dashboard')</div>
<div class="page-sub">@yield('page-sub', '')</div>
</div>
@yield('topbar-actions')
</div>
<div class="content">
{{-- Flash dari server dilempar ke komponen toast global --}}
@if(session('success'))<div data-toast data-jenis="sukses" data-teks="{{ session('success') }}"></div>@endif
@if(session('info'))<div data-toast data-jenis="info" data-teks="{{ session('info') }}"></div>@endif
@if(session('warning'))<div data-toast data-jenis="peringatan" data-teks="{{ session('warning') }}"></div>@endif
@if(session('error'))<div data-toast data-jenis="galat" data-teks="{{ session('error') }}"></div>@endif
@if($errors->any())<div data-toast data-jenis="galat" data-teks="{{ $errors->all() ? implode(' ', $errors->all()) : '' }}"></div>@endif
@yield('content')
</div>
</div>
</div>
@include('partials.dialog')
<script src="{{ asset('js/inaai-toast.js') }}"></script>
<script src="{{ asset('js/inaai-dialog.js') }}"></script>
<script src="{{ asset('js/inaai-table.js') }}"></script>
<script src="{{ asset('js/inaai-select.js') }}"></script>
<script src="{{ asset('js/inaai-upload.js') }}"></script>
<script src="{{ asset('js/inaai-datetime.js') }}"></script>
<script src="{{ asset('js/inaai-timeline.js') }}"></script>
<script src="{{ asset('js/inaai-kanban.js') }}"></script>
<script src="{{ asset('js/inaai-profil.js') }}"></script>
@stack('script')
</body>
</html>
