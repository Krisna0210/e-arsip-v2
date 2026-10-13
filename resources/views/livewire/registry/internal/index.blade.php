<div class="flex flex-col sm:flex-row h-[calc(100vh-6rem)] bg-white dark:bg-slate-900 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
    <!-- Left Sidebar: Folder Tree -->
    <div class="w-full sm:w-64 lg:w-72 flex-shrink-0 border-r border-slate-200 dark:border-slate-800 flex flex-col bg-slate-50/50 dark:bg-slate-900/50">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 tracking-wide uppercase">Loker Penyimpanan</h2>
            <button wire:click="createFolder" class="p-1.5 rounded-md bg-[var(--color-primary)] text-white hover:bg-emerald-700 transition-colors shadow-sm" title="Tambah Loker Baru">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-2 space-y-0.5 custom-scrollbar">
            <button
                wire:click="setActiveFolder(null)"
                class="w-full flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors {{ $activeFolderId === null ? 'bg-blue-50 text-[var(--color-primary)] dark:bg-blue-900/30 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <svg class="w-5 h-5 {{ $activeFolderId === null ? 'text-[var(--color-primary)] dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"></path></svg>
                Semua Dokumen
            </button>

            @foreach($folders as $folder)
                @include('livewire.registry.incoming.folder-item', ['folder' => $folder, 'level' => 0])
            @endforeach

            @if($folders->isEmpty())
                <div class="px-3 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                    Belum ada loker.
                </div>
            @endif
        </div>
    </div>

    <!-- Right Content Area -->
    <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-slate-900">
        <!-- Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 px-2 sm:px-4 flex overflow-x-auto">
            @php
                $tabs = [
                    'all' => 'Semua',
                    'waiting_for_file' => 'Menunggu Upload',
                    'archived' => 'Selesai',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <button
                    wire:click="$set('statusTab', '{{ $key }}')"
                    class="relative whitespace-nowrap px-4 py-3 text-sm font-medium transition-colors focus:outline-none
                    {{ $statusTab === $key ? 'text-[var(--color-primary)] dark:text-blue-400' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    {{ $label }}
                    @if($statusTab === $key)
                        <span class="absolute bottom-0 left-0 w-full h-0.5 bg-[var(--color-primary)] dark:bg-blue-400 rounded-t-md"></span>
                    @endif
                </button>
            @endforeach
        </div>

        <!-- Toolbar -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="relative w-full sm:w-64 flex-shrink-0">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full rounded-md border-1 py-1.5 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary)] sm:text-sm sm:leading-6 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700 dark:placeholder:text-slate-500" placeholder="Cari nomor, perihal, pengirim...">
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                <select wire:model.live="filterSifat" class="block rounded-md border-1 py-1.5 pl-3 pr-8 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary)] sm:text-sm sm:leading-6 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">
                    <option value="">Semua Sifat</option>
                    <option value="biasa">Biasa</option>
                    <option value="penting">Penting</option>
                    <option value="rahasia">Rahasia</option>
                </select>

                <div class="flex items-center gap-2">
                    <input wire:model.live="filterDateStart" type="date" class="block rounded-md border-1 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary)] sm:text-sm sm:leading-6 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">
                    <span class="text-slate-500">-</span>
                    <input wire:model.live="filterDateEnd" type="date" class="block rounded-md border-1 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary)] sm:text-sm sm:leading-6 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="flex-1 overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800/50 sticky top-0 z-10 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th scope="col" class="py-2.5 pl-4 pr-3 text-left text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider sm:pl-6 w-1/6">Nomor Surat</th>
                        <th scope="col" class="px-3 py-2.5 text-left text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-[35%]">Perihal Surat</th>
                        <th scope="col" class="px-3 py-2.5 text-left text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tanggal Surat & Tujuan Surat</th>
                        <th scope="col" class="px-3 py-2.5 text-left text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sifat & Status</th>
                        <th scope="col" class="relative py-2.5 pl-3 pr-4 sm:pr-6 text-center text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="whitespace-nowrap py-3 pl-4 pr-3 text-sm sm:pl-6 align-top">
                                <div class="font-mono text-[13px] font-bold text-slate-800 dark:text-slate-200">{{ $doc->number ?? 'Tanpa Nomor' }}</div>
                            </td>
                            <td class="px-3 py-3 text-sm align-top">
                                <div x-data="{ expanded: false }">
                                    <div 
                                        class="font-medium text-slate-800 dark:text-slate-200 leading-snug transition-all"
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
                                @if($doc->folder)
                                    <div class="mt-2 inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-medium text-slate-500 dark:text-slate-400">
                                        <svg class="w-3 h-3 mr-1 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                                        {{ $doc->folder->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm align-top">
                                <div class="flex items-center text-slate-700 dark:text-slate-300 font-medium text-xs mb-1.5">
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ $doc->document_date ? \Carbon\Carbon::parse($doc->document_date)->format('d M Y') : '-' }}
                                </div>
                                <div class="space-y-0.5 text-xs">
                                    <div class="flex items-start text-slate-600 dark:text-slate-400">
                                        <span class="font-semibold text-slate-500 dark:text-slate-400 w-8 flex-shrink-0">Dari:</span>
                                        <span class="font-medium text-slate-800 dark:text-slate-200 truncate max-w-[200px]" title="{{ $doc->unit->name ?? '-' }}">{{ $doc->unit->name ?? '-' }}</span>
                                    </div>
                                    <div class="flex items-start text-slate-600 dark:text-slate-400">
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400 w-8 flex-shrink-0">Ke:</span>
                                        @php
                                            $recipientUnit = $doc->recipients->first()?->recipient?->name ?? ($doc->recipient_name ?? '-');
                                        @endphp
                                        <span class="font-medium text-slate-800 dark:text-slate-200 truncate max-w-[200px]" title="{{ $recipientUnit }}">{{ $recipientUnit }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm align-top">
                                <div class="flex flex-col items-start gap-1.5">
                                    @php
                                        $sifatColors = [
                                            'biasa' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                            'penting' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            'rahasia' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                        ];
                                        $sifatClass = $sifatColors[$doc->sifat] ?? $sifatColors['biasa'];
                                        $sifatLabel = ucfirst($doc->sifat);
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $sifatClass }}">
                                        {{ $sifatLabel }}
                                    </span>

                                    @php
                                        $statusColors = [
                                            'waiting_for_file' => 'bg-amber-100 text-amber-800 border-amber-300 shadow-sm',
                                            'archived' => 'bg-emerald-600 text-white border-slate-400 shadow-sm',
                                        ];

                                        $statusLabels = [
                                            'waiting_for_file' => 'Menunggu Upload',
                                            'archived' => 'Diarsipkan',
                                        ];

                                        $statusClass = $statusColors[$doc->status] ?? $statusColors['archived'];
                                        $statusLabel = $statusLabels[$doc->status] ?? ucfirst($doc->status);
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            </td>
                            <td class="relative py-3 pl-3 pr-4 text-center text-sm font-medium sm:pr-6 align-top">
                                @if($doc->currentVersion && $doc->currentVersion->file_path)
                                    @if(Auth::user()->hasDocumentPermission('view', $doc->sifat))
                                        <a href="{{ route('document.preview', $doc->currentVersion->id) }}" target="_blank" class="inline-flex items-center justify-center text-blue-600 hover:text-blue-900 bg-blue-50 dark:bg-blue-900/30 p-1.5 rounded-md hover:bg-blue-100 dark:hover:bg-blue-800/50 transition-colors" title="Lihat Dokumen">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center justify-center text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800/50 p-1.5 rounded-md cursor-not-allowed" title="Akses Dibatasi: Memerlukan izin melihat dokumen {{ ucfirst($doc->sifat) }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400 dark:text-slate-600 font-mono text-sm inline-block select-none">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">Tidak ada dokumen</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Belum ada dokumen yang sesuai dengan kriteria filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="px-6 py-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                {{ $documents->links('vendor.pagination.custom') }}
            </div>
        @endif
    </div>

    <!-- Modal Folder Overlays etc... -->
    <!-- Modal Folder Baru -->
    <div
        x-data="{ open: false }"
        x-show="open"
        @open-folder-modal.window="open = true"
        @close-folder-modal.window="open = false"
        class="relative z-50"
        aria-labelledby="slide-over-title"
        role="dialog"
        aria-modal="true"
        style="display: none;"
    >
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div
                        x-show="open"
                        x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-300"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-full"
                        class="pointer-events-auto w-screen max-w-md"
                        @click.away="open = false"
                    >
                        <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 shadow-xl border-l border-slate-200 dark:border-slate-800">
                            <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h2 class="text-lg font-semibold leading-6 text-slate-900 dark:text-slate-100" id="slide-over-title">Tambah Loker Memo Internal</h2>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Loker ini khusus untuk mengelompokkan berkas Memo Internal.</p>
                                    </div>
                                    <div class="ml-3 flex h-7 items-center">
                                        <button type="button" @click="open = false" class="relative rounded-md bg-white dark:bg-slate-900 text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2">
                                            <span class="absolute -inset-2.5"></span>
                                            <span class="sr-only">Close panel</span>
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <form wire:submit.prevent="saveFolder" class="space-y-6">
                                        <div>
                                            <label for="folderName" class="block text-sm font-medium leading-6 text-slate-900 dark:text-slate-100">Nama Loker <span class="text-red-500">*</span></label>
                                            <div class="mt-2">
                                                <input type="text" wire:model="folderName" id="folderName" class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary)] sm:text-sm sm:leading-6 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700" placeholder="Contoh: Tahun 2026">
                                            </div>
                                            @error('folderName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        <div>
                                            <label for="folderParentId" class="block text-sm font-medium leading-6 text-slate-900 dark:text-slate-100">Induk Loker</label>
                                            <div class="mt-2">
                                                <select wire:model="folderParentId" id="folderParentId" class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-[var(--color-primary)] sm:text-sm sm:leading-6 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-700">
                                                    <option value="">-- Root (Tidak ada induk) --</option>
                                                    @foreach($allFolders as $f)
                                                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <p class="mt-1 text-xs text-slate-500">Pilih jika loker ini berada di dalam loker lain.</p>
                                        </div>

                                        <div class="flex flex-shrink-0 justify-end pt-4">
                                            <button type="button" @click="open = false" class="rounded-md bg-white dark:bg-slate-800 px-3 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100 shadow-sm ring-1 ring-inset ring-slate-300 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">Batal</button>
                                            <button type="submit" class="ml-4 inline-flex justify-center rounded-md bg-[var(--color-primary)] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-primary)]">
                                                <span wire:loading.remove wire:target="saveFolder">Simpan</span>
                                                <span wire:loading wire:target="saveFolder">Menyimpan...</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




