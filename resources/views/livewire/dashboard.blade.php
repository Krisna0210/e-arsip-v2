<div class="space-y-6" @if(!$editDocId && !$voidDocId) wire:poll.visible.10s="checkStatusUpdates" @endif>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Dashboard Kearsipan</h1>
            <p class="text-sm text-[var(--color-text-secondary)] mt-1">Ringkasan statistik repositori dokumen digital perusahaan Anda.</p>
        </div>

        <!-- Quick Actions Bar (Responsive & Permission-Aware) -->
        <div class="flex items-center gap-2 flex-wrap">
            @if(auth()->user()->hasModuleAccess('input_incoming'))
            <a href="{{ route('registry.incoming.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-2xs">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>Surat Masuk</span>
            </a>
            @endif

            @if(auth()->user()->hasModuleAccess('input_outgoing'))
            <a href="{{ route('registry.outgoing.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>No. Surat Keluar</span>
            </a>
            @endif

            @if(auth()->user()->hasModuleAccess('input_internal'))
            <a href="{{ route('registry.special.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-2xs">
                <svg class="w-3.5 h-3.5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>No. Surat Khusus</span>
            </a>
            @endif
        </div>
    </div>

    <!-- Bento Grid Stats (Conditional on Role Permissions) -->
    @if($visibleCardsCount > 0)
    <div class="grid {{ $gridColsClass }} gap-4">
        <!-- Card 1: Total Surat Masuk -->
        @if($canViewIncoming)
        <a href="{{ route('registry.incoming.index') }}" class="bg-white p-5 rounded-xl border border-slate-200/90 hover:border-emerald-300 hover:shadow-xs transition-all group flex justify-between items-start">
            <div>
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Surat Masuk</p>
                <h3 class="text-2xl font-bold text-slate-800 tracking-tight group-hover:text-[var(--color-primary)] transition-colors">
                    {{ $countIncoming }} <span class="text-xs font-normal text-slate-400">Arsip</span>
                </h3>
            </div>
            <div class="p-2.5 bg-emerald-50 rounded-lg text-emerald-600 group-hover:bg-emerald-100 transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>
        </a>
        @endif

        <!-- Card 2: Total Surat Keluar -->
        @if($canViewOutgoing)
        <a href="{{ route('registry.outgoing.index') }}" class="bg-white p-5 rounded-xl border border-slate-200/90 hover:border-blue-300 hover:shadow-xs transition-all group flex justify-between items-start">
            <div>
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Surat Keluar</p>
                <h3 class="text-2xl font-bold text-slate-800 tracking-tight group-hover:text-blue-700 transition-colors">
                    {{ $countOutgoing }} <span class="text-xs font-normal text-slate-400">Arsip</span>
                </h3>
            </div>
            <div class="p-2.5 bg-blue-50 rounded-lg text-blue-600 group-hover:bg-blue-100 transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </div>
        </a>
        @endif

        <!-- Card 3: Total Surat Khusus -->
        @if($canViewInternal)
        <a href="{{ route('registry.special.index') }}" class="bg-white p-5 rounded-xl border border-slate-200/90 hover:border-purple-300 hover:shadow-xs transition-all group flex justify-between items-start">
            <div>
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Total Surat Khusus</p>
                <h3 class="text-2xl font-bold text-slate-800 tracking-tight group-hover:text-purple-700 transition-colors">
                    {{ $countInternal }} <span class="text-xs font-normal text-slate-400">Arsip</span>
                </h3>
            </div>
            <div class="p-2.5 bg-purple-50 rounded-lg text-purple-600 group-hover:bg-purple-100 transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
            </div>
        </a>
        @endif

        <!-- Card 4: Antrean Menunggu Upload / Scan -->
        @if($canViewWaiting)
        <a href="{{ route('registry.scan-queue') }}" class="bg-white p-5 rounded-xl border border-slate-200/90 hover:border-amber-300 hover:shadow-xs transition-all group flex justify-between items-start">
            <div>
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Menunggu Upload</p>
                <h3 class="text-2xl font-bold text-slate-800 tracking-tight group-hover:text-amber-700 transition-colors">
                    {{ $countWaiting }} <span class="text-xs font-normal text-slate-400">Berkas</span>
                </h3>
            </div>
            <div class="p-2.5 bg-amber-50 rounded-lg text-amber-600 group-hover:bg-amber-100 transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </a>
        @endif

        <!-- Card 5: Peringatan Retensi JRA -->
        @if($canViewRetention)
        <a href="{{ route('archive.destructions') }}" class="bg-white p-5 rounded-xl border border-slate-200/90 hover:border-rose-300 hover:shadow-xs transition-all group flex justify-between items-start">
            <div>
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">Peringatan Retensi</p>
                <h3 class="text-2xl font-bold text-slate-800 tracking-tight group-hover:text-rose-700 transition-colors">
                    {{ $countRetention }} <span class="text-xs font-normal text-slate-400">Arsip</span>
                </h3>
            </div>
            <div class="p-2.5 bg-rose-50 rounded-lg text-rose-600 group-hover:bg-rose-100 transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </a>
        @endif

        {{-- Card 6: Peminjaman / Sirkulasi Berkas Fisik --}}
        @if($canViewLoan)
        <a href="{{ $isTapersipOrSuper ? route('borrowings.index', ['tab' => 'circulation']) : route('borrowings.index', ['tab' => 'my_loans']) }}"
           class="bg-white p-5 rounded-xl border border-slate-200/90 hover:border-indigo-300 hover:shadow-xs transition-all group flex justify-between items-start">
            <div>
                <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">
                    {{ $isTapersipOrSuper ? 'Sirkulasi Berkas' : 'Pinjaman Saya' }}
                </p>
                <div class="flex items-baseline gap-2 flex-wrap">
                    <h3 class="text-2xl font-bold text-slate-800 tracking-tight group-hover:text-indigo-700 transition-colors">
                        {{ $countBorrowed }} <span class="text-xs font-normal text-slate-400">Berkas</span>
                    </h3>
                    @if($countOverdue > 0)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 font-mono">
                            {{ $countOverdue }} Terlambat
                        </span>
                    @endif
                </div>
            </div>
            <div class="p-2.5 bg-indigo-50 rounded-lg text-indigo-600 group-hover:bg-indigo-100 transition-colors shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
        </a>
        @endif
    </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Kolom Kiri: Tabel Monitoring Arsip Dokumen (Makan 2 kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card class="overflow-hidden shadow-sm border-slate-200">
                <div class="px-5 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                    <div class="flex items-center">
                        <h2 class="text-lg font-bold text-slate-800 tracking-tight">Monitoring Arsip Dokumen</h2>
                    </div>

                    <!-- Toolbar Pencarian & Filter Status -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <!-- Input Pencarian Cepat -->
                        <div class="relative w-full sm:w-44">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-1 pointer-events-none">
                                <svg class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="monitoringSearch"
                                placeholder="Cari nomor/perihal..."
                                class="w-full bg-white border border-slate-200 text-xs rounded-lg pl-5 pr-1.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] transition-colors placeholder:text-slate-400"
                            >
                        </div>

                        <!-- Segmented Pill Filter Status -->
                        <div class="inline-flex p-0.5 bg-slate-200/70 rounded-lg text-xs font-semibold">
                            <button
                                type="button"
                                wire:click="setMonitoringFilter('all')"
                                class="px-2.5 py-1 rounded-md transition-all {{ $monitoringFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">
                                Semua
                            </button>
                            <button
                                type="button"
                                wire:click="setMonitoringFilter('waiting')"
                                class="px-2.5 py-1 rounded-md transition-all {{ $monitoringFilter === 'waiting' ? 'bg-white text-amber-800 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                Menunggu
                            </button>
                            <button
                                type="button"
                                wire:click="setMonitoringFilter('archived')"
                                class="px-2.5 py-1 rounded-md transition-all {{ $monitoringFilter === 'archived' ? 'bg-white text-emerald-800 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-[11px] font-bold text-slate-500 bg-slate-50 uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th scope="col" class="py-2.5 pl-5 pr-3 w-1/4">Nomor & Jenis Dokumen</th>
                                <th scope="col" class="px-3 py-2.5 w-[35%]">Perihal & Tujuan</th>
                                <th scope="col" class="px-3 py-2.5 text-center">Tanggal Nomor Surat</th>
                                <th scope="col" class="px-3 py-2.5 text-center">Status Monitoring</th>
                                <th scope="col" class="py-2.5 pr-5 text-right w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($monitoringDocuments as $doc)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 pl-5 pr-3 align-top">
                                    <div class="font-mono text-[13px] font-bold text-slate-800 dark:text-slate-200">{{ $doc->number ?? 'Tanpa Nomor' }}</div>
                                    <div class="mt-1">
                                        @if($doc->type === 'incoming')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">Surat Masuk</span>
                                        @elseif($doc->type === 'outgoing')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Surat Keluar</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">{{ $doc->documentType?->name ?? 'Surat Khusus' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3.5 align-top">
                                    <div x-data="{ expanded: false }">
                                        <div
                                            class="font-medium text-slate-800 leading-snug transition-all"
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
                                    @if($doc->type === 'internal')
                                        @php
                                            $originUnit = $doc->unit->name ?? 'Internal';
                                            $targetUnit = $doc->recipients->first()?->recipient?->name ?? ($doc->recipient_name ?? '-');
                                        @endphp
                                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap">
                                            <span class="font-medium text-slate-700" title="{{ $originUnit }}">{{ $originUnit }}</span>
                                            <span class="text-slate-400 font-semibold">&rarr;</span>
                                            <span class="font-medium text-purple-700" title="{{ $targetUnit }}">{{ $targetUnit }}</span>
                                        </div>
                                    @elseif($doc->type === 'outgoing')
                                        @php
                                            $originUnit = $doc->unit->name ?? 'Internal';
                                            $targetName = $doc->recipient_name ?? '-';
                                        @endphp
                                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap">
                                            <span class="font-medium text-slate-700" title="{{ $originUnit }}">{{ $originUnit }}</span>
                                            <span class="text-slate-400 font-semibold">&rarr;</span>
                                            <span class="font-medium text-emerald-700" title="{{ $targetName }}">{{ $targetName }}</span>
                                        </div>
                                    @elseif($doc->type === 'incoming')
                                        @php
                                            $senderName = $doc->sender ?? '-';
                                            $targetUnit = $doc->unit->name ?? '-';
                                        @endphp
                                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-1.5 flex-wrap">
                                            <span class="font-medium text-slate-700" title="{{ $senderName }}">{{ $senderName }}</span>
                                            <span class="text-slate-400 font-semibold">&rarr;</span>
                                            <span class="font-medium text-blue-700" title="{{ $targetUnit }}">{{ $targetUnit }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5 align-top text-center whitespace-nowrap">
                                    <div class="text-xs font-semibold text-slate-700">{{ $doc->created_at->format('d M Y') }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $doc->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-3 py-3.5 align-top text-center">
                                    @if($doc->status === 'waiting_for_file')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs" title="Fisik surat belum di-upload Tapersip">
                                        Menunggu Upload
                                        </span>
                                    @elseif($doc->status === 'cancellation_requested')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs" title="Permohonan pembatalan diajukan ke Tapersip">
                                            Permohonan Batal
                                        </span>
                                    @elseif($doc->status === 'archived')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-600 text-white shadow-2xs" title="Fisik surat sudah di-scan dan diarsipkan">
                                           Selesai Diarsipkan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                            {{ ucfirst($doc->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 pr-5 align-top text-right whitespace-nowrap">
                                    @if($doc->status === 'waiting_for_file' && (auth()->user()->isSuperAdmin() || auth()->user()->isTapersip() || $doc->created_by === auth()->id()))
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                    wire:click="openEditDraftModal({{ $doc->id }})"
                                                    title="Koreksi Data Naskah"
                                                    class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-emerald-700 transition-colors shadow-2xs cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                    wire:click="openVoidDraftModal({{ $doc->id }})"
                                                    title="Batalkan Nomor (Void)"
                                                    class="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-rose-50 hover:text-rose-600 transition-colors shadow-2xs cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </button>
                                        </div>
                                    @elseif($doc->status === 'cancellation_requested')
                                        <span class="text-[11px] text-amber-700 font-medium">Menunggu Tapersip</span>
                                    @else
                                        <span class="text-slate-300 font-mono text-xs pr-2 select-none">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-medium text-slate-700">Belum ada naskah surat yang Anda daftarkan.</p>
                                    <p class="text-xs text-slate-400 mt-1">Registrasikan surat baru melalui menu Input Surat untuk mulai memantau status fisik arsip.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($monitoringDocuments->hasPages())
                    <div class="px-5 py-3 border-t border-slate-200 bg-white">
                        {{ $monitoringDocuments->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </x-ui.card>
        </div>

        <!-- Kolom Kanan: Log Aktivitas (Makan 1 kolom) -->
        <div class="space-y-6">
            <x-ui.card class="shadow-sm border-slate-200 flex flex-col h-full">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
                    <div class="flex items-center">
                        <h2 class="text-lg font-bold text-slate-800 tracking-tight">Log Aktivitas Sistem</h2>
                        @if($canViewAllActivities)
                            <span class="ml-2.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">Seluruh Sistem</span>
                        @else
                            <span class="ml-2.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">Aktivitas Anda</span>
                        @endif
                    </div>
                </div>
                <div class="p-2 sm:p-3 flex-1">
                    <div class="divide-y divide-slate-100">
                        @forelse($recentActivities as $activity)
                            <div class="py-3 px-3 rounded-lg hover:bg-slate-50/70 transition-colors">
                                <!-- Baris 1: Aksi & Waktu Relatif -->
                                <div class="flex items-start justify-between gap-2">
                                    <div class="text-xs font-semibold text-slate-800 leading-snug">
                                        {{ $activity->action }}
                                    </div>
                                    <time class="text-[11px] text-slate-400 font-mono shrink-0 whitespace-nowrap">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </time>
                                </div>

                                <!-- Baris 2: Nomor Surat / Identitas + Pelaku + Subjek -->
                                <div class="text-xs text-slate-500 mt-1 flex items-center flex-wrap gap-1.5 leading-normal">
                                    @if($activity->document && $activity->document->number)
                                        <span class="font-mono text-slate-700 font-medium">
                                            {{ $activity->document->number }}
                                        </span>
                                        <span class="text-slate-300">•</span>
                                    @endif

                                    <span class="text-slate-600 font-medium">
                                        {{ $activity->user_id === auth()->id() ? 'Anda' : ($activity->user->name ?? 'Sistem') }}
                                    </span>

                                    @if($activity->document && $activity->document->subject)
                                        <span class="text-slate-300">•</span>
                                        <span class="text-slate-500 line-clamp-1 truncate max-w-[180px]" title="{{ $activity->document->subject }}">
                                            {{ $activity->document->subject }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10 text-slate-400">
                                <svg class="w-8 h-8 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-xs font-medium text-slate-500">Belum ada aktivitas tercatat.</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Aktivitas registrasi dan pengarsipan akan muncul di sini.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50 text-center">
                    <a href="{{ route('settings.audit-trail') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                        Buka Seluruh Audit Trail Log &rarr;
                    </a>
                </div>
            </x-ui.card>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- MODAL: KOREKSI METADATA DRAFT (modal-dashboard-edit-draft)     -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-dashboard-edit-draft" width="max-w-2xl" title="Koreksi Metadata Draft Naskah">
        <form wire:submit.prevent="saveEditedDraft" class="space-y-4">
            <!-- Header Ringkas Identitas -->
            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-500 font-medium">Nomor Surat:</span>
                    <span class="font-mono font-bold text-[var(--color-primary)] ml-1">{{ $editDocNumber }}</span>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Jenis:</span>
                    <span class="font-semibold text-slate-800 ml-1">{{ $editDocType }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Tanggal Dokumen -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Tanggal Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" wire:model.live="editDocumentDate" max="{{ date('Y-m-d') }}"
                           class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                    @error('editDocumentDate') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Sifat Dokumen -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Sifat Dokumen <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model="editSifat"
                            class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                        <option value="biasa">Biasa (Terbuka)</option>
                        <option value="penting">Penting (Internal Unit/Divisi)</option>
                        <option value="rahasia">Rahasia (Terbatas)</option>
                    </select>
                    @error('editSifat') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Perihal -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Perihal Dokumen <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="editSubject" rows="2"
                          placeholder="Masukkan perihal dokumen..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"></textarea>
                @error('editSubject') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Tujuan Penerima Dokumen -->
            @if($editDocTypeRaw === 'outgoing')
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Tujuan / Penerima Surat Keluar <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="editRecipientName" placeholder="Contoh: Kementerian BUMN, PT Telkom Indonesia..."
                           class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                    @error('editRecipientName') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            @elseif($editDocTypeRaw === 'internal')
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                        Departemen / Unit Penerima Surat Khusus <span class="text-rose-500">*</span>
                    </label>
                    <select wire:model="editRecipientId"
                            class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                        <option value="">-- Pilih Unit Penerima --</option>
                        @foreach($availableUnits as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                        @endforeach
                    </select>
                    @error('editRecipientId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            @endif

            <!-- Klasifikasi Dokumen -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Klasifikasi Arsip <span class="text-rose-500">*</span>
                </label>
                <select wire:model.live="editClassificationId"
                        class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]">
                    <option value="">-- Pilih Kode Klasifikasi --</option>
                    @foreach($classifications as $c)
                        <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->subject }}</option>
                    @endforeach
                </select>
                @error('editClassificationId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror

                @if($newDocNumberPreview && $newDocNumberPreview !== $editDocNumber)
                    <div class="mt-2.5 p-3 bg-amber-50/80 border border-amber-200 rounded-lg space-y-2">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model.live="syncDocNumberWithClassification"
                                   class="mt-0.5 rounded border-amber-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)]">
                            <div class="text-xs">
                                <span class="font-semibold text-amber-900">Perbarui nomor naskah sesuai klasifikasi & tanggal baru</span>
                                <p class="text-[11px] text-amber-700 mt-0.5">Centang jika fisik surat belum dicetak atau belum ditandatangani.</p>
                            </div>
                        </label>
                        @if($syncDocNumberWithClassification)
                            <div class="text-[11px] font-mono bg-white p-2 rounded border border-amber-200 text-slate-700 flex items-center gap-1.5 flex-wrap">
                                <span class="text-slate-400 line-through">{{ $editDocNumber }}</span>
                                <span class="text-amber-600 font-bold">&rarr;</span>
                                <span class="text-emerald-700 font-bold">{{ $newDocNumberPreview }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                @if($yearChangeWarning)
                    <div class="mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-[11px] flex items-start gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ $yearChangeWarning }}</span>
                    </div>
                @endif
            </div>

            <!-- Alasan Koreksi (Wajib untuk audit trail) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Alasan Koreksi Metadata <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="editReason" rows="2"
                          placeholder="Contoh: Ralat pengetikan nama instansi penerima sesuai instruksi staf pembuat..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-amber-500"></textarea>
                @error('editReason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-dashboard-edit-draft')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-[var(--color-primary)] hover:bg-emerald-800 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Koreksi Metadata
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: PEMBATALAN NOMOR SURAT RESMI (modal-dashboard-void-draft)-->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-dashboard-void-draft" width="max-w-md" title="Batalkan Nomor Surat Resmi (Void)">
        <form wire:submit.prevent="confirmVoidDraft" class="space-y-4">
            <!-- Box Informasi Surat -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Nomor Surat:</span>
                    <span class="font-mono font-bold text-rose-600">{{ $voidDocNumber }}</span>
                </div>
            </div>

            <!-- Banner Penjelasan Integritas Kearsipan -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-900 leading-relaxed flex items-start gap-2.5">
                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p>Nomor surat ini akan ditandai <strong>BATAL (Void)</strong> di sistem dan <strong>tidak dihapus</strong> dari database agar urutan buku agenda penomoran kantor tetap utuh tanpa lompatan nomor dalam audit kearsipan.</p>
            </div>

            <!-- Alasan Pembatalan -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Alasan Pembatalan Nomor <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="voidReason" rows="3"
                          placeholder="Contoh: Pembatalan penerbitan surat tugas atas instruksi Direksi, salah memilih jenis format surat..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-rose-500"></textarea>
                @error('voidReason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-dashboard-void-draft')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Konfirmasi Batal Nomor (Void)
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
