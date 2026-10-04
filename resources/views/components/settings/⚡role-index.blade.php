<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 tracking-tight">Manajemen Role & Otorisasi Pengguna</h2>
            <p class="text-sm text-slate-500 mt-1">Kelola pembagian peran, hak akses matriks, dan batas kerahasiaan dokumen seluruh karyawan.</p>
        </div>
        <div class="shrink-0">
            <a href="{{ route('settings.roles.create') }}" class="w-full sm:w-auto bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-hover)] font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Role Baru
            </a>
        </div>
    </div>

    <!-- Data Table Role -->
    <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden flex flex-col">
        <!-- Table Toolbar -->
        <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <!-- Search -->
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" class="w-full bg-white border border-slate-200 text-sm rounded-lg pl-9 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="Cari nama role...">
            </div>
            <!-- Filter -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select class="w-full sm:w-auto bg-white border border-slate-200 text-sm text-slate-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    <option>Semua Status</option>
                    <option>Aktif Saja</option>
                    <option>Non-aktif</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-xs font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                        <th class="px-5 py-3 w-1/3">Nama Role</th>
                        <th class="px-5 py-3 w-1/4 items-center">Jumlah Pengguna</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--color-border)]">
                    <!-- Row 1 -->
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 align-middle">
                            <div class="font-bold text-slate-800">Admin Tapersip</div>
                            <div class="text-xs text-slate-500 mt-0.5 max-w-sm line-clamp-1">Akses administratif penuh pengelolaan arsip dan sistem.</div>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center justify-center bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200 px-2 py-1 rounded text-xs">
                                4 Akun Aktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                Aktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle text-right">
                            <a href="{{ route('settings.roles.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 rounded-md text-xs font-medium transition-colors shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Atur Matriks
                            </a>
                        </td>
                    </tr>
                    <!-- Row 2 -->
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 align-middle">
                            <div class="font-bold text-slate-800">Jajaran Direksi</div>
                            <div class="text-xs text-slate-500 mt-0.5 max-w-sm line-clamp-1">Level atas eksekutif. Akses eksklusif dokumen rahasia.</div>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center justify-center bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200 px-2 py-1 rounded text-xs">
                                6 Akun Aktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                Aktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle text-right">
                            <a href="{{ route('settings.roles.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 rounded-md text-xs font-medium transition-colors shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Atur Matriks
                            </a>
                        </td>
                    </tr>
                    <!-- Row 3 -->
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 align-middle">
                            <div class="font-bold text-slate-800">Kepala Divisi / Departemen</div>
                            <div class="text-xs text-slate-500 mt-0.5 max-w-sm line-clamp-1">Manajer lapis kedua. Dapat menyetujui surat keluar/memo.</div>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center justify-center bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200 px-2 py-1 rounded text-xs">
                                25 Akun Aktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                Aktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle text-right">
                            <a href="{{ route('settings.roles.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 rounded-md text-xs font-medium transition-colors shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Atur Matriks
                            </a>
                        </td>
                    </tr>
                    <!-- Row 4 (Inactive) -->
                    <tr class="hover:bg-slate-50 opacity-75 transition-colors">
                        <td class="px-5 py-4 align-middle">
                            <div class="font-bold text-slate-600">Internship / Outsourcing</div>
                            <div class="text-xs text-slate-500 mt-0.5 max-w-sm line-clamp-1">Staf sementara. Akses lihat dokumen biasa terbatas.</div>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center justify-center bg-slate-100 text-slate-500 font-medium border border-slate-200 px-2 py-1 rounded text-xs">
                                0 Akun Aktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-slate-100 text-slate-600 border-slate-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
                                Inaktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle text-right">
                            <a href="{{ route('settings.roles.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 rounded-md text-xs font-medium transition-colors shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Atur Matriks
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination Mock -->
        <div class="px-5 py-3 border-t border-[var(--color-border)] bg-white flex items-center justify-between text-xs text-slate-500">
            <span>Menampilkan 1 hingga 4 dari 4 role</span>
            <div class="flex items-center gap-1">
                <button class="px-2 py-1 border border-slate-200 text-slate-400 rounded cursor-not-allowed">Halaman Sebelumnya</button>
                <button class="px-2 py-1 border border-slate-200 text-slate-700 bg-slate-50 rounded hover:bg-slate-100">Halaman Berikutnya</button>
            </div>
        </div>
    </div>
</div>