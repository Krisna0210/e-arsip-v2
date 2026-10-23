<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'E-Arsip' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|plus-jakarta-sans:400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-[var(--color-background)] text-[var(--color-text-primary)]">

    <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

            <!-- Sidebar Kiri -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-[260px] bg-[var(--color-surface-sidebar)] text-[var(--color-text-sidebar-muted)] border-r border-[#134E44] flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:flex-shrink-0">
                <div class="p-5">
                    <div class="flex items-center gap-3">

                        {{-- <div class="w-8 h-8 rounded bg-emerald-500 flex items-center justify-center text-white font-bold text-lg">
                            EA
                        </div> --}}
                        <h1 class="text-white text-lg font-bold tracking-tight">E-Arsip</h1>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-2 space-y-6 sidebar-scroll">

                    <!-- Kategori 1: Alur Kerja Utama -->
                    @if(auth()->user()->hasModuleAccess('dashboard'))
                    <div>
                        {{-- <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2">Alur Kerja Utama</h3> --}}
                        <nav class="space-y-1">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                <span class="text-sm">Dashboard</span>
                            </a></nav>
                    </div>
                    @endif

                    <!-- Kategori: Input Surat -->
                    @if(auth()->user()->hasModuleAccess('input_incoming') || auth()->user()->hasModuleAccess('input_outgoing') || auth()->user()->hasModuleAccess('input_internal'))
                    <div>
                        <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2 mt-4">Input / Ambil Nomor Surat</h3>
                        <nav class="space-y-1">
                            @if(auth()->user()->hasModuleAccess('input_incoming'))
                            <a href="{{ route('registry.incoming.create') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('registry.incoming.create') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <span class="text-sm">Surat Masuk</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasModuleAccess('input_outgoing'))
                            <a href="{{ route('registry.outgoing.create') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('registry.outgoing.create') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <span class="text-sm">Surat Keluar</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasModuleAccess('input_internal'))
                            <a href="{{ route('registry.special.create') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('registry.special.create') || request()->routeIs('registry.internal.create') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                <span class="text-sm">Surat Khusus</span>
                            </a>
                            @endif
                        </nav>
                    </div>
                    @endif

                    <!-- Kategori: Kearsipan -->
                    @if(auth()->user()->hasModuleAccess('in_out_mail') || auth()->user()->hasModuleAccess('internal_memo') || auth()->user()->hasModuleAccess('archive_queue') || auth()->user()->hasModuleAccess('document_borrowing') || auth()->user()->hasModuleAccess('loan_circulation'))
                    <div>
                        <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2 mt-4">Kearsipan</h3>
                        <nav class="space-y-1">
                            @if(auth()->user()->hasModuleAccess('document_borrowing') || auth()->user()->hasModuleAccess('loan_circulation'))
                            <a href="{{ route('borrowings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('borrowings.*') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <span class="text-sm">Cari & Pinjam Surat</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasModuleAccess('archive_queue'))
                            <a href="{{ route('registry.scan-queue') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('registry.scan-queue') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-sm">Antrean Scan Tapersip</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasModuleAccess('in_out_mail'))
                            <a href="{{ route('registry.incoming.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('registry.incoming.index') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <span class="text-sm">Arsip Surat Masuk</span>
                            </a>
                            <a href="{{ route('registry.outgoing.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('registry.outgoing.index') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <span class="text-sm">Arsip Surat Keluar</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasModuleAccess('internal_memo'))
                            <a href="{{ route('registry.special.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('registry.special.index') || request()->routeIs('registry.internal.index') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                <span class="text-sm">Arsip Surat Khusus</span>
                            </a>
                            @endif</nav>
                    </div>
                    @endif

                    <!-- Kategori 3: Manajemen Arsip -->
                    @if(auth()->user()->hasModuleAccess('classification') || auth()->user()->hasModuleAccess('destruction') || auth()->user()->hasModuleAccess('audit'))
                    <div>
                        <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2">Manajemen Arsip</h3>
                        <nav class="space-y-1">
                            @if(auth()->user()->hasModuleAccess('classification'))
                            <a href="{{ route('settings.classifications') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('settings.classifications') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg>
                                <span class="text-sm">Klasifikasi & JRA</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasModuleAccess('destruction'))
                            <a href="{{ route('archive.destructions') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('archive.destructions') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span class="text-sm">Usul Pemusnahan</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasModuleAccess('audit'))
                            <a href="{{ route('settings.audit-trail') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('settings.audit-trail') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm">Audit Trail</span>
                            </a>
                            @endif</nav>
                    </div>
                    @endif

                    <!-- Kategori 4: Pengaturan & Akses -->
                    @if(auth()->user()->hasModuleAccess('roles') || auth()->user()->hasModuleAccess('master_data'))
                    <div>
                        <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2">Pengaturan</h3>
                        <nav class="space-y-1">
                            @if(auth()->user()->hasModuleAccess('roles'))
                            <a href="{{ route('settings.users') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('settings.users') || request()->routeIs('settings.roles.*') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span class="text-sm">User & Role</span>
                            </a>
                            @endif
                            @if(auth()->user()->hasModuleAccess('master_data'))
                            <a href="{{ route('settings.organization') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('settings.organization') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                <span class="text-sm">Master Data</span>
                            </a>
                            @endif</nav>
                    </div>
                    @endif
                </div>

                <div class="p-4 bg-[rgba(0,0,0,0.15)] mt-auto">
                    <div class="flex items-center text-xs text-[#94A3B8] justify-between">
                        <span>v1.0.0-rc</span>
                        <a href="#" class="hover:text-white transition-colors">Bantuan</a>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Top Navigation Bar -->
                <header class="bg-white border-b border-[var(--color-border)] h-16 flex items-center justify-between px-4 lg:px-6 flex-shrink-0 shadow-sm z-10 w-full">

                    <div class="flex items-center flex-1 gap-2 lg:gap-0">
                        <!-- Mobile Hamburguer Menu -->
                        <button @click="sidebarOpen = true" class="p-2 -ml-2 text-slate-500 rounded-md lg:hidden hover:bg-slate-100 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <!-- Global Search -->
                        <div class="flex-1 max-w-2xl flex items-center hidden md:flex">
                            <div class="relative w-full max-w-lg">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                </span>
                                <input type="text" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg pl-10 px-4 py-2 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="Cari No Surat, Pengirim, Tahun Berkas...">
                            </div>
                        </div>

                        <!-- Mobile Search Icon (visible only on small screens) -->
                        <button class="p-2 text-slate-500 rounded-md md:hidden hover:bg-slate-100 focus:outline-none ml-auto">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </button>
                    </div>

                    <!-- Right Profile & Role Switcher -->
                    <div class="flex items-center gap-2 lg:gap-4 pl-2 shrink-0">
                        @auth
                        @if(auth()->user()->isTapersip())
                            <livewire:components.tapersip-notification-listener />
                            <div class="h-6 w-px bg-slate-200 mx-1 hidden sm:block"></div>
                        @endif
                        <div class="hidden md:flex flex-col items-end">
                            <span class="text-sm font-semibold text-slate-800 leading-tight">{{ auth()->user()->name }}</span>
                            <span class="text-xs text-slate-500 font-medium">{{ auth()->user()->role === 'superadmin' ? 'IT Superadmin' : (auth()->user()->role->name ?? 'User') }}</span>
                        </div>
                        <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold border border-blue-200 cursor-pointer">
                            {{ substr(auth()->user()->name, 0, 2) }}
                        </div>
                        <div class="h-6 w-px bg-slate-200 mx-1"></div>
                        <button class="text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Switch Role
                        </button>
                        <form method="POST" action="{{ route('logout') }}" class="ml-2">
                            @csrf
                            <button type="submit" class="text-xs font-semibold bg-red-50 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg hover:bg-red-100 transition-colors">
                                Logout
                            </button>
                        </form>
                        @endauth
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[var(--color-background)] p-6">
                    <div class="max-w-7xl mx-auto w-full">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

    <!-- ============================================================== -->
    <!-- GLOBAL IN-APP TOAST BANNER CONTAINER                           -->
    <!-- (Mendengarkan event 'notify' Livewire & Flash Session Laravel) -->
    <!-- ============================================================== -->
    <div x-data="{
            toasts: [],
            add(msg, type = 'success') {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message: msg, type });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
         }"
         x-init="
            window.addEventListener('notify', (e) => {
                const data = Array.isArray(e.detail) ? e.detail[0] : e.detail;
                if (typeof data === 'string') {
                    add(data, 'success');
                } else if (data && data.message) {
                    add(data.message, data.type || 'success');
                }
            });
            @if(session()->has('success'))
                add(@js(session('success')), 'success');
            @endif
            @if(session()->has('error'))
                add(@js(session('error')), 'error');
            @endif
            @if(session()->has('warning'))
                add(@js(session('warning')), 'warning');
            @endif
            @if(session()->has('info'))
                add(@js(session('info')), 'info');
            @endif
         "
         class="fixed top-5 right-5 z-[9990] flex flex-col gap-2.5 pointer-events-none w-full max-w-sm sm:max-w-md">
        <template x-for="t in toasts" :key="t.id">
            <div x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="transform translate-y-[-10px] opacity-0 scale-95"
                 x-transition:enter-end="transform translate-y-0 opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="transform translate-y-0 opacity-100 scale-100"
                 x-transition:leave-end="transform translate-y-[-10px] opacity-0 scale-95"
                 class="pointer-events-auto bg-white rounded-xl shadow-xl border px-4 py-3 flex items-center gap-3 transition-all duration-200"
                 :class="{
                     'border-emerald-200 ring-2 ring-emerald-500/10': t.type === 'success',
                     'border-rose-200 ring-2 ring-rose-500/10': t.type === 'error',
                     'border-amber-200 ring-2 ring-amber-500/10': t.type === 'warning',
                     'border-blue-200 ring-2 ring-blue-500/10': t.type === 'info'
                 }">
                <!-- Icon Badge -->
                <div class="h-7 w-7 rounded-full flex items-center justify-center shrink-0 shadow-2xs"
                     :class="{
                         'bg-emerald-50 text-emerald-600 border border-emerald-200': t.type === 'success',
                         'bg-rose-50 text-rose-600 border border-rose-200': t.type === 'error',
                         'bg-amber-50 text-amber-600 border border-amber-200': t.type === 'warning',
                         'bg-blue-50 text-blue-600 border border-blue-200': t.type === 'info'
                     }">
                    <template x-if="t.type === 'success'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'error'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'warning'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <template x-if="t.type === 'info'">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </div>

                <!-- Toast Text Content -->
                <div class="flex-1 min-w-0 pr-1">
                    <p class="text-xs font-medium text-slate-800 leading-normal break-words" x-text="t.message"></p>
                </div>

                <!-- Close Button -->
                <button type="button"
                        @click="remove(t.id)"
                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>





