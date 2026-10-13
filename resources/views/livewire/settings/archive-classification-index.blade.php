<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 tracking-tight">Master Data Kearsipan</h2>
            <p class="text-sm text-slate-500 mt-1">Kelola hierarki klasifikasi arsip, Jadwal Retensi Arsip (JRA), dan jenis dokumen.</p>
        </div>
        <div class="shrink-0">
            @if($activeTab === 'classification')
                <x-ui.button variant="primary" wire:click="createClassification">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Klasifikasi
                </x-ui.button>
            @else
                <x-ui.button variant="primary" wire:click="createDocType">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Jenis Dokumen
                </x-ui.button>
            @endif
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-[var(--color-border)]">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button wire:click="setTab('classification')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'classification' ? 'border-[var(--color-accent)] text-[var(--color-primary)]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                Klasifikasi Arsip & JRA
            </button>
            <button wire:click="setTab('type')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'type' ? 'border-[var(--color-accent)] text-[var(--color-primary)]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                Jenis Dokumen
            </button>
        </nav>
    </div>

    <!-- Tab Content: Klasifikasi Arsip -->
    @if($activeTab === 'classification')
    <div class="bg-white border border-[var(--color-border)] rounded-[var(--radius-lg)] shadow-xs overflow-hidden flex flex-col">
        <!-- Toolbar -->
        <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" class="w-full bg-white border border-slate-200 text-sm rounded-lg pl-9 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)] transition-colors" placeholder="Cari kode atau subjek klasifikasi...">
            </div>
        </div>
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-xs font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                        <th class="px-5 py-3 w-1/5">Kode / Indeks</th>
                        <th class="px-5 py-3 w-1/3">Subjek / Uraian Klasifikasi</th>
                        <th class="px-5 py-3 w-1/5">Masa Retensi (JRA)</th>
                        <th class="px-5 py-3">Nasib Akhir</th>
                        <th class="px-5 py-3 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--color-border)]">
                    @forelse($classifications as $classification)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 {{ $classification->parent_id ? 'pl-10' : '' }}">
                            <span class="font-mono text-slate-700 font-semibold bg-slate-100 px-2 py-0.5 rounded text-xs">{{ $classification->code }}</span>
                        </td>
                        <td class="px-5 py-4 font-bold text-slate-800 {{ $classification->parent_id ? 'flex items-center gap-2' : '' }}">
                            @if($classification->parent_id)
                                <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z" /></svg>
                            @endif
                            {{ $classification->subject }}
                        </td>
                        <td class="px-5 py-4 {{ $classification->active_years || $classification->inactive_years ? 'text-slate-700 font-medium' : 'text-slate-500' }}">
                            @if($classification->active_years || $classification->inactive_years)
                                Aktif: {{ $classification->active_years }} Thn | Inaktif: {{ $classification->inactive_years }} Thn
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($classification->outcome === 'musnah')
                                <span class="bg-red-50 text-red-700 px-2 py-0.5 rounded text-xs font-semibold border border-red-200">Musnah</span>
                            @elseif($classification->outcome === 'permanen')
                                <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded text-xs font-semibold border border-emerald-200">Permanen</span>
                            @elseif($classification->outcome === 'dinilai_kembali')
                                <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs font-semibold border border-blue-200">Dinilai Kembali</span>
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right space-x-1">
                            <x-ui.button wire:click="editClassification({{ $classification->id }})" variant="subtle" class="!px-2 !py-1 text-xs">Edit</x-ui.button>
                            <x-ui.button wire:click="deleteClassification({{ $classification->id }})" variant="danger" class="!px-2 !py-1 text-xs" wire:confirm="Hapus klasifikasi ini?">Hapus</x-ui.button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-4 text-center text-slate-500">Belum ada data klasifikasi arsip.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-[var(--color-border)]">
            {{ $classifications->links() }}
        </div>
    </div>
    @endif

    <!-- Tab Content: Jenis Dokumen -->
    @if($activeTab === 'type')
    <div class="bg-white border border-[var(--color-border)] rounded-[var(--radius-lg)] shadow-xs overflow-hidden flex flex-col">
        <!-- Toolbar -->
        <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" class="w-full bg-white border border-slate-200 text-sm rounded-lg pl-9 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)] transition-colors" placeholder="Cari nama dokumen...">
            </div>
        </div>
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-xs font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                        <th class="px-5 py-3 w-1/3">Nama Jenis Dokumen</th>
                        <th class="px-5 py-3 w-1/4">Sifat Akses Default</th>
                        <th class="px-5 py-3 w-1/3">Format Penomoran Standar</th>
                        <th class="px-5 py-3 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--color-border)]">
                    @forelse($documentTypes as $docType)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 font-bold text-slate-800">{{ $docType->name }}</td>
                        <td class="px-5 py-4">
                            @if($docType->default_sifat === 'biasa')
                                <span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded text-xs font-bold border border-emerald-200">Biasa (Terbuka)</span>
                            @elseif($docType->default_sifat === 'penting')
                                <span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded text-xs font-bold border border-amber-200">Penting (Internal Unit)</span>
                            @elseif($docType->default_sifat === 'rahasia')
                                <span class="bg-red-50 text-red-700 px-2 py-0.5 rounded text-xs font-bold border border-red-200">Rahasia</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $docType->numbering_format ?: '-' }}</td>
                        <td class="px-5 py-4 text-right space-x-1">
                            <x-ui.button wire:click="editDocType({{ $docType->id }})" variant="subtle" class="!px-2 !py-1 text-xs">Edit</x-ui.button>
                            <x-ui.button wire:click="deleteDocType({{ $docType->id }})" variant="danger" class="!px-2 !py-1 text-xs" wire:confirm="Hapus jenis dokumen ini?">Hapus</x-ui.button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-4 text-center text-slate-500">Belum ada data jenis dokumen.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-[var(--color-border)]">
            {{ $documentTypes->links() }}
        </div>
    </div>
    @endif

    <!-- Slide-over: Tambah Klasifikasi -->
    <x-ui.modal id="slide-classification" title="{{ $classId ? 'Edit Klasifikasi Arsip' : 'Tambah Klasifikasi Arsip Baru' }}">
        <form wire:submit="saveClassification" id="form-classification" class="space-y-5">
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Kode / Indeks <span class="text-red-500">*</span></label>
                <input wire:model="classCode" type="text" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: HK.01" required>
                @error('classCode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Subjek / Uraian Klasifikasi <span class="text-red-500">*</span></label>
                <input wire:model="classSubject" type="text" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: Produk Hukum" required>
                @error('classSubject') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Induk Klasifikasi (Opsional)</label>
                <select wire:model="parentClassId" class="w-full bg-slate-50 border border-slate-200 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors">
                    <option value="">-- Akar Klasifikasi (Utama) --</option>
                    @foreach($allClassifications as $c)
                        @if($c->id !== $classId)
                            <option value="{{ $c->id }}">{{ $c->code }} - {{ $c->subject }}</option>
                        @endif
                    @endforeach
                </select>
                @error('parentClassId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            
            <hr class="border-slate-200">
            <h4 class="text-sm font-bold text-slate-800">Penetapan Jadwal Retensi Arsip (JRA)</h4>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Masa Aktif (Tahun)</label>
                    <input wire:model="activeYears" type="number" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: 2" min="0">
                    @error('activeYears') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Masa Inaktif (Tahun)</label>
                    <input wire:model="inactiveYears" type="number" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: 5" min="0">
                    @error('inactiveYears') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Keterangan Nasib Akhir</label>
                <select wire:model="outcome" class="w-full bg-slate-50 border border-slate-200 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors">
                    <option value="musnah">Musnah (Dihancurkan setelah JRA habis)</option>
                    <option value="permanen">Permanen (Disimpan selamanya sebagai arsip statis)</option>
                    <option value="dinilai_kembali">Dinilai Kembali (Tinjauan manual di akhir JRA)</option>
                </select>
                @error('outcome') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
        </form>
        
        <x-slot:footer>
            <x-ui.button variant="secondary" type="button" @click="$dispatch('close-modal', { id: 'slide-classification' })">Batal</x-ui.button>
            <x-ui.button variant="primary" form="form-classification" type="submit">Simpan Klasifikasi</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- Slide-over: Tambah Jenis Dokumen -->
    <x-ui.modal id="slide-doctype" title="{{ $typeId ? 'Edit Jenis Dokumen' : 'Tambah Jenis Dokumen Baru' }}">
        <form wire:submit="saveDocType" id="form-doctype" class="space-y-5">
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Nama Jenis Dokumen <span class="text-red-500">*</span></label>
                <input wire:model="typeName" type="text" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: Surat Edaran" required>
                @error('typeName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Sifat Kerahasiaan Default <span class="text-red-500">*</span></label>
                <select wire:model="typeSifat" class="w-full bg-slate-50 border border-slate-200 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" required>
                    <option value="biasa">Biasa (Terbuka untuk Umum/Internal)</option>
                    <option value="penting">Penting (Terbatas Internal Terkait)</option>
                    <option value="rahasia">Rahasia (Eksklusif / C-Level Only)</option>
                </select>
                @error('typeSifat') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Struktur Penomoran Otomatis</label>
                <input wire:model="typeNumbering" type="text" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: [Urutan]/SE/[KodeUnit]/[Tahun]">
                <p class="text-[11px] text-slate-500 mt-1.5">Variabel tersedia: <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">[Urutan]</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">[KodeUnit]</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">[BulanRomawi]</code>, <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-700">[Tahun]</code>.</p>
                @error('typeNumbering') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            
            @if($typeNumbering)
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1">Preview Penomoran:</p>
                    <p class="font-mono text-xs opacity-90">
                        {{ str_replace(['[Urutan]', '[KodeUnit]', '[BulanRomawi]', '[Tahun]'], ['001', 'IT-01', 'X', date('Y')], $typeNumbering) }}
                    </p>
                </div>
            </div>
            @endif
        </form>
        
        <x-slot:footer>
            <x-ui.button variant="secondary" type="button" @click="$dispatch('close-modal', { id: 'slide-doctype' })">Batal</x-ui.button>
            <x-ui.button variant="primary" form="form-doctype" type="submit">Simpan Jenis Dokumen</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>