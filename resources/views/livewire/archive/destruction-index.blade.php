<div>
    <!-- Header Section (Zero Icons) -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Usul Pemusnahan Arsip</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola daftar arsip yang telah habis masa retensinya dan ajukan pemusnahan berkas fisik secara perorangan maupun kolektif (batch).</p>
        </div>
    </div>

    <!-- Tabs Navigasi -->
    <div class="border-b border-slate-200 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button type="button"
                wire:click="setTab('expired')"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors cursor-pointer flex items-center gap-2 {{ $activeTab === 'expired' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span>Daftar Retensi Habis</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums {{ $activeTab === 'expired' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                    {{ $expiredCount }}
                </span>
            </button>
            <button type="button"
                wire:click="setTab('proposals')"
                class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors cursor-pointer flex items-center gap-2 {{ $activeTab === 'proposals' ? 'border-[var(--color-primary)] text-[var(--color-primary)] font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span>Usulan & Eksekusi</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums {{ $activeTab === 'proposals' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                    {{ $proposalsCount }} Berkas
                </span>
            </button>
        </nav>
    </div>

    <!-- Tab Contents (Zero Icons) -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        @if($activeTab === 'expired')
            <!-- TAB 1: RETENSI HABIS -->

            <!-- Floating Batch Action Bar (Muncul saat ada naskah yang dicentang) -->
            @if(count($selectedDocIds) > 0)
                <div class="bg-emerald-50/90 border-b border-emerald-200 px-5 py-3 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-2 text-xs text-emerald-950 font-medium">
                        <span class="font-bold text-emerald-800 text-sm tabular-nums">{{ count($selectedDocIds) }}</span> naskah terpilih untuk diajukan pemusnahan bersama.
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                wire:click="openProposeModal"
                                class="px-3.5 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-2xs cursor-pointer">
                            Ajukan Usulan Kolektif ({{ count($selectedDocIds) }})
                        </button>
                        <button type="button"
                                wire:click="clearSelection"
                                class="px-3 py-1.5 text-xs font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-lg transition-colors cursor-pointer">
                            Batalkan Pilihan
                        </button>
                    </div>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="py-3.5 pl-5 pr-2 w-10 text-center">
                                <input type="checkbox"
                                       wire:model.live="selectAll"
                                       class="rounded border-slate-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)] cursor-pointer">
                            </th>
                            <th class="px-5 py-3.5 w-1/4">Identitas Dokumen</th>
                            <th class="px-5 py-3.5 w-1/3">Perihal</th>
                            <th class="px-5 py-3.5 text-center">Tgl Habis Retensi</th>
                            <th class="px-5 py-3.5 text-right w-44">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($expiredDocuments as $doc)
                            @php
                                $isSelected = in_array((string)$doc->id, $selectedDocIds);
                            @endphp
                            <tr class="transition-colors {{ $isSelected ? 'bg-emerald-50/40' : 'hover:bg-slate-50/70' }}">
                                <!-- Checkbox Baris -->
                                <td class="py-4 pl-5 pr-2 align-top text-center">
                                    <input type="checkbox"
                                           wire:model.live="selectedDocIds"
                                           value="{{ $doc->id }}"
                                           class="rounded border-slate-300 text-[var(--color-primary)] focus:ring-[var(--color-primary)] cursor-pointer">
                                </td>

                                <!-- Identitas Dokumen (Selaras CancelledDocumentIndex) -->
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
                                    </div>

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
                                    @elseif($doc->type === 'incoming')
                                        @php
                                            $senderName = $doc->sender ?: '-';
                                            $targetUnit = $doc->unit->name ?? ($doc->recipients->first()?->recipient?->name ?? 'Internal');
                                        @endphp
                                        <div class="text-xs text-slate-500 mt-1.5 flex items-center gap-1.5 flex-wrap">
                                            <span class="font-medium text-slate-700" title="{{ $senderName }}">{{ $senderName }}</span>
                                            <span class="text-slate-400 font-semibold">&rarr;</span>
                                            <span class="font-medium text-blue-700" title="{{ $targetUnit }}">{{ $targetUnit }}</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Perihal -->
                                <td class="px-5 py-4 align-top">
                                    <div class="text-slate-800 font-medium leading-snug break-words">
                                        {{ $doc->subject }}
                                    </div>
                                </td>

                                <!-- Tanggal Habis Retensi -->
                                <td class="px-5 py-4 align-top text-center whitespace-nowrap">
                                    <div class="font-bold text-rose-600 text-xs">
                                        {{ $doc->retention_end_date ? $doc->retention_end_date->format('d M Y') : '-' }}
                                    </div>
                                    <div class="text-[11px] text-rose-500 mt-0.5 font-medium">
                                        Retensi Telah Berakhir
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                    <button type="button"
                                            wire:click="openProposeModal({{ $doc->id }})"
                                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-2xs cursor-pointer">
                                        Usul Pemusnahan
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                    Tidak ada arsip yang masa retensinya habis dan belum diajukan pemusnahan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($expiredDocuments->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $expiredDocuments->links('vendor.pagination.custom') }}
                </div>
            @endif

        @elseif($activeTab === 'proposals')
            <!-- TAB 2: USULAN & EKSEKUSI (Kolektif / Batch) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[950px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-600">
                            <th class="px-5 py-3.5 w-1/3">Berkas DUPA & Rincian Naskah</th>
                            <th class="px-5 py-3.5">Pengusul</th>
                            <th class="px-5 py-3.5 w-1/4">Alasan Pemusnahan</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right w-48">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($proposals as $batch)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <!-- Berkas DUPA & Dokumen -->
                                <td class="px-5 py-4 align-top">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-mono font-bold text-slate-900 text-[13px] bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                            {{ $batch->batch_code }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            {{ $batch->count }} Naskah
                                        </span>
                                    </div>

                                    <!-- Daftar naskah dalam berkas usulan ini -->
                                    <div class="mt-2.5 space-y-1.5">
                                        @foreach($batch->documents->take(3) as $d)
                                            <div class="text-xs text-slate-700 bg-slate-50/80 p-2 rounded border border-slate-100">
                                                <div class="font-mono font-semibold text-slate-800">{{ $d->number ?: 'Tanpa Nomor' }}</div>
                                                <div class="text-slate-500 text-[11px] truncate mt-0.5">{{ $d->subject }}</div>
                                            </div>
                                        @endforeach
                                        @if($batch->count > 3)
                                            <div class="text-[11px] text-slate-500 font-medium italic pl-1">
                                                + {{ $batch->count - 3 }} naskah lainnya dalam berkas ini
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Pengusul -->
                                <td class="px-5 py-4 align-top text-xs">
                                    <div class="font-bold text-slate-800">
                                        {{ $batch->proposer->name ?? '-' }}
                                    </div>
                                    <div class="text-slate-400 mt-0.5">
                                        {{ $batch->created_at ? $batch->created_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                </td>

                                <!-- Alasan -->
                                <td class="px-5 py-4 align-top text-xs text-slate-700 leading-relaxed">
                                    <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg">
                                        {{ $batch->reason }}
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-5 py-4 align-top text-center whitespace-nowrap">
                                    @if($batch->status === 'proposed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Diperiksa
                                        </span>
                                    @elseif($batch->status === 'approved')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                            Disetujui
                                        </span>
                                    @elseif($batch->status === 'rejected')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Ditolak
                                        </span>
                                    @elseif($batch->status === 'executed')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Dimusnahkan
                                        </span>
                                    @endif
                                </td>

                                <!-- Tindakan -->
                                <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                    @if($batch->status === 'proposed')
                                        @if($canApproveDestruction)
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button type="button"
                                                        wire:click="approveProposal('{{ $batch->batch_code }}')"
                                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors shadow-2xs cursor-pointer">
                                                    Approve
                                                </button>
                                                <button type="button"
                                                        wire:click="openRejectModal('{{ $batch->batch_code }}')"
                                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-700 bg-white border border-rose-200 hover:bg-rose-50 transition-colors cursor-pointer">
                                                    Reject
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-slate-400 text-xs font-mono select-none">-</span>
                                        @endif
                                    @elseif($batch->status === 'approved')
                                        @if($canExecuteDestruction)
                                            <button type="button"
                                                    wire:click="openExecuteModal('{{ $batch->batch_code }}')"
                                                    class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors shadow-2xs cursor-pointer">
                                                Eksekusi BA
                                            </button>
                                        @else
                                            <span class="text-xs text-blue-600 font-medium">Siap Dimusnahkan</span>
                                        @endif
                                    @elseif($batch->status === 'executed' && $batch->ba_file_path)
                                        <a href="{{ asset('storage/' . $batch->ba_file_path) }}" target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-2xs cursor-pointer">
                                            Unduh BA (PDF)
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs italic">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                    Belum ada usulan pemusnahan arsip yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($proposals->hasPages())
                <div class="px-5 py-4 border-t border-slate-100">
                    {{ $proposals->links('vendor.pagination.custom') }}
                </div>
            @endif
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: USUL PEMUSNAHAN ARSIP (Kolektif / Batch)                 -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-propose" width="max-w-xl" title="Usul Pemusnahan Arsip">
        <form wire:submit.prevent="submitProposal" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-2 text-xs">
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                    <span class="text-slate-500 font-medium">Jumlah Naskah Terpilih:</span>
                    <span class="font-bold text-emerald-700 text-sm">{{ count($selectedDocsSummary) }} Naskah</span>
                </div>

                <div class="max-h-40 overflow-y-auto divide-y divide-slate-100 pr-1">
                    @foreach($selectedDocsSummary as $s)
                        <div class="py-1.5 flex items-start justify-between gap-3">
                            <div class="truncate">
                                <div class="font-mono font-semibold text-slate-800">{{ $s['number'] }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ $s['subject'] }}</div>
                            </div>
                            <span class="text-[11px] font-medium text-rose-600 whitespace-nowrap shrink-0">{{ $s['retention_end_date'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Alasan Pemusnahan <span class="text-rose-500">*</span>
                </label>
                <textarea wire:model="proposalReason" rows="3"
                          placeholder="Jelaskan dasar pertimbangan atau pedoman JRA untuk pemusnahan arsip ini..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"></textarea>
                @error('proposalReason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-propose')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Ajukan Usulan ({{ count($selectedDocsSummary) }})
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: EKSEKUSI PEMUSNAHAN ARSIP (1 BA PDF untuk Berkas DUPA)   -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-execute" width="max-w-lg" title="Eksekusi Pemusnahan Arsip">
        <form wire:submit.prevent="executeDestruction" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Berkas Pemusnahan:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $activeRecordDocNumber }}</span>
                </div>
                <div class="flex items-start justify-between gap-2">
                    <span class="text-slate-500 font-medium shrink-0">Rincian:</span>
                    <span class="font-medium text-slate-700 text-right">{{ $activeRecordDocSubject }}</span>
                </div>
            </div>

            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-900 text-xs leading-relaxed">
                Pemusnahan berkas fisik arsip wajib disertai dokumen <strong>Berita Acara (BA) Pemusnahan</strong> yang telah ditandatangani panitia pemusnah arsip dan saksi. Satu file BA ini berlaku untuk seluruh naskah dalam berkas ini.
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Unggah Berita Acara (Format PDF) <span class="text-rose-500">*</span>
                </label>
                <input type="file" wire:model="baFile" accept="application/pdf"
                       class="block w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                @error('baFile') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                <div wire:loading wire:target="baFile" class="text-xs text-blue-600 mt-1 font-medium">
                    Sedang memproses unggahan dokumen BA...
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-execute')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Simpan Eksekusi
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- ============================================================== -->
    <!-- MODAL: PENOLAKAN USULAN PEMUSNAHAN (Zero Icons)                 -->
    <!-- ============================================================== -->
    <x-ui.modal name="modal-reject" width="max-w-md" title="Tolak Usulan Pemusnahan">
        <form wire:submit.prevent="rejectProposal" class="space-y-4">
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Berkas Pemusnahan:</span>
                    <span class="font-mono font-bold text-slate-800">{{ $rejectRecordDocNumber }}</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wide mb-1.5">
                    Catatan Alasan Penolakan
                </label>
                <textarea wire:model="rejectionReason" rows="3"
                          placeholder="Jelaskan alasan penolakan usulan pemusnahan ini..."
                          class="w-full bg-slate-50 border border-slate-200 text-xs rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-[var(--color-primary)]"></textarea>
                @error('rejectionReason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button"
                        @click="$dispatch('close-modal', 'modal-reject')"
                        class="px-4 py-2 text-xs font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors shadow-2xs cursor-pointer disabled:opacity-50">
                    Tolak Usulan
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
