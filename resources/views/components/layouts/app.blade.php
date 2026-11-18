<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'SIMRS External Access Gateway' }} - RSUD Tidar</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full text-slate-800 antialiased font-sans">
    <div class="min-h-full flex flex-col">
        <!-- Top Navigation Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-teal-600 text-white font-bold text-xl shadow-sm">
                            SG
                        </div>
                        <div>
                            <span class="text-base font-bold text-slate-900 tracking-tight block">SEAG</span>
                            <span class="text-xs text-slate-500 font-medium hidden sm:block">SIMRS External Access Gateway</span>
                        </div>
                    </div>

                    @auth
                    <!-- Nav Links -->
                    <nav class="hidden md:flex space-x-1 items-center">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Dashboard
                        </a>
                        
                        @if(auth()->user()->isAccessAdmin())
                        <a href="{{ route('access-requests.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('access-requests.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Permintaan Akses
                        </a>
                        <a href="{{ route('user-mappings.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('user-mappings.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            SIMRS Mapping
                        </a>
                        @endif

                        @if(auth()->user()->isSecurityAuditor())
                        <a href="{{ route('audit-logs.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('audit-logs.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Audit Log
                        </a>
                        @endif

                        @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('administrators.index') }}" class="px-3 py-2 rounded-md text-sm font-medium transition {{ request()->routeIs('administrators.*') ? 'bg-slate-100 text-slate-900 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Administrator
                        </a>
                        @endif
                    </nav>

                    <!-- User Dropdown / Logout -->
                    <div class="flex items-center space-x-4">
                        <div class="text-right hidden sm:block">
                            <span class="block text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-slate-100 text-slate-700">
                                {{ auth()->user()->role }}
                            </span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                                Keluar
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-4 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500">
                <div>&copy; {{ date('Y') }} RSUD Tidar Kota Magelang. Instalasi IT & Jaringan.</div>
                <div class="mt-2 sm:mt-0">Gateway Version 1.0 (Laravel 13 & Livewire 4)</div>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>

