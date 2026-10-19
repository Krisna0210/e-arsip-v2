<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ uploading: false }" x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false">
    <div class="sm:flex sm:items-center sm:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-jakarta">Antrean Scan Tapersip</h1>
            <p class="mt-2 text-sm text-gray-600">Daftar dokumen fisik yang telah memiliki nomor dan menunggu diunggah (scan PDF).</p>
        </div>

        <!-- NAS Storage Connectivity Pill Badge -->
        <div class="mt-4 sm:mt-0 flex items-center">
            @if($nasStatus['online'])
                <div class="inline-flex items-center gap-4   px-3.5 py-1.5 rounded-xl border border-emerald-200 bg-emerald-50/70 text-emerald-800 text-xs shadow-2xs">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <div class="flex items-center gap-1.5 font-medium">
                        <span class="font-bold text-emerald-900">NAS Online</span>
                        @if($nasStatus['free_space'])
                            <span class="text-emerald-600 font-normal">| Sisa: {{ $nasStatus['free_space'] }}</span>
                        @endif
                    </div>
                    <button type="button" wire:click="refreshNasConnection" wire:loading.attr="disabled"
                            title="Periksa ulang koneksi NAS (Terakhir: {{ $nasStatus['checked_at'] }})"
                            class="ml-1 text-emerald-700 hover:text-emerald-900 p-0.5 rounded hover:bg-emerald-100 transition-colors cursor-pointer">
                        <svg wire:loading.remove wire:target="refreshNasConnection" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <svg wire:loading wire:target="refreshNasConnection" class="w-3.5 h-3.5 animate-spin text-emerald-700" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            @else
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl border border-rose-300 bg-rose-50 text-rose-800 text-xs shadow-2xs">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                    </span>
                    <div class="flex items-center gap-1 font-medium">
                        <span class="font-bold text-rose-900">NAS Terputus</span>
                        <span class="text-rose-600 hidden md:inline">| Hubungi Administrator</span>
                    </div>
                    <button type="button" wire:click="refreshNasConnection" wire:loading.attr="disabled"
                            title="Coba sambungkan ulang NAS"
                            class="ml-1 text-xs font-bold text-rose-700 hover:text-rose-900 underline flex items-center gap-1 cursor-pointer">
                        <svg wire:loading wire:target="refreshNasConnection" class="w-3 h-3 animate-spin text-rose-700 inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="refreshNasConnection">Cek Lagi</span>
                        <span wire:loading wire:target="refreshNasConnection">Memeriksa...</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <!-- Segmented Pill Filter with Queue Counters -->
        <div class="inline-flex p-1 bg-slate-200/70 rounded-xl text-xs font-semibold w-full sm:w-auto">
            <button type="button" wire:click="setFilterType('all')"
                    class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 {{ $filterType === 'all' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                <span>Semua</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $filterType === 'all' ? 'bg-slate-100 text-slate-800' : 'bg-slate-300/60 text-slate-700' }}">{{ $countAll }}</span>
            </button>
            <button type="button" wire:click="setFilterType('outgoing')"
                    class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 {{ $filterType === 'outgoing' ? 'bg-white text-[var(--color-primary)] shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                <span>Surat Keluar</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $filterType === 'outgoing' ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-300/60 text-slate-700' }}">{{ $countOutgoing }}</span>
            </button>
            <button type="button" wire:click="setFilterType('internal')"
                    class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 {{ $filterType === 'internal' ? 'bg-white text-purple-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                <span>Surat Khusus</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $filterType === 'internal' ? 'bg-purple-50 text-purple-800' : 'bg-slate-300/60 text-slate-700' }}">{{ $countInternal }}</span>
            </button>
        </div>

        <div class="w-full sm:w-72 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nomor, perihal, atau staf..." class="block w-full rounded-lg border border-slate-200 pl-9 pr-3 py-2 text-xs text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] bg-slate-50 focus:bg-white transition-colors">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider sm:pl-6 w-16">Antrean</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identitas Dokumen</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Perihal</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sifat</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                <div class="flex flex-col">
                                    <span class="text-gray-900 text-base">#{{ $loop->iteration + $documents->firstItem() - 1 }}</span>
                                    <span class="text-xs text-gray-500 mt-1" title="{{ $doc->created_at }}">{{ $doc->created_at->diffForHumans() }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-500">
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    <span class="font-mono text-[var(--color-primary)] font-semibold">{{ $doc->number }}</span>
                                </div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                            {{ $doc->type === 'incoming' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($doc->type === 'outgoing' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                            {{ $doc->type === 'incoming' ? 'Surat Masuk' : ($doc->type === 'outgoing' ? 'Surat Keluar' : ($doc->documentType?->name ?? 'Surat Khusus')) }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                      Pendaftar: {{ $doc->createdBy->name ?? '-' }}
                                </div>
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-900 max-w-md">
                                <div x-data="{ expanded: false }">
                                    <div class="leading-snug text-slate-800 font-medium transition-all"
                                         :class="expanded ? 'whitespace-normal break-words' : 'line-clamp-2'"
                                         title="{{ $doc->subject }}">
                                        {{ $doc->subject }}
                                    </div>
                                    @if(mb_strlen($doc->subject ?? '') > 75)
                                        <button type="button" @click="expanded = !expanded" class="text-[11px] font-bold text-[var(--color-primary)] hover:underline mt-1 inline-flex items-center gap-0.5 focus:outline-none cursor-pointer">
                                            <span x-text="expanded ? 'Show Less ▴' : 'Show More ▾'"></span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-4 text-sm">
                                <x-ui.badge :color="$doc->sifat === 'rahasia' ? 'red' : ($doc->sifat === 'penting' ? 'amber' : 'green')">
                                    {{ ucfirst($doc->sifat) }}
                                </x-ui.badge>
                            </td>
                            <td class="relative py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                @if(Auth::user()->hasDocumentPermission('archive', $doc->sifat))
                                    <button wire:click="openUploadModal({{ $doc->id }})" class="inline-flex items-center gap-1.5 text-white bg-[var(--color-primary)] hover:bg-emerald-800 px-3 py-1.5 rounded-md shadow-sm text-xs font-semibold transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Upload Scan
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic">No Access</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900">Antrean Kosong</h3>
                                <p class="mt-1 text-sm text-gray-500">Tidak ada dokumen yang menunggu untuk diunggah.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documents->hasPages())
            <div class="px-5 py-3 border-t border-gray-200 bg-white sm:px-6">
                {{ $documents->links('vendor.pagination.custom') }}
            </div>
        @endif
    </div>

        <!-- Smart Verification Modal (Split-Pane) -->
    <x-ui.modal name="modal-upload-queue" width="max-w-7xl" padding="false" title="Verifikasi & Unggah Dokumen Final">
        <form wire:submit.prevent="saveFinalFile"
              x-data="{ pdfUrl: null, uploading: false }"
              x-on:livewire-upload-start="uploading = true"
              x-on:livewire-upload-finish="uploading = false"
              x-on:livewire-upload-error="uploading = false"
              x-on:open-modal.window="if ($event.detail === 'modal-upload-queue' || $event.detail.id === 'modal-upload-queue' || (Array.isArray($event.detail) && $event.detail[0] === 'modal-upload-queue')) pdfUrl = null">

            <div class="flex flex-col lg:flex-row h-[80vh] divide-y lg:divide-y-0 lg:divide-x divide-gray-200">
                <!-- PANEL KIRI: Metadata & Form Upload (Lebar ~40%) -->
                <div class="w-full lg:w-[450px] flex-shrink-0 flex flex-col h-full bg-white">
                    <div class="p-6 flex-1 overflow-y-auto">
                        @if(!$nasStatus['online'])
                            <div class="mb-6 bg-rose-50 p-4 rounded-xl border border-rose-200 flex items-start gap-3">
                                <svg class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h5 class="text-xs font-bold text-rose-900 uppercase tracking-wider">Koneksi NAS Terputus</h5>
                                    <p class="text-xs text-rose-700 mt-0.5 leading-relaxed">Penyimpanan server NAS sedang tidak dapat dijangkau oleh sistem. Pengunggahan berkas dinonaktifkan sementara sampai koneksi pulih.</p>
                                </div>
                            </div>
                        @endif

                        <!-- Alert Info -->
                        <div class="mb-6 bg-amber-50 p-4 rounded-xl border border-amber-100 flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm text-amber-800 leading-relaxed">Pastikan Anda telah melakukan <strong>scan fisik</strong> dokumen yang telah ditandatangani dan dicap resmi. Dokumen ini akan diarsipkan secara permanen.</p>
                        </div>

                        <!-- Identitas Dokumen (Read Only) -->
                        <div class="mb-6">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Identitas Dokumen</h4>
                            <div class="space-y-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Nomor Surat</label>
                                    <div class="font-mono text-sm font-semibold text-gray-900">{{ $uploadDocNumber }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Perihal</label>
                                    <div class="text-sm text-gray-900 font-medium">{{ $uploadDocSubject }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Jenis</label>
                                        <div class="text-sm text-gray-900">{{ $uploadDocType }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal Dokumen</label>
                                        <div class="text-sm text-gray-900">{{ $uploadDocDate }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Staf Pendaftar</label>
                                    <div class="text-sm text-gray-900">{{ $uploadDocCreator }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Loker Penyimpanan (Tapersip Archive Destination) -->
                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Loker Penyimpanan</label>
                            <select wire:model="uploadFolderId" class="w-full px-3.5 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                                <option value="">-- Tanpa Loker / Root --</option>
                                @foreach($availableFolders as $f)
                                    <option value="{{ $f->id }}">
                                        {{ $f->parent ? $f->parent->name . ' / ' : '' }}{{ $f->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('uploadFolderId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Upload Area -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">File Hasil Scan</h4>
                            <div class="relative border-2 border-dashed rounded-xl p-6 text-center transition-all duration-200"
                                 x-bind:class="{ 'opacity-50 cursor-not-allowed': uploading, 'border-[var(--color-primary)] bg-emerald-50': pdfUrl, 'border-gray-300 hover:bg-gray-50 hover:border-gray-400': !pdfUrl }">

                                <div x-show="uploading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/80 rounded-xl">
                                    <svg class="animate-spin h-8 w-8 text-[var(--color-primary)] mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700">Membaca file...</span>
                                </div>

                                <svg x-show="!pdfUrl" class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>

                                <svg x-show="pdfUrl" class="mx-auto h-12 w-12 text-[var(--color-primary)] mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>

                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label class="relative cursor-pointer rounded-md font-medium text-[var(--color-primary)] hover:text-emerald-700 focus-within:outline-none focus-within:ring-2 focus-within:ring-[var(--color-primary)] focus-within:ring-offset-2">
                                        <span x-text="pdfUrl ? 'Ganti file PDF' : 'Pilih file PDF scan'"></span>
                                        <input type="file" wire:model="finalFile" accept="application/pdf" class="sr-only" x-bind:disabled="uploading" x-ref="pdfInput" x-on:change="pdfUrl = $refs.pdfInput.files.length > 0 ? URL.createObjectURL($refs.pdfInput.files[0]) : null">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Maksimal ukuran: 10MB</p>
                            </div>
                            @error('finalFile') <span class="text-red-500 text-xs mt-2 block font-medium flex items-center gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Footer / Aksi -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-bl-xl">
                        <button type="button" @click="$dispatch('close-modal', 'modal-upload-queue')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none transition-colors">
                            Batal
                        </button>
                        <button type="submit" x-bind:disabled="!pdfUrl || uploading || {{ $nasStatus['online'] ? 'false' : 'true' }}" class="inline-flex items-center gap-2 px-5 py-2 text-sm font-medium text-white bg-[var(--color-primary)] border border-transparent rounded-lg shadow-sm hover:bg-emerald-800 focus:outline-none transition-colors disabled:opacity-50 disabled:cursor-not-allowed" wire:loading.attr="disabled" @if(!$nasStatus['online']) disabled @endif>
                            <svg wire:loading.remove wire:target="saveFinalFile" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            <span wire:loading.remove wire:target="saveFinalFile">Simpan & Arsipkan Permanen</span>
                            <span wire:loading wire:target="saveFinalFile">Memproses Arsip...</span>
                        </button>
                    </div>
                </div>

                <!-- PANEL KANAN: PDF Preview (Lebar ~60%) -->
                <div class="flex-1 h-full bg-slate-900 relative rounded-br-xl overflow-hidden">
                    <!-- Empty State Preview -->
                    <div x-show="!pdfUrl" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 p-8 text-center">
                        <svg class="w-20 h-20 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-xl font-medium text-slate-300 mb-2 font-jakarta">Live Preview PDF</h3>
                        <p class="text-sm max-w-sm">Pilih file PDF di panel sebelah kiri untuk menampilkan pratinjau dokumen scan secara instan sebelum diarsipkan.</p>
                    </div>

                    <!-- PDF Iframe -->
                    <iframe x-show="pdfUrl" :src="pdfUrl ? pdfUrl + '#toolbar=0&navpanes=0' : ''" class="w-full h-full border-0" style="display: none;"></iframe>
                </div>
            </div>
        </form>
    </x-ui.modal>
</div>




