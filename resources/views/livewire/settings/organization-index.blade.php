<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 tracking-tight">Master Data Organisasi</h2>
            <p class="text-sm text-slate-500 mt-1">Kelola struktur hierarki Unit Kerja / Departemen dan Jabatan / Posisi pengguna.</p>
        </div>
        <div class="shrink-0">
            @if($activeTab === 'unit')
                <x-ui.button variant="primary" wire:click="createUnit">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Unit Kerja
                </x-ui.button>
            @else
                <x-ui.button variant="primary" wire:click="createPosition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Jabatan
                </x-ui.button>
            @endif
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-[var(--color-border)]">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button wire:click="setTab('unit')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'unit' ? 'border-[var(--color-accent)] text-[var(--color-primary)]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                Unit Kerja / Departemen
            </button>
            <button wire:click="setTab('position')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'position' ? 'border-[var(--color-accent)] text-[var(--color-primary)]' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                Jabatan / Posisi
            </button>
        </nav>
    </div>

    <!-- Tab Content: Unit Kerja -->
    @if($activeTab === 'unit')
    <div class="bg-white border border-[var(--color-border)] rounded-[var(--radius-lg)] shadow-xs overflow-hidden flex flex-col">
        <!-- Toolbar -->
        <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="searchUnit" class="w-full bg-white border border-slate-200 text-sm rounded-lg pl-9 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)] transition-colors" placeholder="Cari kode atau nama unit...">
            </div>

            <!-- Segmented Pill Filter -->
            <div class="inline-flex p-1 bg-slate-200/70 rounded-xl text-xs font-semibold self-start sm:self-auto">
                <button type="button" wire:click="setUnitTypeFilter('all')"
                        class="px-3 py-1.5 rounded-lg transition-all {{ $unitTypeFilter === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua Unit
                </button>
                <button type="button" wire:click="setUnitTypeFilter('division')"
                        class="px-3 py-1.5 rounded-lg transition-all {{ $unitTypeFilter === 'division' ? 'bg-white text-[var(--color-primary)] shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                    Divisi
                </button>
                <button type="button" wire:click="setUnitTypeFilter('department')"
                        class="px-3 py-1.5 rounded-lg transition-all {{ $unitTypeFilter === 'department' ? 'bg-white text-[var(--color-primary)] shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}">
                    Departemen
                </button>
            </div>
        </div>
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-xs font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                        <th class="px-5 py-3 w-1/6">Kode Unit</th>
                        <th class="px-5 py-3 w-1/3">Nama Unit Kerja</th>
                        <th class="px-5 py-3 w-1/6">Tipe</th>
                        <th class="px-5 py-3 w-1/4">Induk Divisi</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--color-border)]">
                    @forelse($units as $unit)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4"><span class="font-mono text-slate-700 font-semibold bg-slate-100 px-2 py-0.5 rounded text-xs">{{ $unit->code }}</span></td>
                        <td class="px-5 py-4 font-bold text-slate-800">{{ $unit->name }}</td>
                        <td class="px-5 py-4">
                            @if(($unit->type ?? 'division') === 'division')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                    Divisi
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200/60">
                                    Departemen
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            @if($unit->parent)
                                <div class="font-medium text-slate-800">{{ $unit->parent->name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $unit->parent->code }}</div>
                            @else
                                <span class="text-slate-400 italic text-xs">- (Induk Utama)</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($unit->status === 'active')
                                <x-ui.badge status="approved">Aktif</x-ui.badge>
                            @else
                                <x-ui.badge status="rejected">Nonaktif</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right space-x-1">
                            <x-ui.button wire:click="editUnit({{ $unit->id }})" variant="subtle" class="!px-2 !py-1 text-xs">Edit</x-ui.button>
                            <x-ui.button wire:click="deleteUnit({{ $unit->id }})" wire:confirm="Apakah Anda yakin ingin menghapus unit kerja ini?" variant="subtle" class="!px-2 !py-1 text-xs !text-red-600 hover:!bg-red-50">Hapus</x-ui.button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-6 text-center text-slate-500">
                            Tidak ditemukan data unit kerja{{ $unitTypeFilter !== 'all' ? ' bertipe ' . ($unitTypeFilter === 'division' ? 'Divisi' : 'Departemen') : '' }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-[var(--color-border)] bg-slate-50">
            {{ $units->links('vendor.pagination.custom') }}
        </div>
    </div>
    @endif

    <!-- Tab Content: Jabatan -->
    @if($activeTab === 'position')
    <div class="bg-white border border-[var(--color-border)] rounded-[var(--radius-lg)] shadow-xs overflow-hidden flex flex-col">
        <!-- Toolbar -->
        <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="searchPosition" class="w-full bg-white border border-slate-200 text-sm rounded-lg pl-9 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)] transition-colors" placeholder="Cari nama jabatan...">
            </div>
        </div>
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-xs font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                        <th class="px-5 py-3 w-1/3">Nama Jabatan</th>
                        <th class="px-5 py-3 w-1/5">Tingkat Level</th>
                        <th class="px-5 py-3 w-1/3">Otorisasi Bawaan</th>
                        <th class="px-5 py-3 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--color-border)]">
                    @forelse($positions as $position)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 font-bold text-slate-800">{{ $position->name }}</td>
                        <td class="px-5 py-4 text-slate-600">
                            @if($position->level == 1)
                                <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded text-xs font-bold border border-amber-200">Level 1 (BOD)</span>
                            @elseif($position->level == 2)
                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-bold border border-blue-200">Level 2 (Manajerial)</span>
                            @elseif($position->level == 3)
                                <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded text-xs font-bold border border-emerald-200">Level 3 (Supervisor)</span>
                            @else
                                <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-xs font-bold border border-slate-200">Level {{ $position->level }} (Staff)</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-500 text-xs mt-3">
                            {{ $position->authority_config ?: '-' }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <x-ui.button wire:click="editPosition({{ $position->id }})" variant="subtle" class="!px-2 !py-1 text-xs">Edit</x-ui.button>
                            <x-ui.button wire:click="deletePosition({{ $position->id }})" wire:confirm="Apakah Anda yakin ingin menghapus jabatan ini?" variant="subtle" class="!px-2 !py-1 text-xs !text-red-600 hover:!bg-red-50">Hapus</x-ui.button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-4 text-center text-slate-500">Belum ada data jabatan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-[var(--color-border)] bg-slate-50">
            {{ $positions->links('vendor.pagination.custom') }}
        </div>
    </div>
    @endif

    <!-- Slide-over: Tambah Unit Kerja -->
    <x-ui.modal id="slide-unit" title="{{ $unitId ? 'Edit Unit Kerja' : 'Tambah Unit Kerja Baru' }}">
        <form wire:submit="saveUnit" id="form-unit" class="space-y-5">
            <!-- Tipe Unit Kerja (Segmented Radio) -->
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Tipe Unit Kerja <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative flex items-center justify-center cursor-pointer rounded-lg border px-4 py-2.5 text-center transition-all duration-150 select-none {{ $unitType === 'division' ? 'border-[var(--color-primary)] bg-emerald-50/40 text-[var(--color-primary)] font-bold ring-1 ring-[var(--color-primary)] shadow-xs' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 font-medium' }}">
                        <input type="radio" wire:model.live="unitType" value="division" class="sr-only">
                        <span class="text-xs">Divisi (Induk)</span>
                    </label>
                    <label class="relative flex items-center justify-center cursor-pointer rounded-lg border px-4 py-2.5 text-center transition-all duration-150 select-none {{ $unitType === 'department' ? 'border-[var(--color-primary)] bg-emerald-50/40 text-[var(--color-primary)] font-bold ring-1 ring-[var(--color-primary)] shadow-xs' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 font-medium' }}">
                        <input type="radio" wire:model.live="unitType" value="department" class="sr-only">
                        <span class="text-xs">Departemen (Sub-Unit)</span>
                    </label>
                </div>
                @error('unitType') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Kode Unit <span class="text-red-500">*</span></label>
                <input type="text" wire:model="unitCode" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: IT atau IT-DEV">
                @error('unitCode') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Nama Unit Kerja <span class="text-red-500">*</span></label>
                <input type="text" wire:model="unitName" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: Divisi TI atau Departemen Aplikasi">
                @error('unitName') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            @if($unitType === 'department')
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Divisi Induk <span class="text-red-500">*</span></label>
                <select wire:model="parentUnitId" class="w-full bg-slate-50 border border-slate-200 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors">
                    <option value="">-- Pilih Divisi Induk --</option>
                    @foreach($divisionOptions as $div)
                        @if($div->id !== $unitId)
                            <option value="{{ $div->id }}">{{ $div->name }} ({{ $div->code }})</option>
                        @endif
                    @endforeach
                </select>
                @error('parentUnitId') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            @endif

            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Status</label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="unitStatus" true-value="active" false-value="inactive" class="w-4 h-4 rounded border-slate-300 text-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                    <span class="text-sm font-medium text-slate-700">Aktif beroperasi</span>
                </label>
                @error('unitStatus') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </form>

        <x-slot:footer>
            <x-ui.button variant="secondary" type="button" @click="$dispatch('close-modal', { id: 'slide-unit' })">Batal</x-ui.button>
            <x-ui.button variant="primary" type="submit" form="form-unit">Simpan Unit</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <!-- Slide-over: Tambah Jabatan -->
    <x-ui.modal id="slide-position" title="{{ $positionId ? 'Edit Jabatan / Posisi' : 'Tambah Jabatan / Posisi Baru' }}">
        <form wire:submit="savePosition" id="form-position" class="space-y-5">
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Nama Jabatan <span class="text-red-500">*</span></label>
                <input type="text" wire:model="posName" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: Kepala Divisi IT">
                @error('posName') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Tingkat Level Jabatan <span class="text-red-500">*</span></label>
                <select wire:model="posLevel" class="w-full bg-slate-50 border border-slate-200 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors">
                    <option value="">-- Pilih Level Hierarki --</option>
                    <option value="1">Level 1 (Board of Directors)</option>
                    <option value="2">Level 2 (Manajerial / Kadiv)</option>
                    <option value="3">Level 3 (Supervisor / Kabag)</option>
                    <option value="4">Level 4 (Staf / Pelaksana)</option>
                </select>
                <p class="text-xs text-slate-500 mt-1.5">Level jabatan menentukan urutan Approval / Disposisi pada sistem.</p>
                @error('posLevel') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Otorisasi Bawaan Khusus</label>
                <textarea wire:model="posAuthority" rows="3" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Cth: Memiliki hak approval tanpa dibatasi hierarki divisi..."></textarea>
                @error('posAuthority') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </form>

        <x-slot:footer>
            <x-ui.button variant="secondary" type="button" @click="$dispatch('close-modal', { id: 'slide-position' })">Batal</x-ui.button>
            <x-ui.button variant="primary" type="submit" form="form-position">Simpan Jabatan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
