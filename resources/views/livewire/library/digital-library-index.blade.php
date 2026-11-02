<div class="space-y-6">
    <!-- Header Halaman (Zero Icons) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Pustaka Digital</h1>
            <p class="text-xs text-slate-500 mt-0.5">Repositori berkas teknik, gambar terlaksana (as-built drawing), kontrak, dan dokumen historis non-surat.</p>
        </div>
        @if($activeTab === 'repository' && $canUploadAndCreateFolder)
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button"
                        wire:click="openCreateFolderModal"
                        class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition-colors shadow-2xs cursor-pointer">
                    + Folder Baru
                </button>
                <button type="button"
                        wire:click="openUploadModal"
                        class="px-4 py-2 text-xs font-semibold text-white bg-[var(--color-primary)] hover:bg-emerald-800 rounded-lg transition-colors shadow-2xs cursor-pointer flex items-center gap-1.5">
                    <span>+ Unggah Berkas Baru</span>
                </button>
            </div>
        @endif
    </div>

    <!-- 3 Tab Navigasi Pustaka Digital (Zero Icons) -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <!-- Tab 1: Repositori Berkas -->
            <button type="button"
                    wire:click="setTab('repository')"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer {{ $activeTab === 'repository' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span>Repositori Berkas</span>
                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $activeTab === 'repository' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $totalDocsCount }}
                </span>
            </button>

            <!-- Tab 2: Permohonan Akses Saya -->
            <button type="button"
                    wire:click="setTab('my_requests')"
                    class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer {{ $activeTab === 'my_requests' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span>Permohonan Akses Saya</span>
                @if($myRequestsCount > 0)
                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $activeTab === 'my_requests' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                        {{ $myRequestsCount }}
                    </span>
                @endif
            </button>

            <!-- Tab 3: Persetujuan Akses Privat (Tapersip / Superadmin) -->
            @if($canManageFlexibility)
                <button type="button"
                        wire:click="setTab('pending_approvals')"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer {{ $activeTab === 'pending_approvals' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                    <span>Persetujuan Akses Privat</span>
                    @if($pendingApprovalsCount > 0)
                        <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-rose-100 text-rose-700 animate-pulse">
                            {{ $pendingApprovalsCount }}
                        </span>
                    @endif
                </button>
            @endif
        </nav>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 1: REPOSITORI BERKAS (KATALOG UTAMA)                       -->
    <!-- ============================================================== -->
    @if($activeTab === 'repository')
        <!-- Active Tag Banner if filtered -->
        @if($selectedTag)
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2.5 flex items-center justify-between text-xs text-emerald-900">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-700 font-medium">Menyaring berdasarkan tagar:</span>
                    <span class="font-bold font-mono bg-white px-2 py-0.5 rounded border border-emerald-300">#{{ $selectedTag }}</span>
                </div>
                <button type="button" wire:click="clearTagFilter" class="text-xs font-semibold text-emerald-700 hover:underline cursor-pointer">
                    Hapus Saringan Tagar &times;
                </button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            <!-- Panel Kiri: Folder Explorer -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Direktori Folder</span>
                    <span class="text-[11px] font-semibold text-slate-400 tabular-nums">{{ $totalDocsCount }} Berkas</span>
                </div>

                <!-- Folder Root / Semua -->
                <button type="button"
                        wire:click="selectFolder(null)"
                        class="w-full text-left px-3 py-2 rounded-lg text-xs font-semibold transition-colors flex items-center justify-between cursor-pointer {{ is_null($activeFolderId) ? 'bg-emerald-50 text-[var(--color-primary)] font-bold border border-emerald-200' : 'text-slate-700 hover:bg-slate-50' }}">
                    <span>Semua Dokumen</span>
                    <span class="text-[10px] tabular-nums font-mono px-1.5 py-0.5 rounded {{ is_null($activeFolderId) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                        {{ $totalDocsCount }}
                    </span>
                </button>

                <!-- Daftar Folder Hierarkis -->
                <div class="space-y-1 max-h-[60vh] overflow-y-auto pr-1">
                    @forelse($folders as $f)
                        @include('livewire.library.folder-item', ['folder' => $f, 'level' => 0, 'expandedFolderIds' => $expandedFolderIds])
                    @empty
                        <div class="py-6 text-center text-xs text-slate-400 italic">
                            Belum ada folder kustom.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Panel Kanan: Tabel Data & Filter Multifaset -->
            <div class="lg:col-span-3 space-y-4">
                <!-- Filter Bar Multifaset -->
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">Cari Berkas</label>
                            <input type="text"
                                   wire:model.live.debounce.300ms="search"
                                   placeholder="Ketik judul, nomor berkas, tagar (#drawing), nama file..."
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-800 placeholder-slate-400 bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">Kategori</label>
                            <select wire:model.live="filterCategory" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-700 bg-slate-50 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                                <option value="all">Semua Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1">Tahun</label>
                            <select wire:model.live="filterYear" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-700 bg-slate-50 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                                <option value="">Semua Tahun</option>
                                @foreach($availableYears as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2 text-xs">
                        <span class="text-slate-500">
                            @if($activeFolder)
                                Folder aktif: <span class="font-bold text-slate-800">{{ $activeFolder->full_path }}</span>
                            @else
                                Menampilkan seluruh arsip digital
                            @endif
                        </span>
                        <button type="button"
                                wire:click="resetFilters"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors cursor-pointer">
                            Reset Saringan
                        </button>
                    </div>
                </div>

                <!-- Tabel Dokumen Digital (Zero Icons) -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[850px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    <th class="px-5 py-3.5 w-1/4">Nomor & Kategori</th>
                                    <th class="px-5 py-3.5 w-1/3">Nama Dokumen</th>
                                    <th class="px-5 py-3.5">Tahun</th>
                                    <th class="px-5 py-3.5">Tagar</th>
                                    <th class="px-5 py-3.5 text-right w-52">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse($documents as $doc)
                                    @php
                                        $catBadges = [
                                            'As-Built Drawing' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'Kontrak/Perjanjian' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                            'Dokumen Hukum/Aset' => 'bg-purple-50 text-purple-700 border border-purple-200',
                                            'Laporan Proyek' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                            'Manual/Pedoman' => 'bg-slate-100 text-slate-700 border border-slate-200',
                                        ];
                                        $badgeClass = $catBadges[$doc->category] ?? 'bg-slate-100 text-slate-700';

                                        $isPrivat = $doc->access_type === 'private';
                                        $hasAccess = $doc->hasActiveAccessForUser(Auth::id());
                                        $pendingReq = $isPrivat && !$hasAccess ? $doc->getPendingAccessForUser(Auth::id()) : null;
                                        $activeAccess = $isPrivat && $hasAccess ? $doc->getActiveAccessForUser(Auth::id()) : null;
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <!-- Kolom 1: Nomor Berkas & Kategori -->
                                        <td class="px-5 py-4 align-top">
                                            <div class="font-mono font-bold text-slate-800 text-[13px] leading-tight">
                                                {{ $doc->document_number }}
                                            </div>
                                            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $badgeClass }}">
                                                    {{ $doc->category }}
                                                </span>
                                                @if($isPrivat)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                        Privat
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-50 text-slate-600 border border-slate-200">
                                                        Publik
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Kolom 2: Nama Dokumen & Rincian Berkas -->
                                        <td class="px-5 py-4 align-top">
                                            <div class="text-slate-800 font-semibold leading-snug break-words">
                                                {{ $doc->title }}
                                            </div>
                                            @if($doc->description)
                                                <div class="text-xs text-slate-500 mt-1 line-clamp-2" title="{{ $doc->description }}">
                                                    {{ $doc->description }}
                                                </div>
                                            @endif
                                            <div class="text-[11px] text-slate-400 mt-1 font-mono">
                                                File: <span class="text-slate-600">{{ $doc->system_file_name }}</span> ({{ $doc->formatted_file_size }})
                                            </div>
                                        </td>

                                        <!-- Kolom 3: Tahun & Unit Pemilik -->
                                        <td class="px-5 py-4 align-top text-xs">
                                            <div class="font-bold text-slate-800 font-mono">
                                                {{ $doc->year }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                Oleh: {{ $doc->creator->name ?? '-' }}
                                            </div>
                                        </td>

                                        <!-- Kolom 4: Tagar / Kata Kunci -->
                                        <td class="px-5 py-4 align-top text-xs">
                                            <div class="flex items-center gap-1 flex-wrap">
                                                @forelse($doc->tags_list as $t)
                                                    <button type="button"
                                                            wire:click="filterByTag('{{ $t }}')"
                                                            class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-slate-100 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors cursor-pointer">
                                                        {{ str_starts_with($t, '#') ? $t : '#' . $t }}
                                                    </button>
                                                @empty
                                                    <span class="text-slate-400 text-xs">-</span>
                                                @endforelse
                                            </div>
                                        </td>

                                        <!-- Kolom 5: Tindakan (Icon / Status Buttons) -->
                                        <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                                @if($isPrivat && !$hasAccess)
                                                    @if($pendingReq)
                                                        <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200 cursor-not-allowed select-none"
                                                              title="Menunggu Persetujuan Tapersip">
                                                            Menunggu Persetujuan
                                                        </span>
                                                    @else
                                                        <button type="button"
                                                                wire:click="openRequestAccessModal({{ $doc->id }})"
                                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white transition-colors cursor-pointer shadow-2xs"
                                                                title="Berkas Privat: Klik untuk mengajukan izin lihat berkas">
                                                            Privat
                                                        </button>
                                                    @endif
                                                @else
                                                    <!-- Pratinjau (Icon Mata) -->
                                                    <div class="flex items-center gap-1">
                                                        <button type="button"
                                                                wire:click="openPreviewModal({{ $doc->id }})"
                                                                class="inline-flex items-center justify-center p-1.5 rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 hover:text-blue-800 transition-colors cursor-pointer"
                                                                title="Pratinjau Berkas">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        {{-- @if($activeAccess)
                                                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-semibold"
                                                                  title="Batas waktu izin aktif">
                                                                {{ $activeAccess->remaining_time_human }}
                                                            </span>
                                                        @endif --}}
                                                    </div>

                                                    <!-- Unduh (Icon Panah ke Bawah) -->
                                                    <a href="{{ route('library.download', $doc->id) }}"
                                                       class="inline-flex items-center justify-center p-1.5 rounded-lg text-emerald-600 bg-emerald-50 hover:bg-emerald-100 hover:text-emerald-800 transition-colors cursor-pointer"
                                                       title="Unduh Berkas">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                    </a>
                                                @endif

                                                <!-- Pindah Folder (Icon Pindah) -->
                                                @if($canManageFlexibility)
                                                    <button type="button"
                                                            wire:click="openMoveDocModal({{ $doc->id }})"
                                                            class="inline-flex items-center justify-center p-1.5 rounded-lg text-amber-600 bg-amber-50 hover:bg-amber-100 hover:text-amber-800 transition-colors cursor-pointer"
                                                            title="Pindahkan Folder Berkas">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                <!-- Hapus (Icon Sampah) -->
                                                @if(Auth::user()->isSuperAdmin() || Auth::user()->isTapersip() || $doc->created_by === Auth::id())
                                                    <button type="button"
                                                            wire:click="confirmDelete({{ $doc->id }})"
                                                            class="inline-flex items-center justify-center p-1.5 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100 hover:text-rose-800 transition-colors cursor-pointer"
                                                            title="Hapus Berkas">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                            Belum ada dokumen yang sesuai dengan kriteria filter di repositori pustaka ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($documents->hasPages())
                        <div class="px-5 py-3 border-t border-slate-100">
                            {{ $documents->links('vendor.pagination.custom') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

    <!-- ============================================================== -->
    <!-- TAB 2: PERMOHONAN AKSES SAYA                                   -->
    <!-- ============================================================== -->
    @elseif($activeTab === 'my_requests')
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[850px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="px-5 py-3.5 w-1/4">Nomor & Judul Berkas</th>
                            <th class="px-5 py-3.5 w-1/3">Alasan Pengajuan</th>
                            <th class="px-5 py-3.5">Waktu Pengajuan</th>
                            <th class="px-5 py-3.5 text-center">Status Akses</th>
                            <th class="px-5 py-3.5 text-center">Masa Berlaku</th>
                            <th class="px-5 py-3.5 text-right w-36">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($myRequests as $req)
                            @php
                                $isActive = $req->isActive();
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Dokumen -->
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono font-bold text-slate-800 text-[13px]">
                                        {{ $req->document->document_number ?? '-' }}
                                    </div>
                                    <div class="text-xs text-slate-600 mt-0.5 font-medium">
                                        {{ $req->document->title ?? '-' }}
                                    </div>
                                </td>

                                <!-- Alasan -->
                                <td class="px-5 py-4 align-top text-xs text-slate-700 leading-relaxed">
                                    <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                                        {{ $req->reason }}
                                    </div>
                                    @if($req->status === 'rejected' && $req->rejection_reason)
                                        <div class="mt-1 text-[11px] text-rose-600">
                                            Catatan Penolakan: {{ $req->rejection_reason }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Waktu -->
                                <td class="px-5 py-4 align-top text-xs text-slate-500 whitespace-nowrap">
                                    <div>{{ $req->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $req->created_at->diffForHumans() }}</div>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 align-top text-center whitespace-nowrap">
                                    @if($req->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            Menunggu Persetujuan
                                        </span>
                                    @elseif($req->status === 'approved')
                                        @if($isActive)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Disetujui (Aktif)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                Izin Kedaluwarsa
                                            </span>
                                        @endif
                                    @elseif($req->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Ditolak
                                        </span>
                                    @endif
                                </td>

                                <!-- Masa Berlaku -->
                                <td class="px-5 py-4 align-top text-center text-xs whitespace-nowrap">
                                    @if($req->status === 'approved')
                                        @if($isActive)
                                            <div class="font-bold text-emerald-700 font-mono">{{ $req->remaining_time_human }}</div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">s.d {{ $req->expires_at->format('d/m H:i') }}</div>
                                        @else
                                            <div class="text-slate-400 italic">Berakhir {{ $req->expires_at ? $req->expires_at->format('d/m/Y') : '-' }}</div>
                                        @endif
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <!-- Tindakan -->
                                <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                    @if($isActive && $req->document)
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button"
                                                    wire:click="openPreviewModal({{ $req->document->id }})"
                                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors cursor-pointer">
                                                Pratinjau
                                            </button>
                                            {{-- <a href="{{ route('library.download', $req->document->id) }}"
                                               class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors cursor-pointer">
                                                Unduh
                                            </a> --}}
                                        </div>
                                    @else
                                        <span class="text-slate-400 text-xs italic">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-sm">
                                    Anda belum pernah mengajukan izin akses untuk berkas privat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($myRequests && $myRequests->hasPages())
                <div class="px-5 py-3 border-t border-slate-100">
                    {{ $myRequests->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>

    <!-- ============================================================== -->
    <!-- TAB 3: PERSETUJUAN AKSES PRIVAT (TAPERSIP / SUPERADMIN)        -->
    <!-- ============================================================== -->
    @elseif($activeTab === 'pending_approvals' && $canManageFlexibility)
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[850px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="px-5 py-3.5 w-1/4">Nomor & Judul Berkas</th>
                            <th class="px-5 py-3.5">Pemohon</th>
                            <th class="px-5 py-3.5 w-1/3">Alasan Pengajuan Akses</th>
                            <th class="px-5 py-3.5 text-center">Waktu Diajukan</th>
                            <th class="px-5 py-3.5 text-right w-44">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($pendingApprovals as $appr)
                            <tr id="req-row-{{ $appr->id }}"
                                x-data="{
                                    isTarget: new URLSearchParams(window.location.search).get('highlight') === 'req-{{ $appr->id }}',
                                    init() {
                                        if (this.isTarget) {
                                            this.$el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                            setTimeout(() => { this.isTarget = false; }, 5000);
                                        }
                                    }
                                }"
                                :class="{ 'hover:bg-slate-50/70': !isTarget, '[&>td]:!bg-[#f1f5f9]': isTarget }"
                                :style="isTarget ? 'background-color: #f1f5f9; outline: 1.5px solid #cbd5e1; outline-offset: -1px; transition: all 0.7s ease;' : 'transition: all 0.7s ease;'">
                                <!-- Dokumen -->
                                <td class="px-5 py-4 align-top">
                                    <div class="font-mono font-bold text-slate-800 text-[13px]">
                                        {{ $appr->document->document_number ?? '-' }}
                                    </div>
                                    <div class="text-xs text-slate-600 mt-0.5 font-medium leading-snug">
                                        {{ $appr->document->title ?? '-' }}
                                    </div>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Berkas Privat
                                        </span>
                                    </div>
                                </td>

                                <!-- Pemohon -->
                                <td class="px-5 py-4 align-top text-xs">
                                    <div class="font-bold text-slate-800">
                                        {{ $appr->user->name ?? '-' }}
                                    </div>
                                    <div class="text-slate-500 mt-0.5 font-medium">
                                        {{ $appr->user->unit->name ?? 'Internal' }}
                                    </div>
                                </td>

                                <!-- Alasan -->
                                <td class="px-5 py-4 align-top text-xs text-slate-700 leading-relaxed">
                                    <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                                        {{ $appr->reason }}
                                    </div>
                                </td>

                                <!-- Waktu -->
                                <td class="px-5 py-4 align-top text-center text-xs text-slate-500 whitespace-nowrap">
                                    <div>{{ $appr->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $appr->created_at->diffForHumans() }}</div>
                                </td>

                                <!-- Tindakan -->
                                <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                wire:click="openApproveAccessModal({{ $appr->id }})"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors shadow-2xs cursor-pointer">
                                            Setujui Izin
                                        </button>
                                        <button type="button"
                                                wire:click="openRejectAccessModal({{ $appr->id }})"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 bg-white border border-rose-200 hover:bg-rose-50 transition-colors cursor-pointer">
                                            Tolak
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                    Tidak ada permohonan izin akses berkas privat yang sedang menunggu persetujuan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pendingApprovals && $pendingApprovals->hasPages())
                <div class="px-5 py-3 border-t border-slate-100">
                    {{ $pendingApprovals->links('vendor.pagination.custom') }}
                </div>
            @endif
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- MODAL: UNGGAH BERKAS PUSTAKA DIGITAL (Zero Icons)              -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-upload-digital-doc" width="max-w-2xl" title="Unggah Berkas Pustaka Digital">
        <form wire:submit.prevent="saveDocument" class="space-y-4">
            <!-- Informasi Format Penomoran Otomatis -->
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-900 leading-relaxed">
                Nomor Berkas otomatis diterbitkan sistem dengan formula: <strong>{No}-ARSIP-DIG-{Tahun}</strong> (format urut otomatis per tahun).
            </div>

            <!-- Tipe Akses Dokumen: Publik vs Privat -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Tipe Akses Dokumen <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer text-xs transition-colors {{ $uploadAccessType === 'public' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-950 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        <input type="radio" wire:model.live="uploadAccessType" value="public" class="mt-0.5 text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                        <div>
                            <div class="font-bold">Publik (Terbuka)</div>
                            <div class="text-[11px] font-normal text-slate-500 mt-0.5">Dapat dilihat dan diunduh langsung oleh seluruh staf.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 p-3 rounded-xl border cursor-pointer text-xs transition-colors {{ $uploadAccessType === 'private' ? 'border-amber-500 bg-amber-50/50 text-amber-950 font-semibold' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        <input type="radio" wire:model.live="uploadAccessType" value="private" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                        <div>
                            <div class="font-bold text-amber-900">Privat (Perlu Izin Akses)</div>
                            <div class="text-[11px] font-normal text-slate-500 mt-0.5">Terkunci secara default. Staf wajib mengajukan izin berbatas waktu ke Tapersip.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Nama / Judul Dokumen -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Nama / Judul Dokumen <span class="text-rose-500">*</span>
                </label>
                <input type="text"
                       wire:model="uploadTitle"
                       placeholder="Contoh: As-Built Drawing Gedung Pusat Lantai 1 s.d 3..."
                       class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                @error('uploadTitle') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Kategori Berkas -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Kategori Berkas <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model="uploadCategory" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('uploadCategory') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Tahun Dokumen -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Tahun Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <input type="number"
                           wire:model="uploadYear"
                           min="1950"
                           max="2099"
                           class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] font-mono">
                    @error('uploadYear') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Unit / Divisi Pemilik -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Unit / Divisi Pemilik Berkas
                    </label>
                    <select wire:model="uploadUnitId" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                        <option value="">-- Lintas Unit / Umum --</option>
                        @foreach($availableUnits as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @error('uploadUnitId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Folder Penempatan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Folder Penempatan
                    </label>
                    <select wire:model="uploadFolderId" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                        <option value="">-- Tanpa Folder / Root --</option>
                        @foreach($allFolders as $fl)
                            <option value="{{ $fl->id }}">{!! $fl->tree_name !!}</option>
                        @endforeach
                    </select>
                    @error('uploadFolderId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Tagar / Kata Kunci -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Tagar / Kata Kunci Pencarian
                </label>
                <input type="text"
                       wire:model="uploadTags"
                       placeholder="Contoh: #drawing #arsitektur #gedung-a #mep"
                       class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                <span class="text-[11px] text-slate-400 mt-0.5 block">Gunakan awalan pagar (#) atau pisahkan dengan spasi untuk memudahkan pencarian.</span>
                @error('uploadTags') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Deskripsi Opsional -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Deskripsi / Catatan Tambahan (Opsional)
                </label>
                <textarea wire:model="uploadDescription" rows="2"
                          placeholder="Tambahkan keterangan teknis, nomor kontrak asli, atau histori berkas..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"></textarea>
                @error('uploadDescription') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Upload File Berkas Fisik -->
            <div class="p-3 bg-slate-50 border border-dashed border-slate-300 rounded-xl">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Pilih File Dokumen Digital <span class="text-rose-500">*</span>
                </label>
                <input type="file"
                       wire:model="uploadFile"
                       class="block w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                <span class="text-[11px] text-slate-400 mt-1 block">Mendukung file PDF, Gambar Teknik (JPG/PNG/WEBP), ZIP, RAR, atau berkas CAD. Maksimal 50 MB.</span>
                @error('uploadFile') <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror

                <div wire:loading wire:target="uploadFile" class="text-xs text-blue-600 mt-1.5 font-medium">
                    Sedang memproses unggahan berkas...
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-upload-digital-doc')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-[var(--color-primary)] hover:bg-emerald-800 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Simpan & Unggah Berkas
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: AJUKAN IZIN AKSES BERKAS PRIVAT (Staf Pemohon)          -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-request-private-access" width="max-w-md" title="Pengajuan Izin Berkas Privat">
        <form wire:submit.prevent="submitRequestAccess" class="space-y-4">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 space-y-1.5 text-xs text-amber-950">
                <div class="flex items-center justify-between">
                    <span class="text-amber-700 font-medium">Nomor Berkas:</span>
                    <span class="font-mono font-bold">{{ $requestAccessDocNumber }}</span>
                </div>
                <div class="font-semibold leading-snug">{{ $requestAccessDocTitle }}</div>
                <div class="text-[11px] text-amber-800 mt-1">
                    Berkas ini bersifat <strong>Privat</strong>. Pengajuan Anda akan ditinjau oleh petugas Tapersip untuk pemberian batas waktu akses.
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Alasan Pengajuan Izin Akses <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="requestAccessReason" rows="3"
                          placeholder="Jelaskan kebutuhan pekerjaan Anda untuk membuka berkas privat ini..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"></textarea>
                @error('requestAccessReason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-request-private-access')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Kirim Permohonan Izin
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: SETUJUI IZIN AKSES PRIVAT (Tapersip)                    -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-approve-private-access" width="max-w-md" title="Setujui Izin Akses Berkas Privat">
        <form wire:submit.prevent="executeApproveAccess" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Pemohon:</span>
                    <span class="font-bold text-slate-800">{{ $approvalUserName }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Berkas:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $approvalDocNumber }}</span>
                </div>
                <div class="text-slate-600 leading-snug pt-1 border-t border-slate-200">{{ $approvalDocTitle }}</div>
                <div class="p-2 bg-white rounded border border-slate-200 text-slate-700 mt-1">
                    <span class="font-semibold text-slate-500 block mb-0.5">Alasan Pemohon:</span>
                    {{ $approvalUserReason }}
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Pilih Batas Waktu Akses (Durasi) <span class="text-rose-500">*</span>
                </label>
                <select wire:model="approvalDurationHours" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                    <option value="2">2 Jam</option>
                    <option value="6">6 Jam</option>
                    <option value="24">1 Hari (24 Jam)</option>
                    <option value="72">3 Hari (72 Jam)</option>
                    <option value="168">7 Hari (1 Minggu)</option>
                </select>
                <span class="text-[11px] text-slate-400 mt-1 block">Setelah waktu ini habis, berkas otomatis terkunci kembali untuk pemohon bersangkutan.</span>
                @error('approvalDurationHours') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-approve-private-access')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Setujui & Buka Akses
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: TOLAK IZIN AKSES PRIVAT (Tapersip)                      -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-reject-private-access" width="max-w-md" title="Tolak Permohonan Akses Privat">
        <form wire:submit.prevent="executeRejectAccess" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1 text-xs">
                <div><span class="text-slate-500">Pemohon:</span> <strong class="text-slate-800">{{ $approvalUserName }}</strong></div>
                <div><span class="text-slate-500">Berkas:</span> <span class="font-mono font-bold">{{ $approvalDocNumber }}</span></div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Alasan Penolakan <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="rejectionReasonNotes" rows="3"
                          placeholder="Jelaskan alasan mengapa permohonan akses berkas ini ditolak..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
                @error('rejectionReasonNotes') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-reject-private-access')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Kirim Penolakan
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: BUAT FOLDER BARU (Zero Icons)                           -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-folder-editor" width="max-w-md" title="Buat Folder Pustaka Baru">
        <form wire:submit.prevent="saveFolder" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Nama Folder <span class="text-rose-500">*</span>
                </label>
                <input type="text"
                       wire:model="folderName"
                       placeholder="Contoh: Proyek Gedung Kantor 2020..."
                       class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                @error('folderName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Folder Induk (Parent)
                </label>
                <select wire:model="folderParentId" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                    <option value="">-- Folder Tingkat Utama (Root) --</option>
                    @foreach($allFolders as $fl)
                        <option value="{{ $fl->id }}">{!! $fl->tree_name !!}</option>
                    @endforeach
                </select>
                @error('folderParentId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Keterangan (Opsional)
                </label>
                <textarea wire:model="folderDescription" rows="2"
                          placeholder="Keterangan isi kelompok berkas..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"></textarea>
                @error('folderDescription') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-folder-editor')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-[var(--color-primary)] hover:bg-emerald-800 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Simpan Folder
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: PRATINJAU DOKUMEN DIGITAL (PDF & Blueprint Viewer)      -->
    <!-- Dilindungi 4 Lapis Pengamanan: Anti-Print, Anti-Context, Anti-Capture, Forensic Watermark -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-preview-digital-doc" width="max-w-7xl" padding="false" title="Pratinjau Berkas Pustaka Digital">
        @if($previewDoc)
            <div x-data="{
                    isBlurred: false,
                    init() {
                        const blockKey = (e) => {
                            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P' || e.key === 's' || e.key === 'S')) {
                                e.preventDefault();
                                e.stopPropagation();
                                return false;
                            }
                        };
                        window.addEventListener('keydown', blockKey);
                        window.addEventListener('blur', () => { this.isBlurred = true; });
                        window.addEventListener('focus', () => { this.isBlurred = false; });
                    }
                 }"
                 @contextmenu.prevent
                 class="flex flex-col h-[85vh] bg-slate-900 select-none relative overflow-hidden">

                <style>
                    @media print {
                        body, iframe, img, [role="dialog"] {
                            display: none !important;
                            visibility: hidden !important;
                        }
                        body::before {
                            content: "DOKUMEN DILINDUNGI: Berkas Pustaka Digital bersifat rahasia dan dilarang dicetak sesuai kebijakan keamanan informasi perusahaan.";
                            display: block !important;
                            font-family: sans-serif;
                            font-size: 16pt;
                            font-weight: bold;
                            text-align: center;
                            color: #b91c1c;
                            padding-top: 150px;
                        }
                    }
                </style>

                <!-- Toolbar Header Pratinjau -->
                <div class="bg-slate-800 px-5 py-3 border-b border-slate-700 flex items-center justify-between text-white flex-wrap gap-3 z-30 relative">
                    <div class="flex items-center gap-3">
                        <span class="font-mono font-bold text-xs bg-slate-900 px-2.5 py-1 rounded border border-slate-700 text-emerald-400">
                            {{ $previewDoc->document_number }}
                        </span>
                        <div>
                            <h4 class="text-xs font-bold text-slate-100 leading-tight">{{ $previewDoc->title }}</h4>
                            <span class="text-[11px] text-slate-400">{{ $previewDoc->category }} &bull; Tahun {{ $previewDoc->year }} &bull; {{ $previewDoc->formatted_file_size }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($previewDoc->access_type === 'private')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-950 text-amber-300 border border-amber-800">
                                Dokumen Dilindungi &bull; Berkas Privat
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-semibold bg-slate-900 text-slate-300 border border-slate-700">
                                Dokumen Terbuka &bull; Publik
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Konten Pratinjau (Iframe PDF / Image Viewer) dengan Lapisan Pengamanan -->
                <div class="flex-1 overflow-hidden relative bg-slate-950 flex items-center justify-center">

                    <!-- Lapis 3: Anti-Capture Screen Shield (Aktif saat jendela kehilangan fokus / membuka snipping tool) -->
                    <div x-show="isBlurred"
                         x-transition.opacity
                         class="absolute inset-0 z-40 bg-slate-950/95 backdrop-blur-2xl flex flex-col items-center justify-center text-center p-6"
                         style="display: none;">
                        <div class="p-3 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 mb-3">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-100">Tampilan Pratinjau Diamankan</h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm">Konten disamarkan secara otomatis saat jendela browser kehilangan fokus.</p>
                    </div>

                    <!-- Lapis 4: Forensic Dynamic Watermark Overlay (Khusus Berkas Privat) -->
                    @if($previewDoc->access_type === 'private')
                        <div class="absolute inset-0 z-30 pointer-events-none overflow-hidden select-none" style="opacity: 0.26;">
                            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <pattern id="forensic-watermark" width="340" height="200" patternUnits="userSpaceOnUse" patternTransform="rotate(-35)">
                                        <text x="15" y="45" font-family="monospace" font-size="12" font-weight="bold" fill="#0f172a" stroke="#ffffff" stroke-width="0.25" letter-spacing="1">
                                            DOKUMEN ARSIP PERUSAHAAN &bull; RAHASIA
                                        </text>
                                        <text x="15" y="65" font-family="monospace" font-size="10" font-weight="semibold" fill="#1e293b" stroke="#ffffff" stroke-width="0.2">
                                            {{ Auth::user()->name }} &bull;{{ Auth::user()->unit?->name ?? 'Internal' }} &bull; {{ request()->ip() }}
                                        </text>
                                        <text x="15" y="85" font-family="monospace" font-size="9" fill="#334155" stroke="#ffffff" stroke-width="0.15">
                                            {{ now()->format('d/m/Y H:i') }} WIB
                                        </text>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" fill="url(#forensic-watermark)" />
                            </svg>
                        </div>
                    @endif

                    <!-- Konten Berkas Fisik (PDF / Gambar) -->
                    @if(str_contains(strtolower($previewDoc->mime_type ?? ''), 'image') || in_array(strtolower(pathinfo($previewDoc->file_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp']))
                        <div class="max-w-full max-h-full overflow-auto p-4 flex items-center justify-center">
                            <img src="{{ route('library.preview', $previewDoc->id) }}"
                                 alt="{{ $previewDoc->title }}"
                                 class="max-w-full max-h-[75vh] object-contain rounded shadow-lg border border-slate-800 pointer-events-none">
                        </div>
                    @else
                        <iframe src="{{ route('library.preview', $previewDoc->id) }}#toolbar=0&navpanes=0"
                                class="w-full h-full border-0"></iframe>
                    @endif
                </div>
            </div>
        @endif
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: KONFIRMASI HAPUS BERKAS (Zero Icons)                    -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-delete-digital-doc" width="max-w-md" title="Hapus Berkas Pustaka">
        <div class="space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed">
                Apakah Anda yakin ingin menghapus berkas <strong class="text-slate-800">{{ $deleteDocTitle }}</strong> dari repositori pustaka digital?
            </p>
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-delete-digital-doc')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="button"
                        wire:click="executeDelete"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors shadow-2xs cursor-pointer">
                    Ya, Hapus Berkas
                </button>
            </div>
        </div>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: PINDAH FOLDER BERKAS (Zero Icons)                       -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-move-digital-doc" width="max-w-md" title="Pindahkan Folder Berkas">
        <form wire:submit.prevent="executeMoveDoc" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Nomor Berkas:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $moveDocNumber }}</span>
                </div>
                <div class="text-slate-700 font-semibold truncate">{{ $moveDocTitle }}</div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Pilih Folder Tujuan
                </label>
                <select wire:model="moveTargetFolderId" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                    <option value="">-- Tanpa Folder / Root --</option>
                    @foreach($allFolders as $fl)
                        <option value="{{ $fl->id }}">{!! $fl->tree_name !!}</option>
                    @endforeach
                </select>
                @error('moveTargetFolderId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-move-digital-doc')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Simpan Pemindahan
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: PINDAHKAN HIERARKI FOLDER (Zero Icons)                  -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-move-digital-folder" width="max-w-md" title="Pindahkan Hierarki Folder">
        <form wire:submit.prevent="executeMoveFolder" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Folder yang Dipindahkan:</span>
                    <span class="font-bold text-slate-800">{{ $moveFolderName }}</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Pilih Folder Induk Tujuan
                </label>
                <select wire:model="moveTargetParentId" class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                    <option value="">-- Jadikan Folder Tingkat Utama (Root) --</option>
                    @foreach($allFolders as $fl)
                        @if($fl->id !== $moveFolderId)
                            <option value="{{ $fl->id }}">{!! $fl->tree_name !!}</option>
                        @endif
                    @endforeach
                </select>
                @error('moveTargetParentId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-move-digital-folder')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Simpan Posisi Folder
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: HAPUS FOLDER PUSTAKA (Zero Data Loss Protection)        -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-delete-digital-folder" width="max-w-md" title="Hapus Folder Pustaka">
        <div class="space-y-4">
            <div class="bg-rose-50 border border-rose-200 rounded-xl p-3.5 space-y-1.5 text-xs text-rose-900">
                <div class="font-bold">Apakah Anda yakin ingin menghapus folder "{{ $deleteFolderName }}"?</div>
                <div class="text-[11px] text-rose-700 leading-relaxed mt-1">
                    Terdapat <strong class="text-rose-900">{{ $deleteFolderDocsCount }} berkas</strong> di dalam folder ini.
                </div>
            </div>

            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 text-xs leading-relaxed">
                <strong>Jaminan Keamanan Berkas:</strong> Seluruh berkas digital di dalam folder ini <strong>TIDAK akan terhapus</strong>, melainkan otomatis dialihkan secara aman ke folder induk atau ke Root Dokumen.
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-delete-digital-folder')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="button"
                        wire:click="executeDeleteFolder"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors shadow-2xs cursor-pointer">
                    Ya, Hapus Folder & Amankan Berkas
                </button>
            </div>
        </div>
    </x-ui.modal>
</div>
