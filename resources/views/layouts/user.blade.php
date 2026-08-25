<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') · INAai Project</title>
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
@if(session('success'))
<div class="alert alert-ok">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
{{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-err">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
{{ session('error') }}
</div>
@endif
@if($errors->any())
<div class="alert alert-err">
<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
{{ $errors->first() }}
</div>
@endif
@yield('content')
</div>
</div>
</div>
@include('partials.dialog')
<script src="{{ asset('js/inaai-dialog.js') }}"></script>
<script src="{{ asset('js/inaai-table.js') }}"></script>
<script src="{{ asset('js/inaai-select.js') }}"></script>
<script src="{{ asset('js/inaai-upload.js') }}"></script>
<script src="{{ asset('js/inaai-timeline.js') }}"></script>
@stack('script')
</body>
</html>
