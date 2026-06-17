<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} @hasSection('title') — @yield('title') @endif</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/boels-favicon.svg') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --boels-orange: {{ $boelsBrand['color'] ?? '#FF6600' }};
            --boels-orange-dark: #cc5200;
            --boels-text-on-orange: {{ $boelsBrand['text_color'] ?? '#FFFFFF' }};
        }
        body { background:#f6f7f9; }
        .navbar-boels { background: var(--boels-orange); }
        .navbar-boels .navbar-brand,
        .navbar-boels .nav-link,
        .navbar-boels .navbar-text { color: var(--boels-text-on-orange) !important; }
        .navbar-boels .nav-link:hover { opacity:.85; }
        .btn-boels { background: var(--boels-orange); color: var(--boels-text-on-orange); border:0; }
        .btn-boels:hover { background: var(--boels-orange-dark); color: var(--boels-text-on-orange); }
        .text-boels { color: var(--boels-orange) !important; }
        .bg-boels { background: var(--boels-orange) !important; color: var(--boels-text-on-orange); }
        .boels-logo {
            display:inline-flex; align-items:center; justify-content:center;
            width:34px; height:34px; border-radius:6px;
            background: var(--boels-orange); color:#fff;
            font-weight:800; font-family:Arial,sans-serif; font-size:20px;
            margin-right:10px;
        }
        .app-tile { transition: transform .15s ease, box-shadow .15s ease; }
        .app-tile:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        .app-tile .icon-circle {
            width:64px; height:64px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:30px; margin:0 auto 12px;
        }
    </style>
    @stack('styles')
</head>
<body>

@auth
<nav class="navbar navbar-expand-lg navbar-boels shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/launcher') }}">
            <span class="boels-logo">B</span>
            <span>{{ config('boels.brand.name') }} <small class="opacity-75">{{ config('boels.brand.product') }}</small></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{{ url('/launcher') }}"><i class="bi bi-grid-3x3-gap"></i> Launcher</a></li>
                @if(auth()->user()->is_super_admin)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-gear"></i> Beheer</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ url('/admin/users') }}">Gebruikers (login)</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/employees') }}">Medewerkers</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/roles') }}">Rollen</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/permissions') }}">Permissies</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/applications') }}">Applicaties</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/custom-fields') }}">Custom Fields</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/field-aliases') }}">Field Aliases</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/imports') }}">Import Engine</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/audit-log') }}">Audit Log</a></li>
                            <li><a class="dropdown-item" href="{{ url('/admin/table-ownership') }}">Tabel-eigendom</a></li>
                        </ul>
                    </li>
                @endif
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ url('/logout') }}">@csrf
                                <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right"></i> Uitloggen</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
@endauth

<main class="py-4">
    <div class="container-fluid">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </div>
</main>

<footer class="text-center text-muted py-4 small">
    &copy; {{ date('Y') }} Boels — CORE Platform · v{{ config('app.version', '0.1') }}
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
