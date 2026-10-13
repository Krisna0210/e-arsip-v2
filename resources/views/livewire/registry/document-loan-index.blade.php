<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-800 tracking-tight">Cari & Pinjam Berkas Arsip Fisik</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Layanan pencarian hardcopy di lemari kearsipan, permohonan peminjaman fisik, dan penerbitan tiket sirkulasi.</p>
                </div>
            </div>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Tapersip Beroperasi
            </span>
        </div>
    </div>

    <!-- Tab Navigasi Mandiri -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <!-- Tab 1: Katalog & Cari Surat -->
            <button
                type="button"
                wire:click="setTab('catalog')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'catalog' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span>Katalog & Cari Surat</span>
            </button>

            <!-- Tab 2: Pinjaman Saya -->
            <button
                type="button"
                wire:click="setTab('my_loans')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'my_loans' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Pinjaman Saya</span>
                @php
                    $myActiveCount = \App\Models\DocumentLoan::where('user_id', auth()->id())->whereIn('status', ['requested', 'borrowed'])->count();
                @endphp
                @if($myActiveCount > 0)
                    <span class="ml-1 px-2 py-0.5 text-[11px] font-bold rounded-full bg-amber-100 text-amber-800">
                        {{ $myActiveCount }}
                    </span>
                @endif
            </button>

            <!-- Tab 3: Sirkulasi & Persetujuan Tapersip (Khusus Pengelola Kearsipan) -->
            @if($this->canManageCirculation)
            <button
                type="button"
                wire:click="setTab('circulation')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors {{ $activeTab === 'circulation' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <span>Sirkulasi & Persetujuan Tapersip</span>
                @php
                    $pendingRequestsCount = \App\Models\DocumentLoan::where('status', 'requested')->count();
                @endphp
                @if($pendingRequestsCount > 0)
                    <span class="ml-1 px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-600 text-white animate-pulse">
                        {{ $pendingRequestsCount }}
                    </span>
                @endif
            </button>
            @endif
        </nav>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: KATALOG & CARI SURAT                -->
    <!-- ========================================== -->
    @if($activeTab === 'catalog')
    <div class="space-y-5">
        <!-- Panel Filter & Pencarian -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Input Kata Kunci -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Kata Kunci Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari Nomor Surat, Perihal, Pengirim, atau Penerima..."
                            class="block w-full rounded-lg border border-slate-200 pl-9 pr-3 py-2 text-sm placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] bg-slate-50 focus:bg-white transition-colors"
                        >
                    </div>
                </div>

                <!-- Dropdown Jenis Naskah -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Jenis Naskah</label>
                    <select wire:model.live="filterType" class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] bg-slate-50">
                        <option value="">Semua Jenis Naskah</option>
                        <option value="incoming">Surat Masuk</option>
                        <option value="outgoing">Surat Keluar</option>
                        <option value="internal">Memo Internal</option>
                    </select>
                </div>

                <!-- Dropdown Sifat Dokumen -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Sifat Kerahasiaan</label>
                    <select wire:model.live="filterSifat" class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] bg-slate-50">
                        <option value="">Semua Sifat</option>
                        <option value="biasa">Biasa</option>
                        <option value="penting">Penting</option>
                        <option value="rahasia">Rahasia</option>
                    </select>
                </div>
            </div>

            <!-- Baris Kedua Filter: Rentang Tanggal & Tombol Reset -->
            <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <span class="text-xs font-semibold text-slate-500 whitespace-nowrap">Rentang Tanggal Surat:</span>
                    <input
                        type="date"
                        wire:model.live="filterDateStart"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] bg-slate-50"
                    >
                    <span class="text-xs text-slate-400">s/d</span>
                    <input
                        type="date"
                        wire:model.live="filterDateEnd"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] bg-slate-50"
                    >
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-800 transition-colors">
                        Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel Hasil Pencarian Katalog -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[850px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="px-5 py-3.5 w-1/4">Identitas Dokumen</th>
                            <th class="px-5 py-3.5 w-1/3">Perihal & Distribusi</th>
                            <th class="px-5 py-3.5 text-center">Lokasi Surat</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($catalogDocuments as $doc)
                            @php
                                $isBorrowed = $doc->isCurrentlyBorrowed();
                                $isRequested = $doc->loans()->where('status', 'requested')->exists();
                                $activeLoan = $doc->activeLoan;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Identitas Dokumen -->
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono font-bold text-slate-800 text-[13px] leading-tight">
                                        {{ $doc->number }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            {{ $doc->type === 'incoming' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($doc->type === 'outgoing' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                            {{ $doc->type === 'incoming' ? 'Surat Masuk' : ($doc->type === 'outgoing' ? 'Surat Keluar' : 'Memo Internal') }}
                                        </span>
                                        <span class="text-xs text-slate-400">
                                            {{ $doc->document_date ? \Carbon\Carbon::parse($doc->document_date)->translatedFormat('d M Y') : '-' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Perihal & Distribusi -->
                                <td class="px-5 py-4 align-top">
                                    <div x-data="{ expanded: false }">
                                        <div
                                            class="text-slate-800 font-medium leading-snug transition-all"
                                            :class="expanded ? 'whitespace-normal break-words' : 'line-clamp-2'"
                                            title="{{ $doc->subject }}"
                                        >
                                            {{ $doc->subject }}
                                        </div>
                                        @if(mb_strlen($doc->subject ?? '') > 75)
                                            <button
                                                type="button"
                                                @click="expanded = !expanded"
                                                class="text-[11px] font-bold text-[var(--color-primary)] hover:text-emerald-700 hover:underline mt-1 inline-flex items-center gap-0.5 focus:outline-none cursor-pointer"
                                            >
                                                <span x-text="expanded ? 'Show Less ▴' : 'Show More ▾'"></span>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1.5 flex items-center gap-2">
                                        @if($doc->sender)
                                            <span class="truncate max-w-[200px]">Dari: <strong class="text-slate-700 font-semibold">{{ $doc->sender }}</strong></span>
                                        @elseif($doc->unit)
                                            <span class="truncate max-w-[200px]">Unit: <strong class="text-slate-700 font-semibold">{{ $doc->unit->name }}</strong></span>
                                        @endif
                                        @if($doc->recipient_name)
                                            <span class="text-slate-300">|</span>
                                            <span class="truncate max-w-[200px]">Ke: <strong class="text-slate-700 font-semibold">{{ $doc->recipient_name }}</strong></span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Lokasi Fisik Loker -->
                                <td class="px-5 py-4 align-top">
                                    @if($doc->folder)
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                            </svg>
                                            <span class="truncate max-w-[180px]" title="{{ $doc->folder->name }}">{{ $doc->folder->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Belum ditentukan</span>
                                    @endif
                                </td>

                                <!-- Status Ketersediaan Fisik -->
                                <td class="px-5 py-4 align-top text-center">
                                    @if($isBorrowed)
                                        <span class="inline-flex flex-col items-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                Dipinjam
                                            </span>
                                            @if($activeLoan && $activeLoan->user)
                                                <span class="text-[10px] text-amber-700 mt-1 max-w-[140px] truncate" title="Dipinjam oleh {{ $activeLoan->user->name }}">
                                                    Oleh: {{ $activeLoan->user->name }}
                                                </span>
                                            @endif
                                        </span>
                                    @elseif($isRequested)
                                        <span class="inline-flex flex-col items-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                Diajukan Pinjam
                                            </span>
                                            <span class="text-[10px] text-slate-400 mt-1">Menunggu Tapersip</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Tersedia
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 align-top text-right">
                                    @if(!$isBorrowed && !$isRequested)
                                        <button
                                            type="button"
                                            wire:click="openLoanModal({{ $doc->id }})"
                                            class="inline-flex items-center gap-1.5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-2xs transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Ajukan Pinjam
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            disabled
                                            class="inline-flex items-center gap-1 bg-slate-100 text-slate-400 text-xs font-semibold px-3 py-1.5 rounded-lg cursor-not-allowed border border-slate-200">
                                            Tidak Tersedia
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-sm">
                                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Tidak ada dokumen arsip yang cocok dengan kriteria pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($catalogDocuments->hasPages())
                <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
                    {{ $catalogDocuments->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 2: PINJAMAN SAYA                       -->
    <!-- ========================================== -->
    @if($activeTab === 'my_loans')
    <div class="space-y-5">
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Daftar Pengajuan & Pinjaman Hardcopy Anda</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Pantau status fisik dokumen yang sedang Anda pinjam atau ajukan ke Tapersip.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[850px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="px-5 py-3.5">Kode Tiket</th>
                            <th class="px-5 py-3.5 w-1/3">Dokumen & Lokasi Fisik</th>
                            <th class="px-5 py-3.5">Masa Peminjaman</th>
                            <th class="px-5 py-3.5">Keperluan Pinjam</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($myLoans as $loan)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Kode Tiket -->
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono font-bold text-[var(--color-primary)] text-sm">
                                        {{ $loan->loan_code }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $loan->created_at->format('d M Y, H:i') }}
                                    </div>
                                </td>

                                <!-- Dokumen & Lokasi Fisik -->
                                <td class="px-5 py-4 align-top">
                                    <div class="font-bold text-slate-800 font-mono text-xs">
                                        {{ $loan->document->number ?? 'Tanpa Nomor' }}
                                    </div>
                                    <div x-data="{ expanded: false }">
                                        <div
                                            class="text-xs text-slate-600 mt-1 leading-snug transition-all"
                                            :class="expanded ? 'whitespace-normal break-words' : 'line-clamp-2'"
                                            title="{{ $loan->document->subject ?? '-' }}"
                                        >
                                            {{ $loan->document->subject ?? '-' }}
                                        </div>
                                        @if(mb_strlen($loan->document->subject ?? '') > 75)
                                            <button
                                                type="button"
                                                @click="expanded = !expanded"
                                                class="text-[10px] font-bold text-[var(--color-primary)] hover:text-emerald-700 hover:underline mt-0.5 inline-flex items-center gap-0.5 focus:outline-none cursor-pointer"
                                            >
                                                <span x-text="expanded ? 'Show Less ▴' : 'Show More ▾'"></span>
                                            </button>
                                        @endif
                                    </div>
                                    @if($loan->document && $loan->document->folder)
                                        <div class="mt-1.5">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                                📁 {{ $loan->document->folder->name }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Masa Peminjaman -->
                                <td class="px-5 py-4 align-top text-xs">
                                    <div class="text-slate-700">
                                        Pinjam: <strong class="font-semibold">{{ $loan->borrow_date ? $loan->borrow_date->translatedFormat('d M Y') : '-' }}</strong>
                                    </div>
                                    <div class="text-slate-700 mt-1">
                                        Kembali: <strong class="font-semibold">{{ $loan->expected_return_date ? $loan->expected_return_date->translatedFormat('d M Y') : '-' }}</strong>
                                    </div>
                                    @if($loan->returned_date)
                                        <div class="text-emerald-700 mt-1 font-semibold">
                                            Riil: {{ $loan->returned_date->translatedFormat('d M Y') }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Keperluan Pinjam -->
                                <td class="px-5 py-4 align-top text-xs text-slate-600 max-w-[200px]">
                                    <p class="line-clamp-2" title="{{ $loan->purpose }}">{{ $loan->purpose }}</p>
                                    @if($loan->notes)
                                        <div class="text-[10px] text-slate-400 mt-1 italic">
                                            Catatan: {{ $loan->notes }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 align-top text-center">
                                    @if($loan->status === 'requested')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Menunggu
                                        </span>
                                        <div class="text-[10px] text-slate-400 mt-1">Bawa tiket ke Tapersip</div>
                                    @elseif($loan->status === 'borrowed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Sedang Dipinjam
                                        </span>
                                    @elseif($loan->status === 'returned')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Sudah Kembali
                                        </span>
                                    @elseif($loan->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                            Ditolak Tapersip
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 align-top text-right">
                                    @if(in_array($loan->status, ['requested', 'borrowed']))
                                        <button
                                            type="button"
                                            wire:click="openTicketModal({{ $loan->id }})"
                                            title="Cetak Ulang Tiket"
                                            class="inline-flex items-center gap-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold px-3 py-1.5 rounded-lg shadow-2xs transition-colors">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-slate-400 font-mono text-sm inline-block select-none pr-3">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">
                                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Anda belum memiliki riwayat pengajuan peminjaman berkas fisik.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($myLoans->hasPages())
                <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
                    {{ $myLoans->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- ========================================== -->
    <!-- TAB 3: SIRKULASI & PERSETUJUAN TAPERSIP    -->
    <!-- ========================================== -->
    @if($activeTab === 'circulation' && $this->canManageCirculation)
    <div class="space-y-5">
        <!-- Panel Pencarian Cepat Sirkulasi -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="w-full sm:w-80 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="circulationSearch"
                    placeholder="Scan / Ketik Kode Tiket, No Surat, atau Nama Peminjam..."
                    class="block w-full rounded-lg border border-slate-200 pl-9 pr-3 py-2 text-xs placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] bg-slate-50 focus:bg-white"
                >
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <label class="text-xs font-semibold text-slate-500 whitespace-nowrap">Filter Status:</label>
                <select wire:model.live="circulationStatus" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 bg-slate-50 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                    <option value="all">Semua Status Sirkulasi</option>
                    <option value="requested">Menunggu Serah Terima (Tiket Masuk)</option>
                    <option value="borrowed">Sedang Dipinjam Staf</option>
                    <option value="returned">Selesai / Kembali ke Loker</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
        </div>

        <!-- Tabel Meja Kerja Tapersip -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="px-5 py-3.5">Tiket Sirkulasi</th>
                            <th class="px-5 py-3.5 w-1/4">Dokumen & Loker Fisik</th>
                            <th class="px-5 py-3.5">Peminjam</th>
                            <th class="px-5 py-3.5 w-1/5">Keperluan Pinjam</th>
                            <th class="px-5 py-3.5">Masa Peminjaman</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Tindakan Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($circulationLoans as $loan)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Tiket Sirkulasi -->
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono font-bold text-[var(--color-primary)] text-sm">
                                        {{ $loan->loan_code }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        Diajukan: {{ $loan->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </td>

                                <!-- Dokumen & Loker Fisik -->
                                <td class="px-5 py-4 align-top">
                                    <div class="font-bold text-slate-800 font-mono text-xs">
                                        {{ $loan->document->number ?? 'Tanpa Nomor' }}
                                    </div>
                                    <div x-data="{ expanded: false }">
                                        <div
                                            class="text-xs text-slate-600 mt-1 leading-snug transition-all"
                                            :class="expanded ? 'whitespace-normal break-words' : 'line-clamp-2'"
                                            title="{{ $loan->document->subject ?? '-' }}"
                                        >
                                            {{ $loan->document->subject ?? '-' }}
                                        </div>
                                        @if(mb_strlen($loan->document->subject ?? '') > 75)
                                            <button
                                                type="button"
                                                @click="expanded = !expanded"
                                                class="text-[10px] font-bold text-[var(--color-primary)] hover:text-emerald-700 hover:underline mt-0.5 inline-flex items-center gap-0.5 focus:outline-none cursor-pointer"
                                            >
                                                <span x-text="expanded ? 'Show Less ▴' : 'Show More ▾'"></span>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        @if($loan->document && $loan->document->folder)
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                                📁 Loker: {{ $loan->document->folder->name }}
                                            </span>
                                        @else
                                            <span class="text-[11px] text-red-500 font-semibold italic">⚠️ Belum ada loker fisik</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Peminjam -->
                                <td class="px-5 py-4 align-top text-xs">
                                    <div class="font-bold text-slate-800">
                                        {{ $loan->user->name ?? 'User Terhapus' }}
                                    </div>
                                    <div class="text-slate-500 mt-0.5">
                                        {{ $loan->user->position->name ?? 'Staf' }}
                                    </div>
                                    <div class="text-slate-400 mt-0.5">
                                        {{ $loan->user->unit->name ?? '-' }}
                                    </div>
                                </td>

                                <!-- Keperluan Pinjam -->
                                <td class="px-5 py-4 align-top text-xs">
                                    <div class="text-slate-800 font-medium whitespace-normal break-words leading-relaxed max-w-xs">
                                        {{ $loan->purpose }}
                                    </div>
                                    @if($loan->notes)
                                        <div class="mt-1.5 p-1.5 rounded bg-slate-100 text-[11px] text-slate-500 italic border border-slate-200">
                                            Catatan: {{ $loan->notes }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Masa Peminjaman -->
                                <td class="px-5 py-4 align-top text-xs">
                                    <div>Pinjam: <strong>{{ $loan->borrow_date ? $loan->borrow_date->format('d/m/Y') : '-' }}</strong></div>
                                    <div class="mt-0.5">Dikembalikan: <strong>{{ $loan->expected_return_date ? $loan->expected_return_date->format('d/m/Y') : '-' }}</strong></div>
                                    @if($loan->status === 'borrowed' && now()->isAfter($loan->expected_return_date))
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">
                                            Jatuh Tempo
                                        </span>
                                    @endif
                                    @if($loan->returned_date)
                                        <div class="mt-1 text-emerald-700 font-semibold">
                                            Kembali: {{ $loan->returned_date->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 align-top text-center">
                                    @if($loan->status === 'requested')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Menunggu Serah Terima
                                        </span>
                                    @elseif($loan->status === 'borrowed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Sedang Dipinjam
                                        </span>
                                    @elseif($loan->status === 'returned')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Sudah di Loker
                                        </span>
                                    @elseif($loan->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                            Ditolak
                                        </span>
                                    @endif
                                </td>

                                <!-- Tindakan Petugas (Dropdown) -->
                                <td class="px-5 py-4 align-top text-right">
                                    @if(in_array($loan->status, ['requested', 'borrowed']))
                                        <div class="relative inline-block text-left" x-data="{ open: false }">
                                            <!-- Tombol Pemicu Dropdown -->
                                            <button
                                                type="button"
                                                @click="open = !open"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 active:bg-slate-100 transition-colors shadow-2xs focus:outline-none"
                                            >
                                                <span>Pilihan Aksi</span>
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>

                                            <!-- Menu Dropdown (Tanpa Ikon pada Teks Opsi) -->
                                            <div
                                                x-show="open"
                                                @click.outside="open = false"
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="transform opacity-0 scale-95"
                                                x-transition:enter-end="transform opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="transform opacity-100 scale-100"
                                                x-transition:leave-end="transform opacity-0 scale-95"
                                                class="absolute right-0 top-full mt-1.5 w-48 rounded-xl bg-white shadow-xl border border-slate-200 py-1 z-30 divide-y divide-slate-100 focus:outline-none text-left"
                                                style="display: none;"
                                            >
                                                <div class="py-1">
                                                    <!-- Opsi 1: Lihat & Cetak Tiket -->
                                                    <button
                                                        type="button"
                                                        wire:click="openTicketModal({{ $loan->id }})"
                                                        @click="open = false"
                                                        class="w-full text-left px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors"
                                                    >
                                                        Cetak Tiket
                                                    </button>
                                                </div>

                                                @if($loan->status === 'requested')
                                                    <div class="py-1">
                                                        <!-- Opsi 2: Serahkan Berkas -->
                                                        <button
                                                            type="button"
                                                            wire:click="approveLoan({{ $loan->id }})"
                                                            wire:confirm="Konfirmasi penyerahan berkas fisik kepada pemohon?"
                                                            @click="open = false"
                                                            class="w-full text-left px-4 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition-colors"
                                                        >
                                                            Pinjamkan
                                                        </button>

                                                        <!-- Opsi 3: Tolak Permohonan -->
                                                        <button
                                                            type="button"
                                                            wire:click="rejectLoan({{ $loan->id }})"
                                                            wire:confirm="Tolak permohonan pinjam berkas ini?"
                                                            @click="open = false"
                                                            class="w-full text-left px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors"
                                                        >
                                                            Tolak
                                                        </button>
                                                    </div>
                                                @elseif($loan->status === 'borrowed')
                                                    <div class="py-1">
                                                        <!-- Opsi 2: Terima & Masukkan Loker -->
                                                        <button
                                                            type="button"
                                                            wire:click="openReturnModal({{ $loan->id }})"
                                                            @click="open = false"
                                                            class="w-full text-left px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50 transition-colors"
                                                        >
                                                            Terima & Masukkan Loker
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-slate-400 font-mono text-sm inline-block select-none pr-3">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-sm">
                                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Tidak ada data transaksi peminjaman dalam kategori sirkulasi ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($circulationLoans->hasPages())
                <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
                    {{ $circulationLoans->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- ============================================================== -->
    <!-- MODAL 1: FORM PENGAJUAN PINJAM (modal-loan-request)            -->
    <!-- ============================================================== -->
    <x-ui.modal title="Formulir Permohonan Pinjam Arsip Fisik" id="modal-loan-request" width="max-w-xl">
        @if($selectedDocument)
            <div class="space-y-4">
                <!-- Info Ringkas Dokumen Terpilih -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Identitas Naskah</span>
                        <span class="text-xs font-mono font-bold text-[var(--color-primary)]">{{ $selectedDocument->number }}</span>
                    </div>
                    <div class="text-xs font-semibold text-slate-800 leading-snug">
                        {{ $selectedDocument->subject }}
                    </div>
                    <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Lokasi Loker Fisik:</span>
                        <span class="font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200">
                            📁 {{ $selectedDocument->folder->name ?? 'Belum Ditentukan' }}
                        </span>
                    </div>
                </div>

                <!-- Input Tanggal & Estimasi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Tanggal Mulai Pinjam</label>
                        <input
                            type="date"
                            wire:model="borrowDate"
                            class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"
                        >
                        @error('borrowDate') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Estimasi Pengembalian</label>
                        <input
                            type="date"
                            wire:model="expectedReturnDate"
                            class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"
                        >
                        @error('expectedReturnDate') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Input Keperluan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Keperluan Peminjaman <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        wire:model="purpose"
                        rows="3"
                        placeholder="Contoh: Audit BPK / KAP, Kebutuhan Sidang Klaim Hukum, Kelengkapan Verifikasi Tender..."
                        class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] placeholder-slate-400"
                    ></textarea>
                    @error('purpose') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Petunjuk Alur -->
                <div class="bg-blue-50 border border-blue-200 text-blue-800 text-xs p-3 rounded-lg flex items-start gap-2">
                    <svg class="w-4 h-4 shrink-0 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Setelah pengajuan disimpan, sistem akan langsung menerbitkan <b>Tiket Peminjaman Hardcopy</b>. Cetak tiket tersebut dan bawa ke meja Tapersip untuk mengambil berkas fisik.</span>
                </div>
            </div>
        @endif

        <x-slot:footer>
            <button
                type="button"
                wire:click="submitLoanRequest"
                class="w-full sm:w-auto bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors inline-flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Terbitkan Tiket Pinjam
            </button>
            <button
                type="button"
                @click="show = false"
                class="w-full sm:w-auto bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                Batal
            </button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL 2: KONFIRMASI PENGEMBALIAN BERKAS (modal-loan-return)    -->
    <!-- ============================================================== -->
    <x-ui.modal title="Konfirmasi Pengembalian Berkas ke Loker" id="modal-loan-return" width="max-w-md">
        <div class="space-y-4">
            <p class="text-sm text-slate-600">
                Pastikan fisik naskah surat telah diperiksa kelengkapannya dan dikembalikan ke lemari loker arsip bersangkutan.
            </p>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Catatan Kondisi Berkas Fisik (Opsional)
                </label>
                <textarea
                    wire:model="returnNotes"
                    rows="3"
                    placeholder="Contoh: Berkas dikembalikan utuh, bersih, dan map tertata rapi..."
                    class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"
                ></textarea>
            </div>
        </div>

        <x-slot:footer>
            <button
                type="button"
                wire:click="confirmReturn"
                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors inline-flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Konfirmasi Berkas Kembali
            </button>
            <button
                type="button"
                @click="show = false"
                class="w-full sm:w-auto bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                Batal
            </button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL 3: TIKET CETAK HARDCOPY PRINT-FRIENDLY (modal-loan-ticket)-->
    <!-- ============================================================== -->
    <x-ui.modal title="Tiket Bukti Peminjaman Arsip Fisik" id="modal-loan-ticket" width="max-w-3xl">
        @if($activeTicketLoan)
            <div class="space-y-4">
                <!-- PRINTABLE TICKET CONTAINER (PERSIS SESUAI STANDAR RESMI & GAMBAR REFERENSI) -->
                <div id="printable-ticket" class="bg-white border-2 border-slate-900 p-7 rounded-xl font-sans text-slate-900 shadow-xs">

                    <!-- Header Tiket Resmi -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <!-- Logo Resmi dari Storage -->
                            <div class="w-20 h-20 flex items-center justify-center shrink-0">
                                <img src="{{ asset('storage/assets/logo_cms_hd_png.svg') }}" alt="Logo Instansi" class="w-20 h-20 object-contain shrink-0">
                            </div>
                            <!-- Garis Pemisah Vertikal -->
                            <div class="h-10 w-px bg-slate-300 mx-3.5 self-center"></div>
                            <!-- Judul & Subteks -->
                            <div>
                                <h2 class="text-lg font-black tracking-tight text-slate-900 uppercase leading-none">BUKTI PEMINJAMAN</h2>
                                <h2 class="text-lg font-black tracking-tight text-slate-900 uppercase leading-none mt-1">ARSIP FISIK</h2>
                                <p class="text-[10px] font-semibold tracking-wider text-slate-500 uppercase mt-1">TATA PERSURATAN & KEARSIPAN RESMI (TAPERSIP)</p>
                            </div>
                        </div>

                        <!-- Badge Kode Tiket & Tanggal Jam -->
                        <div class="text-right shrink-0">
                            <div class="inline-block px-4 py-1.5 font-mono font-bold text-sm text-slate-900 border-2 border-slate-900 rounded-lg bg-white">
                                {{ $activeTicketLoan->loan_code }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-1 font-medium">
                                Tgl. {{ $activeTicketLoan->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>

                    <!-- Garis Batas Bawah Header -->
                    <div class="border-b-2 border-slate-800 my-4"></div>

                    <!-- LOKASI LEMARI / MAP ARSIP FISIK (KOTAK PUTUS-PUTUS BIRU ELEGAN) -->
                    <div class="my-4  border-2 border border-black-400 rounded-xl py-3 px-4 text-center">
                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">
                            LOKASI DOKUMEN ARSIP
                        </div>
                        <div class="text-base font-bold text-slate-900 flex items-center justify-center gap-2">
                            <svg class="w-6 h-6 text-yellow-600 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                            </svg>
                            <span>{{ $activeTicketLoan->document->folder ? $activeTicketLoan->document->folder->getDirectoryPath() : ($activeTicketLoan->document->folder->name ?? 'BELUM DITENTUKAN') }}</span>
                        </div>
                    </div>

                    <!-- Detail Peminjam & Dokumen (Grid 2 Kolom Sejajar Titik Dua) -->
                    <div class="grid grid-cols-2 gap-8 text-xs py-2">
                        <!-- Kolom Kiri: IDENTITAS PEMOHON -->
                        <div>
                            <div class="font-bold text-slate-900 uppercase tracking-wide text-xs mb-2">IDENTITAS PEMOHON:</div>
                            <table class="w-full text-xs border-collapse">
                                <tbody>
                                    <tr class="leading-normal">
                                        <td class="w-20 text-slate-700 py-0.5">Nama</td>
                                        <td class="w-4 text-slate-700 py-0.5 text-center">:</td>
                                        <td class="font-bold text-slate-900 py-0.5">{{ $activeTicketLoan->user->name ?? '-' }}</td>
                                    </tr>
                                    <tr class="leading-normal">
                                        <td class="text-slate-700 py-0.5">Jabatan</td>
                                        <td class="text-slate-700 py-0.5 text-center">:</td>
                                        <td class="text-slate-800 py-0.5">{{ $activeTicketLoan->user->position->name ?? 'Staf' }}</td>
                                    </tr>
                                    <tr class="leading-normal">
                                        <td class="text-slate-700 py-0.5">Unit Kerja</td>
                                        <td class="text-slate-700 py-0.5 text-center">:</td>
                                        <td class="text-slate-800 py-0.5">{{ $activeTicketLoan->user->unit->name ?? '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Kolom Kanan: IDENTITAS NASKAH -->
                        <div>
                            <div class="font-bold text-slate-900 uppercase tracking-wide text-xs mb-2">IDENTITAS NASKAH:</div>
                            <table class="w-full text-xs border-collapse">
                                <tbody>
                                    <tr class="leading-normal">
                                        <td class="w-20 text-slate-700 py-0.5">No Surat</td>
                                        <td class="w-4 text-slate-700 py-0.5 text-center">:</td>
                                        <td class="font-bold font-mono text-slate-900 py-0.5">{{ $activeTicketLoan->document->number ?? '-' }}</td>
                                    </tr>
                                    <tr class="leading-normal">
                                        <td class="text-slate-700 py-0.5">Tanggal</td>
                                        <td class="text-slate-700 py-0.5 text-center">:</td>
                                        <td class="text-slate-800 py-0.5">{{ $activeTicketLoan->document->document_date ? $activeTicketLoan->document->document_date->translatedFormat('d M Y') : '-' }}</td>
                                    </tr>
                                    <tr class="leading-normal">
                                        <td class="text-slate-700 py-0.5">Sifat</td>
                                        <td class="text-slate-700 py-0.5 text-center">:</td>
                                        <td class="font-semibold text-slate-800 uppercase py-0.5">{{ $activeTicketLoan->document->sifat ?? 'BIASA' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Garis Pembatas Halus -->
                    <div class="border-b border-slate-200 my-4"></div>

                    <!-- Perihal Dokumen -->
                    <div class="text-xs mb-4">
                        <div class="font-bold text-slate-900 uppercase tracking-wide text-xs mb-1.5">PERIHAL DOKUMEN:</div>
                        <p class="text-slate-800 font-medium leading-relaxed whitespace-normal break-words">
                            {{ $activeTicketLoan->document->subject ?? '-' }}
                        </p>
                    </div>

                    <!-- Keperluan Peminjaman -->
                    <div class="text-xs mb-5">
                        <div class="font-bold text-slate-900 uppercase tracking-wide text-xs mb-1.5">KEPERLUAN PEMINJAMAN:</div>
                        <p class="text-slate-800 font-medium leading-relaxed whitespace-normal break-words">
                            {{ $activeTicketLoan->purpose }}
                        </p>
                    </div>

                    <!-- Rentang Tanggal Pinjam & Batas Kembali -->
                    <div class="grid grid-cols-2 gap-8 text-xs py-1">
                        <div>
                            Mulai Pinjam: <strong class="font-bold text-slate-900 ml-1">{{ $activeTicketLoan->borrow_date ? $activeTicketLoan->borrow_date->translatedFormat('d F Y') : '-' }}</strong>
                        </div>
                        <div>
                            Batas Kembali: <strong class="font-bold text-red-600 ml-1">{{ $activeTicketLoan->expected_return_date ? $activeTicketLoan->expected_return_date->translatedFormat('d F Y') : '-' }}</strong>
                        </div>
                    </div>

                    <!-- Garis Pembatas Sebelum Tanda Tangan -->
                    <div class="border-b border-slate-200 my-5"></div>

                    <!-- Kolom Tanda Tangan Basah Simetris -->
                    <div class="grid grid-cols-2 gap-8 text-center text-xs pt-2">
                        <div>
                            <p class="font-medium text-slate-700 mb-16">Peminjam Berkas,</p>
                            <div class="border-b border-slate-800 w-4/5 mx-auto mb-1.5"></div>
                            <p class="font-bold text-slate-900 text-xs">{{ $activeTicketLoan->user->name ?? '...........................' }}</p>
                            <p class="text-[11px] text-slate-500">{{ $activeTicketLoan->user->unit->name ?? 'Pemohon' }}</p>
                        </div>
                        <div>
                            <p class="font-medium text-slate-700 mb-16">Petugas Layanan Tapersip,</p>
                            <div class="border-b border-slate-800 w-4/5 mx-auto mb-1.5"></div>
                            <p class="font-bold text-slate-900 text-xs">{{ $activeTicketLoan->officer ? $activeTicketLoan->officer->name : '( Petugas Tapersip )' }}</p>
                            <p class="text-[11px] text-slate-500">Unit Kearsipan</p>
                        </div>
                    </div>

                    <!-- Garis Penutup Bawah -->
                    <div class="border-b border-slate-200 my-5"></div>

                    <!-- Catatan Footer Tiket -->
                    <div class="text-[10px] text-slate-500 italic">
                        * Harap simpan tiket ini dan serahkan kembali bersama berkas fisik saat pengembalian ke Tapersip.
                    </div>
                </div>
            </div>
        @endif

        <x-slot:footer>
            <button
                type="button"
                onclick="window.print()"
                class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white font-bold px-5 py-2 rounded-lg text-sm transition-colors inline-flex items-center justify-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak Tiket (Print)
            </button>
            <button
                type="button"
                @click="show = false"
                class="w-full sm:w-auto bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-medium px-4 py-2 rounded-lg text-sm transition-colors">
                Tutup
            </button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- Style Print Khusus Isolasi Tiket A4 Portrait -->
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 15mm;
            }
            body * {
                visibility: hidden !important;
            }
            #printable-ticket, #printable-ticket * {
                visibility: visible !important;
            }
            #printable-ticket {
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                max-width: 180mm !important;
                margin: 0 auto !important;
                padding: 24px 28px !important;
                border: 1.5px solid #0f172a !important;
                border-radius: 8px !important;
                background: #ffffff !important;
                color: #0f172a !important;
                box-shadow: none !important;
                z-index: 999999 !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</div>
