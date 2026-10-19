<div class="flex flex-col lg:flex-row min-h-[calc(100vh-4rem)] bg-white">
    <!-- Left Pane: Form -->
    <div class="w-full lg:w-1/2 p-6 lg:p-8 xl:p-10 overflow-y-auto border-r border-gray-200">
        <div class="max-w-2xl mx-auto">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-1">Registrasi Surat Keluar</h2>
                <p class="text-sm text-gray-500">Isi formulir di bawah ini untuk membuat dan mengajukan surat keluar baru.</p>
            </div>

<form wire:submit.prevent="save" class="space-y-8">
                <!-- Section 1: Informasi Surat -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Informasi Surat</h3>

                    @if($this->canArchive)
                    <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                        <label class="block text-sm font-semibold text-emerald-900 mb-1">
                            Nomor Surat Fisik (Khusus Arsip Langsung / Dokumen Lama)
                        </label>
                        <input
                            type="text"
                            wire:model="number"
                            placeholder="Kosongkan jika ingin nomor otomatis"
                            class="w-full px-4 py-2.5 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] font-mono text-sm bg-white"
                        >
                        <p class="text-[11px] text-emerald-700 mt-1.5 leading-snug">
                            Ketik nomor fisik dokumen jika Anda sedang mendigitalkan arsip lama. Jika dikosongkan, sistem otomatis menerbitkan nomor berikutnya secara urut.
                        </p>
                        @error('number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Surat</label>
                        <input type="date" wire:model="document_date" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                        @error('document_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit Pengirim (Divisi)</label>
                        <select wire:model.live="unit_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                            <option value="">-- Pilih Divisi Pengirim --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                            @endforeach
                        </select>
                        @error('unit_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Pejabat Penandatangan Surat (Minimalis & Rapi) -->
                    <div x-data class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700">
                            Pejabat Penandatangan Surat <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative flex items-center justify-center cursor-pointer rounded-lg border px-4 py-2.5 text-center transition-all duration-150 select-none"
                                   :class="$wire.signer_type === 'pimpinan_unit' ? 'border-[var(--color-primary)] bg-emerald-50/40 text-[var(--color-primary)] font-bold ring-1 ring-[var(--color-primary)] shadow-xs' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 font-medium'">
                                <input type="radio" wire:model.live="signer_type" value="pimpinan_unit" class="sr-only">
                                <span class="text-xs">Pimpinan Unit Kerja</span>
                            </label>
                            <label class="relative flex items-center justify-center cursor-pointer rounded-lg border px-4 py-2.5 text-center transition-all duration-150 select-none"
                                   :class="$wire.signer_type === 'direktur' ? 'border-[var(--color-primary)] bg-emerald-50/40 text-[var(--color-primary)] font-bold ring-1 ring-[var(--color-primary)] shadow-xs' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 font-medium'">
                                <input type="radio" wire:model.live="signer_type" value="direktur" class="sr-only">
                                <span class="text-xs">Direktur</span>
                            </label>
                        </div>
                        @error('signer_type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tujuan / Penerima</label>
                        <input type="text" wire:model="recipient_name" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition" placeholder="Nama Instansi/Individu Penerima">
                        @error('recipient_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Perihal</label>
                        <textarea wire:model="subject" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition" placeholder="Ringkasan isi surat..."></textarea>
                        @error('subject') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Section 2: Klasifikasi & Atribut -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Klasifikasi & Atribut</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Dokumen</label>
                        <select wire:model="document_type_id" disabled class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100/80 text-gray-700 cursor-not-allowed outline-none transition select-none">
                            @foreach($documentTypes as $docType)
                                <option value="{{ $docType->id }}">{{ $docType->name }}</option>
                            @endforeach
                        </select>
                        @error('document_type_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Klasifikasi Arsip</label>
                        <select wire:model.live="classification_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                            <option value="">-- Pilih Klasifikasi --</option>
                            @foreach($classifications as $classification)
                                <option value="{{ $classification->id }}">{{ $classification->code }} - {{ $classification->name ?? $classification->subject }}</option>
                            @endforeach
                        </select>
                        @error('classification_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($this->canArchive)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Loker Penyimpanan</label>
                        <select wire:model="folder_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                            <option value="">-- Tanpa Loker --</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @endforeach
                        </select>
                        @error('folder_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    @endif

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
                <div class="pt-6 border-t border-gray-200 flex flex-col sm:flex-row items-center gap-3">
                    <button type="button" wire:click="bookNumber" class="w-full sm:w-auto px-6 py-2.5 bg-slate-800 text-white rounded-lg font-medium shadow-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-800 transition-all">
                        Ambil Nomor Surat
                    </button>

                    @if($this->canArchive)
                        <button type="button" wire:click="saveAndArchive" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-medium shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-600 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan & Arsipkan</span>
                        </button>
                    @endif

                    <div wire:loading class="text-sm text-gray-500">
                        Memproses...
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Pane: PDF Preview / Workflow Guide -->
    <div class="w-full lg:w-1/2 bg-gray-50 p-6 lg:p-8 xl:p-10 lg:sticky lg:top-0 h-[600px] lg:h-screen flex flex-col">
        @if($this->canArchive)
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
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Upload Dokumen (Opsional)</h4>
                            <div class="flex text-sm text-gray-600 justify-center mb-2">
                                <label for="file-upload" class="relative cursor-pointer rounded-md font-semibold text-[var(--color-primary)] hover:text-blue-700 focus-within:outline-none transition-colors">
                                    <span>Pilih file</span>
                                    <input id="file-upload" wire:model="file" type="file" class="sr-only" accept="application/pdf"
                                           x-on:change="let f = $event.target.files[0]; if(f && f.type === 'application/pdf') { objectUrl = URL.createObjectURL(f); }">
                                </label>
                                <p class="pl-1">atau drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PDF maksimal 10MB. Gunakan untuk digitalisasi langsung.</p>
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
        @else
            <!-- Regular Staff View: Alur Booking Nomor Surat -->
            <div class="flex-1 rounded-2xl flex flex-col justify-center items-center bg-white border border-slate-200 p-8 text-center shadow-xs">
                <div class="w-16 h-16 bg-emerald-50 text-[var(--color-primary)] rounded-2xl flex items-center justify-center mb-5 shadow-xs">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>

                <h4 class="text-lg font-bold text-slate-800 font-jakarta mb-2">Alur Booking Nomor Surat</h4>
                <p class="text-xs text-slate-500 max-w-sm mb-6 leading-relaxed">
                    Akun staf Anda difokuskan untuk menerbitkan nomor surat resmi secara cepat dan akurat.
                </p>

                <div class="w-full max-w-md bg-slate-50 rounded-xl p-4 sm:p-5 border border-slate-100 text-left space-y-3.5">
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-[var(--color-primary)] text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">1</div>
                        <div>
                            <div class="text-xs font-semibold text-slate-800">Isi Formulir</div>
                            <div class="text-[11px] text-slate-500 leading-snug">Lengkapi informasi unit, penerima, dan perihal di sebelah kiri.</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-[var(--color-primary)] text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">2</div>
                        <div>
                            <div class="text-xs font-semibold text-slate-800">Ambil Nomor Surat</div>
                            <div class="text-[11px] text-slate-500 leading-snug">Klik tombol "Ambil Nomor Surat" untuk menerbitkan nomor resmi otomatis.</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-[var(--color-primary)] text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">3</div>
                        <div>
                            <div class="text-xs font-semibold text-slate-800">Cetak & Tanda Tangan Fisik</div>
                            <div class="text-[11px] text-slate-500 leading-snug">Salin nomor resmi ke draf dokumen Anda lalu cetak untuk ditandatangani dan dicap basah.</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-6 h-6 rounded-full bg-[var(--color-primary)] text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">4</div>
                        <div>
                            <div class="text-xs font-semibold text-slate-800">Setorkan ke Tapersip</div>
                            <div class="text-[11px] text-slate-500 leading-snug">Bawa fisik surat final ke Tapersip untuk di-scan dan diarsipkan permanen ke sistem.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-2 text-[11px] text-slate-400">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Fitur upload langsung hanya dapat diakses oleh petugas Tata Persuratan / Admin.</span>
                </div>
            </div>
        @endif
    </div>
    <!-- Modal Sukses Booking Nomor -->
    <div x-data="{ show: @entangle('showSuccessModal') }" x-show="show" x-cloak class="relative z-[999]" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="show" x-transition.opacity class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div x-show="show" x-transition class="relative transform overflow-hidden rounded-2xl bg-white px-4 pb-4 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:p-6">
                    <div>
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div class="mt-4 text-center sm:mt-5">
                            <h3 class="text-xl font-bold leading-6 text-gray-900" id="modal-title">Berhasil Booking Nomor!</h3>
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-2">Silakan salin nomor resmi di bawah ini ke dalam draf dokumen Anda:</p>
                                <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-4">
                                    <span class="text-2xl font-mono font-bold text-[var(--color-primary)] selectable" style="user-select: all;">{{ $number }}</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-3">* Jangan lupa untuk menyerahkan dokumen fisik final ke Tapersip agar diunggah ke sistem.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 sm:mt-8">
                        <button wire:click="finishAndRedirect" type="button" class="inline-flex w-full justify-center rounded-xl bg-[var(--color-primary)] px-3 py-3 text-sm font-semibold text-white shadow-sm hover:brightness-110 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2">
                            Selesai & Kembali ke Dashboard
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sukses Arsip Surat Keluar (Khusus Simpan & Arsipkan Tapersip) -->
    <div x-data="{ show: @entangle('showArchiveSuccessModal') }" x-show="show" x-cloak class="relative z-[999]" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
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
                                Berkas surat keluar dan dokumen fisik telah berhasil dicatat dan diarsipkan secara permanen.
                            </p>

                            <!-- Ringkasan Kartu Data Naskah -->
                            <div class="mt-4 bg-slate-50 rounded-xl p-4 border border-slate-200 text-left space-y-2.5">
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nomor Surat Keluar</div>
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
                                        <div class="font-medium text-slate-700 truncate">{{ $savedDocumentUnitName }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] text-slate-400">Tujuan:</div>
                                        <div class="font-medium text-slate-700 truncate">{{ $savedDocumentRecipient }}</div>
                                    </div>
                                </div>
                                <div class="pt-1 text-[11px] text-slate-500 flex items-center justify-between border-t border-slate-200/80">
                                    <span class="text-slate-400">Penandatangan: <strong class="text-slate-700 font-medium">{{ $savedDocumentSignerLabel }}</strong></span>
                                    <span class="font-semibold text-slate-700 font-mono text-[10px] bg-white px-1.5 py-0.5 rounded border border-slate-200">{{ $savedDocumentFolderName }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Cepat -->
                    <div class="mt-6 flex flex-col gap-2">
                        <button wire:click="resetFormAndInputAgain" type="button" class="w-full inline-flex justify-center items-center gap-1.5 rounded-xl bg-[var(--color-primary)] px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:brightness-110 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            <span>Input Surat Keluar Lagi</span>
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

