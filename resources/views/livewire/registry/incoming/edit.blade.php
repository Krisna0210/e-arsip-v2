<div class="flex flex-col lg:flex-row min-h-[calc(100vh-4rem)] bg-white">
    <!-- Left Pane: Form -->
    <div class="w-full lg:w-1/2 p-6 lg:p-8 xl:p-10 overflow-y-auto border-r border-gray-200">
        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-1">Edit / Revisi Surat Masuk</h2>
                <p class="text-sm text-gray-500">Perbaiki data atau unggah file baru untuk mengajukan ulang surat yang ditolak.</p>
            </div>

            @if($rejectionReason)
            <div class="mb-8 p-4 rounded-lg bg-red-50 border border-red-200">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Ditolak oleh {{ $rejectorName }} pada {{ $rejectedAt }}</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p><strong>Alasan:</strong> {{ $rejectionReason }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Flash Message -->
            @if (session()->has('success'))
                <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50">
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit.prevent="resubmit" class="space-y-8">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Perihal</label>
                        <textarea wire:model="subject" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition" placeholder="Ringkasan isi surat..."></textarea>
                        @error('subject') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Section 2: Klasifikasi & Distribusi -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">Klasifikasi & Distribusi</h3>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lokasi Penyimpanan (Loker)</label>
                        <select wire:model="folder_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                            <option value="">-- Tanpa Loker / Root --</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                            @endforeach
                        </select>
                        @error('folder_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div x-data>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Sifat Surat</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- Biasa -->
                            <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:bg-gray-50 focus:outline-none transition-all duration-200" 
                                   :class="{ 'border-emerald-500 ring-2 ring-emerald-500 bg-emerald-50/20': $wire.sifat === 'biasa' }">
                                <input type="radio" wire:model="sifat" value="biasa" class="sr-only">
                                <span class="flex flex-1">
                                    <span class="flex flex-col">
                                        <span class="block text-sm font-semibold text-gray-900" :class="{ 'text-emerald-700': $wire.sifat === 'biasa' }">Biasa</span>
                                        <span class="mt-1 flex items-center text-xs text-gray-500">Reguler</span>
                                    </span>
                                </span>
                                <svg class="h-6 w-6 text-emerald-600 transition-opacity duration-200" :class="{'opacity-100': $wire.sifat === 'biasa', 'opacity-0': $wire.sifat !== 'biasa'}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </label>
                            
                            <!-- Penting -->
                            <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:bg-gray-50 focus:outline-none transition-all duration-200" 
                                   :class="{ 'border-amber-500 ring-2 ring-amber-500 bg-amber-50/20': $wire.sifat === 'penting' }">
                                <input type="radio" wire:model="sifat" value="penting" class="sr-only">
                                <span class="flex flex-1">
                                    <span class="flex flex-col">
                                        <span class="block text-sm font-semibold text-gray-900" :class="{ 'text-amber-700': $wire.sifat === 'penting' }">Penting</span>
                                        <span class="mt-1 flex items-center text-xs text-gray-500">Prioritas</span>
                                    </span>
                                </span>
                                <svg class="h-6 w-6 text-amber-600 transition-opacity duration-200" :class="{'opacity-100': $wire.sifat === 'penting', 'opacity-0': $wire.sifat !== 'penting'}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </label>

                            <!-- Rahasia -->
                            <label class="relative flex cursor-pointer rounded-xl border border-gray-200 bg-white p-4 shadow-sm hover:bg-gray-50 focus:outline-none transition-all duration-200" 
                                   :class="{ 'border-rose-500 ring-2 ring-rose-500 bg-rose-50/20': $wire.sifat === 'rahasia' }">
                                <input type="radio" wire:model="sifat" value="rahasia" class="sr-only">
                                <span class="flex flex-1">
                                    <span class="flex flex-col">
                                        <span class="block text-sm font-semibold text-gray-900" :class="{ 'text-rose-700': $wire.sifat === 'rahasia' }">Rahasia</span>
                                        <span class="mt-1 flex items-center text-xs text-gray-500">Konfidensial</span>
                                    </span>
                                </span>
                                <svg class="h-6 w-6 text-rose-600 transition-opacity duration-200" :class="{'opacity-100': $wire.sifat === 'rahasia', 'opacity-0': $wire.sifat !== 'rahasia'}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </label>
                        </div>
                        @error('sifat') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit Tujuan</label>
                        <select wire:model="unit_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition bg-white">
                            <option value="">-- Pilih Unit Tujuan --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        @error('unit_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="pt-6 border-t border-gray-200 flex flex-col sm:flex-row items-center gap-4">
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-[var(--color-primary)] text-white rounded-lg font-medium shadow-sm hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)] transition-all">
                        Ajukan Ulang Verifikasi
                    </button>
                    <a href="{{ route('registry.incoming.index') }}" class="w-full sm:w-auto px-6 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-colors text-center">
                        Batal
                    </a>
                    
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
            
            <template x-if="!objectUrl">
                <object data="{{ route('document.preview', $document->current_version_id) }}" type="application/pdf" class="w-full h-full absolute inset-0 rounded-xl z-10"></object>
            </template>

            <!-- File Change Button -->
            <div class="absolute bottom-4 right-4 z-20">
                <label for="file-upload" class="cursor-pointer bg-white px-4 py-2 rounded-lg shadow-md border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Ganti File PDF
                </label>
                <input id="file-upload" wire:model="file" type="file" class="sr-only" accept="application/pdf"
                       x-on:change="let f = $event.target.files[0]; if(f && f.type === 'application/pdf') { objectUrl = URL.createObjectURL(f); }">
            </div>

            <!-- Clear file button (only for newly uploaded file) -->
            <div x-show="objectUrl || '{{ $file ? 1 : 0 }}' === '1'" style="display: none;">
                <button type="button" @click="objectUrl = null; $wire.set('file', null)" class="absolute top-4 right-4 bg-white p-2.5 rounded-full shadow-md hover:bg-red-50 hover:text-red-600 text-gray-600 transition-colors z-20 focus:outline-none" title="Batal Ganti File">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Progress Bar for File Upload -->
            <div x-data="{ isUploading: false, progress: 0 }"
                 x-on:livewire-upload-start="isUploading = true"
                 x-on:livewire-upload-finish="isUploading = false"
                 x-on:livewire-upload-error="isUploading = false"
                 x-on:livewire-upload-progress="progress = $event.detail.progress"
                 class="absolute bottom-16 left-6 right-6 transition-opacity duration-300 z-20"
                 x-show="isUploading"
                 style="display: none;">
                <div class="bg-white/90 backdrop-blur p-4 rounded-xl shadow-lg border border-gray-100">
                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden shadow-inner">
                        <div class="bg-[var(--color-primary)] h-2 rounded-full transition-all duration-300 ease-out" x-bind:style="`width: ${progress}%`"></div>
                    </div>
                    <p class="text-xs text-center mt-2 text-gray-700 font-medium" x-text="`Mengunggah... ${progress}%`"></p>
                </div>
            </div>
        </div>
        @error('file') <div class="text-red-500 text-sm mt-3 font-medium bg-red-50 p-3 rounded-lg border border-red-100">{{ $message }}</div> @enderror
    </div>
</div>
