<div>
    <!-- Header Section -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Usul Pemusnahan Arsip</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola daftar arsip yang telah habis masa retensinya dan ajukan pemusnahan.</p>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- Tabs -->
    <div class="border-b border-slate-200 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button wire:click="setTab('expired')"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'expired' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                Daftar Retensi Habis
            </button>
            <button wire:click="setTab('proposals')"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'proposals' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                Usulan & Eksekusi
            </button>
        </nav>
    </div>

    <!-- Tab Contents -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        @if($activeTab === 'expired')
            <!-- Retensi Habis -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold">Nomor Arsip</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Perihal</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Tgl Habis Retensi</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($expiredDocuments as $doc)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $doc->number }}</td>
                                <td class="px-6 py-4 truncate max-w-xs">{{ $doc->subject }}</td>
                                <td class="px-6 py-4 text-rose-600 font-medium">
                                    {{ $doc->retention_end_date ? $doc->retention_end_date->format('d M Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="openProposeModal({{ $doc->id }})" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">
                                        Usul Pemusnahan
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="text-sm">Tidak ada arsip yang masa retensinya habis dan belum diajukan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($expiredDocuments->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $expiredDocuments->links('vendor.pagination.custom') }}
                </div>
            @endif
        @elseif($activeTab === 'proposals')
            <!-- Usulan & Eksekusi -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold">Arsip</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Pengusul</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Alasan</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                            <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-700">
                        @forelse($proposals as $record)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $record->document->number }}</div>
                                    <div class="text-xs text-slate-500 truncate max-w-[200px] mt-0.5">{{ $record->document->subject }}</div>
                                </td>
                                <td class="px-6 py-4">{{ $record->proposer->name ?? '-' }}</td>
                                <td class="px-6 py-4 truncate max-w-xs text-slate-600" title="{{ $record->reason }}">
                                    {{ $record->reason }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($record->status === 'proposed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            Diperiksa
                                        </span>
                                    @elseif($record->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                            Disetujui
                                        </span>
                                    @elseif($record->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                            Ditolak
                                        </span>
                                    @elseif($record->status === 'executed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Dimusnahkan
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($record->status === 'proposed')
                                        <div class="flex items-center justify-end gap-2">
                                            <button wire:click="approveProposal({{ $record->id }})" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1">
                                                Approve
                                            </button>
                                            <button wire:click="rejectProposal({{ $record->id }})" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-1">
                                                Reject
                                            </button>
                                        </div>
                                    @elseif($record->status === 'approved')
                                        <button wire:click="openExecuteModal({{ $record->id }})" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                            Eksekusi
                                        </button>
                                    @elseif($record->status === 'executed' && $record->ba_file_path)
                                        <a href="{{ asset('storage/' . $record->ba_file_path) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Unduh BA
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        <p class="text-sm">Belum ada usulan pemusnahan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($proposals->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $proposals->links('vendor.pagination.custom') }}
                </div>
            @endif
        @endif
    </div>

    <!-- Alpine Modal for Propose -->
    <div x-data="{ open: false }" 
         x-on:open-modal.window="if ($event.detail === 'modal-propose') open = true"
         x-on:close-modal.window="if ($event.detail === 'modal-propose') open = false"
         x-show="open" 
         class="relative z-50" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="open" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     @click.outside="open = false"
                     class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                    
                    <form wire:submit.prevent="submitProposal">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Usul Pemusnahan Arsip</h3>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Alasan Pemusnahan <span class="text-rose-500">*</span></label>
                                        <textarea wire:model="proposalReason" rows="3" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Tuliskan alasan pemusnahan..." required></textarea>
                                        @error('proposalReason') <span class="text-rose-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-200">
                            <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:ml-3 sm:w-auto">Ajukan</button>
                            <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Alpine Modal for Execute -->
    <div x-data="{ open: false }" 
         x-on:open-modal.window="if ($event.detail === 'modal-execute') open = true"
         x-on:close-modal.window="if ($event.detail === 'modal-execute') open = false"
         x-show="open" 
         class="relative z-50" 
         aria-labelledby="modal-title-execute" role="dialog" aria-modal="true" style="display: none;">
        
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="open" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     @click.outside="open = false"
                     class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                    
                    <form wire:submit.prevent="executeDestruction">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title-execute">Eksekusi Pemusnahan</h3>
                                    <p class="text-sm text-slate-500 mt-1">Unggah Berita Acara (BA) pemusnahan untuk menyelesaikan proses ini.</p>
                                    
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Berita Acara (PDF) <span class="text-rose-500">*</span></label>
                                        <input type="file" wire:model="baFile" accept="application/pdf" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer" required>
                                        @error('baFile') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        <div wire:loading wire:target="baFile" class="text-xs text-blue-500 mt-1 font-medium">Mengunggah...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-200">
                            <button type="submit" wire:loading.attr="disabled" class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto">Simpan Eksekusi</button>
                            <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Batal</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>