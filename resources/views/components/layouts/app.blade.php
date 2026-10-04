<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'E-Arsip Enterprise' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|plus-jakarta-sans:400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-[var(--color-background)] text-[var(--color-text-primary)]">
    
    @if(request()->routeIs('login'))
        <main>
            {{ $slot }}
        </main>
    @else
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
            <!-- Mobile sidebar backdrop -->
            <div x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

            <!-- Sidebar Kiri -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-[260px] bg-[var(--color-surface-sidebar)] text-[var(--color-text-sidebar-muted)] border-r border-[#134E44] flex flex-col transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:flex-shrink-0">
                <div class="p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-emerald-500 flex items-center justify-center text-white font-bold text-lg">
                            EA
                        </div>
                        <h1 class="text-white text-lg font-bold tracking-tight">E-Arsip Enterprise</h1>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-2 space-y-6 sidebar-scroll">
                    
                    <!-- Kategori 1: Alur Kerja Utama -->
                    <div>
                        <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2">Alur Kerja Utama</h3>
                        <nav class="space-y-1">
                            <a href="/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg relative {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white font-medium before:absolute before:right-0 before:top-2 before:bottom-2 before:w-1 before:bg-emerald-400 before:rounded-l' : 'hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors' }}">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                <span class="text-sm">Dashboard</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-sm">Registrasi Dokumen</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                <span class="text-sm">Surat Masuk</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <span class="text-sm">Surat Keluar</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                <span class="text-sm">Memo Internal</span>
                            </a>
                        </nav>
                    </div>

                    <!-- Kategori 2: Tata Kelola & Workflow -->
                    <div>
                        <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2">Tata Kelola & Workflow</h3>
                        <nav class="space-y-1">
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm">Verifikasi</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                <span class="text-sm">Approval</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <span class="w-5 h-5 flex-shrink-0 flex items-center justify-center"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg></span>
                                <span class="text-sm">Disposisi & Tindak Lanjut</span>
                            </a>
                        </nav>
                    </div>

                    <!-- Kategori 3: Manajemen Arsip -->
                    <div>
                        <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2">Manajemen Arsip</h3>
                        <nav class="space-y-1">
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/></svg>
                                <span class="text-sm">Klasifikasi & JRA</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span class="text-sm">Usul Pemusnahan</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm">Audit Trail</span>
                            </a>
                        </nav>
                    </div>

                    <!-- Kategori 4: Pengaturan & Akses -->
                    <div>
                        <h3 class="px-3 text-[11px] font-semibold text-[#94A3B8] uppercase tracking-wider mb-2">Pengaturan</h3>
                        <nav class="space-y-1">
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span class="text-sm">User & Role</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg relative hover:bg-[var(--color-surface-sidebar-hover)] hover:text-white transition-colors">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                <span class="text-sm">Master Data</span>
                            </a>
                        </nav>
                    </div>
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
                        <div class="hidden md:flex flex-col items-end">
                            <span class="text-sm font-semibold text-slate-800 leading-tight">Budi Santoso</span>
                            <span class="text-xs text-slate-500 font-medium">Ka. Divisi Operasional</span>
                        </div>
                        <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold border border-blue-200 cursor-pointer">
                            BS
                        </div>
                        <div class="h-6 w-px bg-slate-200 mx-1"></div>
                        <button class="text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-lg hover:bg-emerald-100 transition-colors flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            Switch Role
                        </button>
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
    @endif

    @livewireScripts
</body>
</html>
