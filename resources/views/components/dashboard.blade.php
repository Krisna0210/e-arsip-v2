<?php

use Livewire\Component;

new class extends Component {
};
?>

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Dashboard & Antrean Tugas</h2>
                    <p class="text-sm text-slate-500 mt-1">Ringkasan status dokumen dan tindak lanjut harian Anda.</p>
                </div>
                <div class="flex gap-2">
                    <button class="bg-white border text-slate-700 border-slate-200 hover:bg-slate-50 font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Registrasi Baru
                    </button>
                    <button class="bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-hover)] font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Export Laporan
                    </button>
                </div>
            </div>

        <!-- Bento Cards - 6 Kolom Grid (Wrapped) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <!-- Card 1: Verifikasi -->
            <div class="bg-white border border-[var(--color-border)] rounded-xl p-5 shadow-xs relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-start justify-between mb-2">
                    <div class="text-[0.75rem] font-semibold text-blue-600 uppercase tracking-widest leading-tight">Menunggu Verifikasi</div>
                    <span class="inline-flex shrink-0 ml-2 items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200">Urgent</span>
                </div>
                <div class="flex items-baseline gap-2 mb-3">
                    <span class="text-3xl font-bold text-slate-900">12</span>
                    <span class="text-xs font-medium text-slate-500">Berkas</span>
                </div>
                <div class="flex items-center text-[11px] text-red-600 font-medium bg-red-50 py-1 px-2 rounded w-fit mb-4 border border-red-100">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    3 Melewati Batas SLA
                </div>
                <button class="w-full text-center text-sm font-medium text-blue-700 bg-white border border-blue-200 py-1.5 rounded-md hover:bg-blue-50 transition-colors">Tinjau Antrean</button>
            </div>

            <!-- Card 2: Approval -->
            <div class="bg-white border border-[var(--color-border)] rounded-xl p-5 shadow-xs relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-16 h-16 bg-amber-50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-start justify-between mb-2">
                    <div class="text-[0.75rem] font-semibold text-amber-600 uppercase tracking-widest leading-tight">Menunggu Approval</div>
                </div>
                <div class="flex items-baseline gap-2 mb-3">
                    <span class="text-3xl font-bold text-slate-900">5</span>
                    <span class="text-xs font-medium text-slate-500">Berkas</span>
                </div>
                <div class="flex items-center text-[11px] text-amber-600 font-medium bg-amber-50 py-1 px-2 rounded w-fit mb-4 border border-amber-100">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    2 Mendekati Tenggat
                </div>
                <button class="w-full text-center text-sm font-medium text-amber-700 bg-white border border-amber-200 py-1.5 rounded-md hover:bg-amber-50 transition-colors">Periksa Persetujuan</button>
            </div>

            <!-- Card 3: Ditolak/Revisi -->
            <div class="bg-[var(--color-status-rejected-bg,#FEF2F2)] border border-[var(--color-status-rejected-border,#FECACA)] rounded-xl p-5 shadow-xs relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-16 h-16 bg-red-100/50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-start justify-between mb-2">
                    <div class="text-[0.75rem] font-semibold text-red-600 uppercase tracking-widest leading-tight">Ditolak / Butuh Revisi</div>
                </div>
                <div class="flex items-baseline gap-2 mb-3">
                    <span class="text-3xl font-bold text-red-600">3</span>
                    <span class="text-xs font-medium text-red-400">Berkas</span>
                </div>
                <div class="flex items-center text-[11px] text-red-700 font-medium bg-white/60 py-1 px-2 rounded w-fit mb-4 border border-red-200">
                    Perlu Perbaikan Segera
                </div>
                <button class="w-full text-center text-sm font-medium text-red-700 bg-white border border-red-200 py-1.5 rounded-md hover:bg-red-50 transition-colors">Tindak Lanjuti Penolakan</button>
            </div>

            <!-- Card 4: Disposisi Aktif -->
            <div class="bg-white border border-[var(--color-border)] rounded-xl p-5 shadow-xs relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-16 h-16 bg-emerald-50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-start justify-between mb-2">
                    <div class="text-[0.75rem] font-semibold text-emerald-600 uppercase tracking-widest leading-tight">Disposisi Aktif</div>
                </div>
                <div class="flex items-baseline gap-2 mb-3">
                    <span class="text-3xl font-bold text-slate-900">8</span>
                    <span class="text-xs font-medium text-slate-500">Bawahan</span>
                </div>
                <div class="flex items-center text-[11px] text-emerald-700 font-medium bg-emerald-50 py-1 px-2 rounded w-fit mb-4 border border-emerald-200">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Semua Disposisi Lancar
                </div>
                <button class="w-full text-center text-sm font-medium text-emerald-700 bg-white border border-emerald-200 py-1.5 rounded-md hover:bg-emerald-50 transition-colors">Pantau Disposisi</button>
            </div>

            <!-- Card 5: Follow Up Tugas -->
            <div class="bg-white border border-[var(--color-border)] rounded-xl p-5 shadow-xs relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-16 h-16 bg-cyan-50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-start justify-between mb-2">
                    <div class="text-[0.75rem] font-semibold text-cyan-600 uppercase tracking-widest leading-tight">Follow-up / Tugas Saya</div>
                </div>
                <div class="flex items-baseline gap-2 mb-3">
                    <span class="text-3xl font-bold text-slate-900">4</span>
                    <span class="text-xs font-medium text-slate-500">Tugas Belum Selesai</span>
                </div>
                <div class="flex items-center text-[11px] text-amber-600 font-medium bg-amber-50 py-1 px-2 rounded w-fit mb-4 border border-amber-200">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    1 Butuh Konfirmasi
                </div>
                <button class="w-full text-center text-sm font-medium text-cyan-700 bg-white border border-cyan-200 py-1.5 rounded-md hover:bg-cyan-50 transition-colors">Lihat Tugas</button>
            </div>

            <!-- Card 6: Retensi -->
            <div class="bg-white border border-[var(--color-border)] rounded-xl p-5 shadow-xs relative overflow-hidden group">
                <div class="absolute right-0 top-0 w-16 h-16 bg-purple-50 rounded-bl-full -z-10 group-hover:scale-110 transition-transform"></div>
                <div class="flex items-start justify-between mb-2">
                    <div class="text-[0.75rem] font-semibold text-purple-600 uppercase tracking-widest leading-tight">Mendekati Expiry Retensi</div>
                    <span class="inline-flex shrink-0 ml-2 items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200">JRA</span>
                </div>
                <div class="flex items-baseline gap-2 mb-3">
                    <span class="text-3xl font-bold text-slate-900">27</span>
                    <span class="text-xs font-medium text-slate-500">Arsip</span>
                </div>
                <div class="flex items-center text-[11px] text-slate-600 font-medium bg-slate-50 py-1 px-2 rounded w-fit mb-4 border border-slate-200">
                    Dalam 30 Hari Mendatang
                </div>
                <button class="w-full text-center text-sm font-medium text-purple-700 bg-white border border-purple-200 py-1.5 rounded-md hover:bg-purple-50 transition-colors">Tinjau Pemusnahan</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <!-- Widget Tugas Saya (Tabel) -->
            <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden lg:col-span-2 flex flex-col">
                <div class="px-6 py-4 border-b border-[var(--color-border)] flex flex-wrap items-center justify-between bg-slate-50 gap-2">
                    <div>
                        <h3 class="font-bold text-slate-800">Widget Tugas Saya</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Antrean revisi dan penyelesaian dokumen Anda.</p>
                    </div>
                </div>
                <div class="overflow-x-auto flex-1 h-full">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-[0.75rem] font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                                <th class="px-5 py-3 w-1/4">Nomor & Sifat</th>
                                <th class="px-5 py-3 w-1/3">Perihal & Jenis Dokumen</th>
                                <th class="px-5 py-3 w-1/5">Batas Waktu SLA</th>
                                <th class="px-5 py-3 w-1/5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-[var(--color-border)]">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono text-[13px] font-semibold text-slate-700">REG-202610-0014</div>
                                    <div class="inline-flex mt-1.5 items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">Penting</div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="font-medium text-slate-800 mb-1 leading-snug">Permohonan Data Pajak Kendaraan Bermotor Triwulan III</div>
                                    <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        Surat Masuk
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center text-xs text-red-600 font-medium">
                                        <svg class="w-3.5 h-3.5 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Overdue - Sisa 0 Jam
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top text-right">
                                    <button class="bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-hover)] font-medium px-3 py-1.5 rounded-md text-xs transition-colors shadow-sm inline-flex items-center">
                                        Tindak Lanjut
                                    </button>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono text-[13px] font-semibold text-slate-700">1/-OPS-PM.05/X/2026</div>
                                    <div class="inline-flex mt-1.5 items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">Rahasia</div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="font-medium text-slate-800 mb-1 leading-snug">Laporan Kegiatan Audit Internal Tahunan 2026</div>
                                    <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Surat Keluar
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center text-xs text-slate-600 font-medium">
                                        <svg class="w-3.5 h-3.5 mr-1 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        24 Okt 2026 (Sisa 3 Hari)
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top text-right">
                                    <button class="bg-white border text-slate-700 border-slate-300 hover:bg-slate-50 font-medium px-3 py-1.5 rounded-md text-xs transition-colors shadow-sm mb-1 w-full text-center">
                                        Review Draft
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Komponen Log Aktivitas Terakhir -->
            <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden flex flex-col h-full lg:col-span-1">
                <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50">
                    <h3 class="font-bold text-slate-800 text-sm">Log Aktivitas Terakhir</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Jejak audit dokumen terkait.</p>
                </div>
                <div class="p-5 overflow-y-auto flex-1">
                    <div class="relative border-l border-slate-200 ml-3 space-y-6">
                        
                        <!-- Timeline Item 1 -->
                        <div class="relative pl-6">
                            <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-4 ring-white"></span>
                            <div class="text-[10px] font-bold text-emerald-600 mb-0.5 uppercase tracking-wide">Approved</div>
                            <div class="text-sm font-semibold text-slate-800 leading-snug">Anda menyetujui Surat Keluar 1/-HRD/X/2026</div>
                            <div class="text-xs text-slate-500 mt-1">Hari ini, 09:24 WIB</div>
                        </div>

                        <!-- Timeline Item 2 -->
                        <div class="relative pl-6">
                            <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full bg-blue-500 ring-4 ring-white"></span>
                            <div class="text-[10px] font-bold text-blue-600 mb-0.5 uppercase tracking-wide">Disposisi</div>
                            <div class="text-sm font-semibold text-slate-800 leading-snug">Disposisi Memo M-090/DIR/X diteruskan ke Anda oleh Direktur.</div>
                            <div class="text-xs text-slate-500 mt-1">Kemarin, 16:15 WIB</div>
                        </div>

                        <!-- Timeline Item 3 -->
                        <div class="relative pl-6">
                            <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-4 ring-white"></span>
                            <div class="text-[10px] font-bold text-red-600 mb-0.5 uppercase tracking-wide">Rejected</div>
                            <div class="text-sm font-semibold text-slate-800 leading-snug">Draft PKS No. 04 ditarik kembali / ditolak oleh Div. Hukum.</div>
                            <div class="text-xs text-slate-500 mt-1">2 Okt 2026, 11:30 WIB</div>
                        </div>

                        <!-- Timeline Item 4 -->
                        <div class="relative pl-6">
                            <span class="absolute -left-[5px] top-1.5 w-2.5 h-2.5 rounded-full bg-purple-500 ring-4 ring-white"></span>
                            <div class="text-[10px] font-bold text-purple-600 mb-0.5 uppercase tracking-wide">Archive</div>
                            <div class="text-sm font-semibold text-slate-800 leading-snug">Masa Retensi Surat B-01/X habis (Menunggu Jadwal Musnah).</div>
                            <div class="text-xs text-slate-500 mt-1">30 Sep 2026</div>
                        </div>

                    </div>
                    <button class="w-full mt-6 text-center text-xs font-semibold text-slate-600 border border-slate-200 rounded py-2 hover:bg-slate-50 transition">
                        Buka Audit Trail Penuh
                    </button>
                </div>
            </div>
        </div>
            
        </div>
