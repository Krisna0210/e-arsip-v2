<div class="flex flex-col lg:flex-row min-h-[calc(100vh-4rem)] bg-white">
    <!-- Left Pane: Form -->
    <div class="w-full lg:w-1/2 p-6 lg:p-8 xl:p-10 overflow-y-auto border-r border-gray-200">
        <div class="max-w-2xl mx-auto">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-1">Registrasi Surat Masuk</h2>
                <p class="text-sm text-gray-500">Isi formulir di bawah ini untuk mencatat surat masuk baru.</p>
            </div>

            <form wire:submit.prevent="save" class="space-y-8">
                <!-- Section 1: Informasi Surat -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Informasi Surat</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Surat</label>
                        <input type="text" wire:model="number" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition" placeholder="Contoh: 123/UM/2026">
                        @error('number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Surat</label>
                            <input type="date" wire:model="document_date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition">
                            @error('document_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Diterima</label>
                            <input type="date" wire:model="received_date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition">
                            @error('received_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pengirim</label>
                        <input type="text" wire:model="sender" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition" placeholder="Nama Instansi/Individu Pengirim">
                        @error('sender') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit Tujuan <span class="text-red-500">*</span></label>
                        <select wire:model="unit_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                            <option value="">-- Pilih Unit Tujuan --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">
                                    {{ $unit->name }} ({{ $unit->code }}){{ $unit->parent ? ' - ' . $unit->parent->code : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('unit_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Perihal</label>
                        <textarea wire:model="subject" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition" placeholder="Ringkasan isi surat..."></textarea>
                        @error('subject') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Section 2: Klasifikasi & Distribusi -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Klasifikasi & Distribusi</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Klasifikasi Arsip <span class="text-red-500">*</span></label>
                        <select wire:model="classification_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                            <option value="">-- Pilih Klasifikasi --</option>
                            @foreach($classifications as $classification)
                                <option value="{{ $classification->id }}">{{ $classification->code }} - {{ $classification->name ?? $classification->subject }}</option>
                            @endforeach
                        </select>
                        @error('classification_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi Penyimpanan (Loker)</label>
                        <select wire:model="folder_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                            <option value="">-- Tanpa Loker --</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @endforeach
                        </select>
                        @error('folder_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div x-data class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700">
                            Sifat Surat <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="relative flex items-center justify-center cursor-pointer rounded-lg border px-3 py-2.5 text-center transition-all duration-150 select-none"
                                   :class="$wire.sifat === 'biasa' ? 'border-emerald-600 bg-emerald-50/60 text-emerald-800 font-bold ring-1 ring-emerald-600 shadow-2xs' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 font-medium'">
                                <input type="radio" wire:model="sifat" value="biasa" class="sr-only">
                                <span class="text-xs">Biasa</span>
                            </label>
                            <label class="relative flex items-center justify-center cursor-pointer rounded-lg border px-3 py-2.5 text-center transition-all duration-150 select-none"
                                   :class="$wire.sifat === 'penting' ? 'border-amber-600 bg-amber-50/60 text-amber-800 font-bold ring-1 ring-amber-600 shadow-2xs' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 font-medium'">
                                <input type="radio" wire:model="sifat" value="penting" class="sr-only">
                                <span class="text-xs">Penting</span>
                            </label>
                            <label class="relative flex items-center justify-center cursor-pointer rounded-lg border px-3 py-2.5 text-center transition-all duration-150 select-none"
                                   :class="$wire.sifat === 'rahasia' ? 'border-rose-600 bg-rose-50/60 text-rose-800 font-bold ring-1 ring-rose-600 shadow-2xs' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 font-medium'">
                                <input type="radio" wire:model="sifat" value="rahasia" class="sr-only">
                                <span class="text-xs">Rahasia</span>
                            </label>
                        </div>
                        @error('sifat') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                                <!-- Form Actions -->
                <div class="pt-6 border-t border-gray-200 flex flex-col sm:flex-row items-center gap-4">
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-[var(--color-primary)] text-white rounded-lg font-medium shadow-sm hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)] transition-all">
                        Simpan ke Arsip
                    </button>

                    <div wire:loading class="text-sm text-gray-500">
                        Memproses...
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Pane: PDF Preview -->
    <div class="w-full lg:w-1/2 bg-gray-50 p-6 lg:p-8 xl:p-10 lg:sticky lg:top-0 h-[600px] lg:h-screen flex flex-col">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex-none">Pratinjau Dokumen</h3>

                <div class="flex-1 border-2 border-dashed border-gray-300 rounded-2xl flex flex-col items-center justify-center bg-white relative overflow-hidden transition-colors"
             x-data="{ isDropping: false, objectUrl: null }"
             x-on:dragover.prevent="isDropping = true"
             x-on:dragleave.prevent="isDropping = false"
             x-on:drop.prevent="isDropping = false;
                let f = $event.dataTransfer.files[0];
                if(f && f.type === 'application/pdf') { objectUrl = URL.createObjectURL(f); }
                $wire.upload('file', f);"
             :class="{ 'border-[var(--color-primary)] bg-blue-50/50': isDropping }">

            <template x-if="objectUrl">
                <object x-bind:data="objectUrl" type="application/pdf" class="w-full h-full absolute inset-0 rounded-xl z-10"></object>
            </template>

            <div x-show="!objectUrl" class="w-full h-full flex flex-col items-center justify-center relative">
                @if ($file)
                    @if (!in_array($file->extension(), ['pdf', 'PDF']))
                        <div class="p-6 text-center">
                            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-700 font-medium">File dipilih: {{ $file->getClientOriginalName() }}</p>
                            <p class="text-sm text-gray-500 mt-1">Pratinjau hanya tersedia untuk file PDF.</p>
                        </div>
                    @else
                        <!-- Fallback if objectUrl is lost but file exists -->
                        <div class="p-6 text-center">
                            <svg class="mx-auto h-16 w-16 text-emerald-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-gray-700 font-medium">File PDF berhasil dipilih: {{ $file->getClientOriginalName() }}</p>
                            <p class="text-sm text-gray-500 mt-1">Siap untuk diunggah.</p>
                        </div>
                    @endif
                @else
                    <div class="text-center p-8 max-w-sm">
                        <div class="bg-gray-100 rounded-full p-4 w-20 h-20 mx-auto mb-6 flex items-center justify-center">
                            <svg class="h-10 w-10 text-[var(--color-primary)]" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Upload Dokumen Surat</h4>
                        <div class="flex text-sm text-gray-600 justify-center mb-2">
                            <label for="file-upload" class="relative cursor-pointer rounded-md font-semibold text-[var(--color-primary)] hover:text-blue-700 focus-within:outline-none transition-colors">
                                <span>Pilih file</span>
                                <input id="file-upload" wire:model="file" type="file" class="sr-only" accept="application/pdf"
                                       x-on:change="let f = $event.target.files[0]; if(f && f.type === 'application/pdf') { objectUrl = URL.createObjectURL(f); }">
                            </label>
                            <p class="pl-1">atau drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PDF maksimal 10MB</p>
                    </div>
                @endif
            </div>

            <!-- Clear file button -->
            <div x-show="objectUrl || '{{ $file ? 1 : 0 }}' === '1'">
                <button type="button" @click="objectUrl = null; $wire.set('file', null)" class="absolute top-4 right-4 bg-white p-2.5 rounded-full shadow-md hover:bg-red-50 hover:text-red-600 text-gray-600 transition-colors z-20 focus:outline-none" title="Hapus File">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Progress Bar for File Upload -->
            <div x-data="{ isUploading: false, progress: 0 }"
                 x-on:livewire-upload-start="isUploading = true"
                 x-on:livewire-upload-finish="isUploading = false"
                 x-on:livewire-upload-error="isUploading = false"
                 x-on:livewire-upload-progress="progress = $event.detail.progress"
                 class="absolute bottom-6 left-6 right-6 transition-opacity duration-300 z-20"
                 x-show="isUploading"
                 style="display: none;">
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden shadow-inner">
                    <div class="bg-[var(--color-primary)] h-2 rounded-full transition-all duration-300 ease-out" x-bind:style="`width: ${progress}%`"></div>
                </div>
                <p class="text-xs text-center mt-2 text-gray-700 font-medium" x-text="`Mengunggah... ${progress}%`"></p>
            </div>
        </div>
        @error('file') <div class="text-red-500 text-sm mt-3 font-medium bg-red-50 p-3 rounded-lg border border-red-100">{{ $message }}</div> @enderror
    </div>

    <!-- Modal Sukses Arsip Surat Masuk -->
    <div x-data="{ show: @entangle('showSuccessModal') }" x-show="show" x-cloak class="relative z-[999]" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="show" x-transition.opacity class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="show" x-transition class="relative transform overflow-hidden rounded-2xl bg-white px-4 pb-5 pt-6 text-left shadow-2xl transition-all sm:my-8 w-full sm:max-w-md sm:p-6">
                    <div>
                        <!-- Icon Checkmark Bulat -->
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                            <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <div class="mt-4 text-center">
                            <h3 class="text-lg font-bold text-slate-900 font-jakarta" id="modal-title">
                                Berhasil Diarsipkan!
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">
                                Berkas surat masuk fisik dan file scan telah berhasil dicatat dan disimpan ke repositori arsip.
                            </p>

                            <!-- Ringkasan Kartu Data Naskah -->
                            <div class="mt-4 bg-slate-50 rounded-xl p-4 border border-slate-200 text-left space-y-2.5">
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nomor Surat</div>
                                    <div class="font-mono text-sm font-bold text-[var(--color-primary)] mt-0.5">
                                        {{ $savedDocumentNumber }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Perihal</div>
                                    <div class="text-xs font-semibold text-slate-800 line-clamp-2 mt-0.5">
                                        {{ $savedDocumentSubject }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200/80 text-xs">
                                    <div>
                                        <div class="text-[10px] text-slate-400">Pengirim:</div>
                                        <div class="font-medium text-slate-700 truncate">{{ $savedDocumentSender }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400">Tujuan:</div>
                                        <div class="font-medium text-slate-700 truncate">{{ $savedDocumentUnitName }}</div>
                                    </div>
                                </div>
                                <div class="pt-1 text-[11px] text-slate-500 flex items-center gap-1.5">
                                    <span class="text-slate-400">Loker:</span>
                                    <span class="font-semibold text-slate-700 font-mono">{{ $savedDocumentFolderName }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Cepat -->
                    <div class="mt-6 flex flex-col gap-2">
                        <button wire:click="resetFormAndInputAgain" type="button" class="w-full inline-flex justify-center items-center gap-1.5 rounded-xl bg-[var(--color-primary)] px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:brightness-110 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span>Input Surat Masuk Lagi</span>
                        </button>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button wire:click="redirectToIndex" type="button" class="w-full inline-flex justify-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                Daftar Arsip
                            </button>
                            <button wire:click="finishAndRedirect" type="button" class="w-full inline-flex justify-center rounded-xl bg-white border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                Ke Dashboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


