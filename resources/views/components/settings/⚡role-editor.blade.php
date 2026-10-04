<?php

use Livewire\Component;

new class extends Component {
    // Component logic
};
?>

<div class="relative pb-24">
    <!-- Breadcrumb & Header -->
    <div class="mb-6">
        <a href="{{ route('settings.roles.index') }}" class="inline-flex items-center text-xs font-medium text-slate-500 hover:text-emerald-700 transition-colors mb-3">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Daftar Role
        </a>
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">Form Konfigurasi Matriks Peran</h2>
        <p class="text-sm text-slate-500 mt-1">Lakukan penyetelan silang antara tindakan yang dizinkan dan sifat dokumen.</p>
    </div>

    <div class="space-y-6 max-w-5xl">
        
        <!-- Blok 1: Informasi Dasar -->
        <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50">
                <h3 class="font-bold text-slate-800 text-sm">1. Informasi Dasar Peran</h3>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-1.5 sm:col-span-2 lg:col-span-1">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide">Nama Role</label>
                    <input type="text" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="Cth: Kadiv SDM & Operasional" value="Kepala Divisi / Departemen">
                </div>
                <div class="space-y-1.5 sm:col-span-2 lg:col-span-1">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide">Status Peran</label>
                    <select class="w-full bg-slate-50 border border-slate-200 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="active" selected>Aktif Beroperasi</option>
                        <option value="inactive">Non-aktifkan (Suspend)</option>
                    </select>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide">Deskripsi Tugas (Opsional)</label>
                    <textarea rows="2" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="Deskripsikan cakupan otorisasi peran ini secara singkat...">Manajer lapis kedua. Dapat menyetujui surat keluar / memo pada departemen terkait.</textarea>
                </div>
            </div>
        </div>

        <!-- Blok 2: Matriks Akses Arsip -->
        <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm">2. Matriks Otorisasi Sifat Dokumen</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Tentukan aksi apa saja yang boleh dilakukan pengguna pada tiap tingkat kerahasiaan berkas.</p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-[var(--color-background)] border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="px-6 py-4 w-1/4">Tindakan / Hak Akses</th>
                            <!-- Kolom Biasa -->
                            <th class="px-6 py-4 text-center border-l border-slate-200 bg-emerald-50/30">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-[11px] font-bold text-emerald-700 border border-emerald-200 bg-white">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Biasa
                                </span>
                            </th>
                            <!-- Kolom Penting -->
                            <th class="px-6 py-4 text-center border-l border-slate-200 bg-amber-50/30">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-[11px] font-bold text-amber-700 border border-amber-200 bg-white">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Penting
                                </span>
                            </th>
                            <!-- Kolom Rahasia -->
                            <th class="px-6 py-4 text-center border-l border-slate-200 bg-red-50/30">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-[11px] font-bold text-red-700 border border-red-200 bg-white">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Rahasia
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        @php
                            $actions = [
                                ['id' => 'view', 'label' => 'Melihat Dokumen', 'desc' => 'Dapat mencari dan melihat metadata & PDF dokumen.'],
                                ['id' => 'download', 'label' => 'Mengunduh Berkas', 'desc' => 'Dapat men-download lampiran file fisik (PDF).'],
                                ['id' => 'create', 'label' => 'Mencatat/Create Baru', 'desc' => 'Dapat meregistrasikan atau menyusun draft surat.'],
                                ['id' => 'edit', 'label' => 'Ubah Metadata (Edit)', 'desc' => 'Dapat merubah atribut/metadata sebelum atau saat final.'],
                                ['id' => 'verify', 'label' => 'Verifikasi', 'desc' => 'Bertindak sebagai verifikator keabsahan tata naskah.'],
                                ['id' => 'approve', 'label' => 'Persetujuan (Approval)', 'desc' => 'Memiliki hak setuju/tolak konten atau TTE dokumen.'],
                                ['id' => 'disposition', 'label' => 'Delegasi Disposisi', 'desc' => 'Dapat meneruskan dokumen & membuat intruksi baru.'],
                                ['id' => 'archive', 'label' => 'Review Retensi Arsip', 'desc' => 'Dapat mengeksekusi nasib akhir arsip (JRA/Pemusnahan).'],
                            ];
                        @endphp
                        
                        @foreach($actions as $action)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3 border-r border-slate-200 bg-white">
                                <div class="font-bold text-slate-800">{{ $action['label'] }}</div>
                                <div class="text-[10px] text-slate-500 leading-tight mt-0.5">{{ $action['desc'] }}</div>
                            </td>
                            <!-- Biasa Toggle -->
                            <td class="px-6 py-3 text-center align-middle border-r border-slate-100">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[var(--color-primary)]"></div>
                                </label>
                            </td>
                            <!-- Penting Toggle -->
                            <td class="px-6 py-3 text-center align-middle border-r border-slate-100">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" @if(in_array($action['id'], ['view', 'download', 'approve'])) checked @endif>
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[var(--color-primary)]"></div>
                                </label>
                            </td>
                            <!-- Rahasia Toggle -->
                            <td class="px-6 py-3 text-center align-middle">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" @if(in_array($action['id'], ['archive'])) disabled @endif>
                                    <div class="w-9 h-5 {{ in_array($action['id'], ['archive']) ? 'bg-slate-100 opacity-50 cursor-not-allowed' : 'bg-slate-200 peer-focus:outline-none' }} rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[var(--color-primary)]"></div>
                                </label>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="px-5 py-3 border-t border-[var(--color-border)] bg-yellow-50 text-xs text-yellow-800 flex items-start gap-2">
                <svg class="w-4 h-4 shrink-0 mt-0.5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><b>Perhatian:</b> Pemberian akses kepada Sifat "Rahasia" akan mencabut batas blokir internal. Pastikan Anda mengesahkan prosedur ini sesuai SOP keamanan Direksi.</span>
            </div>
        </div>

        <!-- Blok 3: Izin Menu Modul Navigasi -->
        <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-sm">3. Hak Akses Visibilitas Modul Navigasi</h3>
                </div>
                <!-- Select All Checkbox -->
                <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-emerald-700">
                    <input type="checkbox" class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                    Pilih Semua Menu
                </label>
            </div>
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Group 1 -->
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Alur Kerja Utama</div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Dashboard</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Registrasi Baru</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Surat Masuk & Keluar</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Memo Internal</label>
                    </div>
                </div>
                <!-- Group 2 -->
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Persetujuan & Disposisi</div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Verifikator</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Pos Approval</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Panel Disposisi</label>
                    </div>
                </div>
                <!-- Group 3 -->
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Manajemen Kepatuhan</div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Klasifikasi & JRA</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Usul Pemusnahan</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Audit Trail Log</label>
                    </div>
                </div>
                <!-- Group 4 -->
                <div>
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pengaturan Inti</div>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Hak Akses Role</label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"> Master Data Reference</label>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Sticky Footer Bar -->
    <div class="fixed bottom-0 left-0 lg:left-[260px] right-0 bg-white border-t border-[var(--color-border)] px-6 py-4 flex items-center justify-between shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-20">
        <div class="text-xs text-slate-500 hidden sm:block">Perubahan terakhir disimpan: <span class="font-semibold text-slate-700">Otomatis / Belum Disimpan</span></div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <a href="{{ route('settings.roles.index') }}" class="flex-1 sm:flex-none text-center bg-white border text-slate-700 border-slate-300 hover:bg-slate-50 font-medium px-6 py-2.5 rounded-lg text-sm transition-colors cursor-pointer">
                Batalkan
            </a>
            <button class="flex-1 sm:flex-none bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-hover)] font-medium px-6 py-2.5 rounded-lg text-sm transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Simpan Matriks
            </button>
        </div>
    </div>

</div>
