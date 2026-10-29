<div class="space-y-6" @if(!$approveDocId && !$rejectDocId) wire:poll.visible.15s @endif>
    <!-- Header Halaman (Zero Icons) -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Pembatalan Dokumen</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pengelolaan permohonan pembatalan nomor dokumen dan list dokumen resmi yang dibatalkan.</p>
        </div>
    </div>

    <!-- Tab Navigasi Mandiri (Zero Icons) -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <!-- Tab 1: Permohonan Batal -->
            <button
                type="button"
                wire:click="setTab('requests')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer {{ $activeTab === 'requests' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span>Permohonan Batal</span>
                @if($pendingCount > 0)
                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-rose-100 text-rose-700">
                        {{ $pendingCount }}
                    </span>
                @endif
            </button>

            <!-- Tab 2: Buku Register Batal (Void) -->
            <button
                type="button"
                wire:click="setTab('register')"
                class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer {{ $activeTab === 'register' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span>List Dokumen Batal</span>
                @if($voidCount > 0)
                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-slate-100 text-slate-700">
                        {{ $voidCount }}
                    </span>
                @endif
            </button>
        </nav>
    </div>

    <!-- Panel Filter & Pencarian (Zero Icons) -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Input Kata Kunci -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Cari Dokumen</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Ketik nomor surat, perihal, staf, atau alasan..."
                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] bg-slate-50 focus:bg-white transition-colors"
                >
            </div>

            <!-- Filter Jenis Naskah -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Jenis Naskah</label>
                <select wire:model.live="filterType" class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] bg-slate-50">
                    <option value="all">Semua Jenis Naskah</option>
                    <option value="outgoing">Surat Keluar</option>
                    <option value="internal">Surat Khusus</option>
                </select>
            </div>

            <!-- Rentang Tanggal Dokumen -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">Tanggal Dokumen</label>
                <div class="flex items-center gap-1.5">
                    <input
                        type="date"
                        wire:model.live="filterDateStart"
                        class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] bg-slate-50"
                    >
                    <span class="text-xs text-slate-400">-</span>
                    <input
                        type="date"
                        wire:model.live="filterDateEnd"
                        class="w-full rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)] bg-slate-50"
                    >
                </div>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-400">
                Menampilkan {{ $activeTab === 'requests' ? 'antrean permohonan pembatalan' : 'buku register nomor yang dibatalkan' }}
            </span>
            <button
                type="button"
                wire:click="resetFilters"
                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-800 transition-colors cursor-pointer">
                Reset Filter
            </button>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- TABEL UTAMA (Zero Icons)                                       -->
    <!-- ============================================================== -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[950px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <th class="px-5 py-3.5 w-1/4">Identitas Dokumen</th>
                        <th class="px-5 py-3.5 w-1/3">Perihal</th>
                        <th class="px-5 py-3.5">Pemohon</th>
                        <th class="px-5 py-3.5 w-1/4">Alasan Pembatalan</th>
                        @if($activeTab === 'requests')
                            <th class="px-5 py-3.5 text-center">Waktu Permohonan</th>
                            <th class="px-5 py-3.5 text-center w-44">Aksi</th>
                        @else
                            <th class="px-5 py-3.5">Disahkan Batal</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($documents as $doc)
                        <tr id="doc-row-{{ $doc->id }}"
                            x-data="{
                                isTarget: new URLSearchParams(window.location.search).get('highlight') === 'doc-{{ $doc->id }}',
                                init() {
                                    if (this.isTarget) {
                                        this.$el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        setTimeout(() => { this.isTarget = false; }, 5000);
                                    }
                                }
                            }"
                            :class="{ 'hover:bg-slate-50/70': !isTarget, '[&>td]:!bg-[#f1f5f9]': isTarget }"
                            :style="isTarget ? 'background-color: #f1f5f9; outline: 1.5px solid #cbd5e1; outline-offset: -1px; transition: all 0.7s ease;' : 'transition: all 0.7s ease;'">
                            <!-- Nomor & Jenis Dokumen -->
                            <td class="px-5 py-4 align-top">
                                <div class="font-mono font-bold text-slate-800 text-[13px] leading-tight">
                                    {{ $doc->number ?: 'Tanpa Nomor' }}
                                </div>
                                <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium
                                        {{ $doc->type === 'incoming' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($doc->type === 'outgoing' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-purple-50 text-purple-700 border border-purple-200') }}">
                                        {{ $doc->type === 'incoming' ? 'Surat Masuk' : ($doc->type === 'outgoing' ? 'Surat Keluar' : ($doc->documentType?->name ?? 'Surat Khusus')) }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        {{ $doc->document_date ? $doc->document_date->format('d M Y') : '-' }}
                                    </span>
                                     @if($doc->type === 'internal')
                                    @php
                                        $originUnit = $doc->unit->name ?? 'Internal';
                                        $targetUnit = $doc->recipients->first()?->recipient?->name ?? ($doc->recipient_name ?? '-');
                                    @endphp
                                    <div class="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5 flex-wrap">
                                        <span class="font-medium text-slate-700" title="{{ $originUnit }}">{{ $originUnit }}</span>
                                        <span class="text-slate-400 font-semibold">&rarr;</span>
                                        <span class="font-medium text-purple-700" title="{{ $targetUnit }}">{{ $targetUnit }}</span>
                                    </div>
                                @elseif($doc->type === 'outgoing')
                                    @php
                                        $originUnit = $doc->unit->name ?? 'Internal';
                                        $targetName = $doc->recipient_name ?? '-';
                                    @endphp
                                    <div class="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5 flex-wrap">
                                        <span class="font-medium text-slate-700" title="{{ $originUnit }}">{{ $originUnit }}</span>
                                        <span class="text-slate-400 font-semibold">&rarr;</span>
                                        <span class="font-medium text-emerald-700" title="{{ $targetName }}">{{ $targetName }}</span>
                                    </div>
                                @endif
                                </div>
                            </td>

                            <!-- Perihal & Alur Distribusi (Opsi 3 Tanpa Ikon) -->
                            <td class="px-5 py-4 align-top">
                                <div class="text-slate-800 font-medium leading-snug break-words">
                                    {{ $doc->subject }}
                                </div>

                            </td>

                            <!-- Pemohon Awal -->
                            <td class="px-5 py-4 align-top text-xs">
                                <div class="font-bold text-slate-800">
                                    {{ $doc->createdBy->name ?? 'Staf' }}
                                </div>
                                <div class="text-slate-500 mt-0.5">
                                    {{ $doc->createdBy->unit->name ?? ($doc->unit->name ?? '-') }}
                                </div>
                            </td>

                            <!-- Alasan Pembatalan -->
                            <td class="px-5 py-4 align-top text-xs text-slate-700 leading-relaxed">
                                <div class="p-2.5 bg-amber-50/70 border border-amber-200/80 rounded-lg text-amber-950">
                                    <span class="font-semibold text-amber-900 block mb-0.5">Alasan Pemohon:</span>
                                    {{ $doc->notes ?: '-' }}
                                </div>
                            </td>

                            @if($activeTab === 'requests')
                                <!-- Waktu Permohonan -->
                                <td class="px-5 py-4 align-top text-center text-xs whitespace-nowrap">
                                    <div class="font-semibold text-slate-700">
                                        {{ $doc->cancellation_requested_at ? $doc->cancellation_requested_at->format('d/m/Y') : '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $doc->cancellation_requested_at ? $doc->cancellation_requested_at->format('H:i') . ' WIB' : '-' }}
                                    </div>
                                </td>

                                <!-- Tindakan Tapersip (Zero Icons) -->
                                <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                    @if($isTapersipOrSuper)
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                wire:click="openApproveModal({{ $doc->id }})"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 transition-colors shadow-2xs cursor-pointer">
                                                Setujui Batal
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="openRejectModal({{ $doc->id }})"
                                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors cursor-pointer">
                                                Tolak
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Menunggu Tapersip</span>
                                    @endif
                                </td>
                            @else
                                <!-- Disahkan Batal Oleh & Waktu Void -->
                                <td class="px-5 py-4 align-top text-xs">
                                    <div class="font-bold text-slate-800">
                                        {{ $doc->cancelledBy->name ?? 'Admin Tapersip' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $doc->updated_at ? $doc->updated_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                </td>

                                <!-- Status Badge Void -->
                                <td class="px-5 py-4 align-top text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        BATAL (Void)
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $activeTab === 'requests' ? 6 : 6 }}" class="px-6 py-12 text-center text-slate-400 text-sm">
                                {{ $activeTab === 'requests' ? 'Tidak ada permohonan pembatalan nomor yang sedang menunggu persetujuan.' : 'Belum ada naskah dinas yang berstatus batal (void).' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/50">
                {{ $documents->links('vendor.pagination.custom') }}
            </div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: KONFIRMASI PERSETUJUAN PEMBATALAN (Zero Icons)          -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-approve-cancellation" width="max-w-md" title="Konfirmasi Persetujuan Pembatalan">
        <form wire:submit.prevent="confirmApproveCancellation" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Nomor Surat:</span>
                    <span class="font-mono font-bold text-rose-600">{{ $approveDocNumber }}</span>
                </div>
            </div>

            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 text-xs leading-relaxed">
                Persetujuan ini akan mengubah status nomor naskah menjadi <strong>BATAL (Void)</strong> permanen dalam register kearsipan. Nomor surat tidak akan dihapus demi integritas buku agenda tahunan.
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-approve-cancellation')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Setujui Pembatalan (Void)
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: PENOLAKAN PERMOHONAN PEMBATALAN (Zero Icons)            -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-reject-cancellation" width="max-w-md" title="Tolak Permohonan Pembatalan">
        <form wire:submit.prevent="confirmRejectCancellation" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Nomor Surat:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $rejectDocNumber }}</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Alasan Penolakan Pembatalan <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="rejectionNotes" rows="3"
                          placeholder="Jelaskan alasan mengapa permohonan pembatalan nomor naskah ini ditolak..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"></textarea>
                @error('rejectionNotes') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-reject-cancellation')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-[var(--color-primary)] hover:bg-emerald-800 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Kirim Penolakan
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
