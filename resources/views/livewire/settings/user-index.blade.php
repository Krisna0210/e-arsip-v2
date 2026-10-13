<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 tracking-tight">Manajemen Akun Pengguna</h2>
            <p class="text-sm text-slate-500 mt-1">Kelola data karyawan, pendaftaran akses sistem, dan otorisasi profil.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 shrink-0">
            <a href="{{ route('settings.roles.index') }}" class="w-full sm:w-auto bg-white border text-slate-700 border-slate-300 hover:bg-slate-50 font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Manajemen Roles
            </a>
            <button wire:click="create" class="w-full sm:w-auto bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-hover)] font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Tambah Pengguna
            </button>
        </div>
    </div>

    <!-- Data Table Users -->
    <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden flex flex-col">
        <!-- Table Toolbar -->
        <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <!-- Search -->
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" wire:model.live="search" class="w-full bg-white border border-slate-200 text-sm rounded-lg pl-9 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="Cari nama, NIP, atau unit...">
            </div>
            <!-- Filter -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select wire:model.live="filterUnit" class="w-full sm:w-auto bg-white border border-slate-200 text-sm text-slate-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Semua Unit Kerja</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterStatus" class="w-full sm:w-auto bg-white border border-slate-200 text-sm text-slate-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="active">Status Aktif</option>
                    <option value="inactive">Status Non-Aktif</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-xs font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                        <th class="px-5 py-3 w-1/4">Nama Lengkap</th>
                        <th class="px-5 py-3 w-1/4">Unit Kerja</th>
                        <th class="px-5 py-3 w-1/5">Jabatan / Posisi</th>
                        <th class="px-5 py-3 w-1/6">Role Sistem</th>
                        <th class="px-5 py-3 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--color-border)]">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 align-middle">
                            <div class="flex items-center gap-3">
                                @php
                                    $initials = Str::upper(substr($user->name, 0, 2));
                                    $colors = ['emerald', 'blue', 'slate', 'indigo', 'rose', 'amber'];
                                    $color = $colors[$user->id % count($colors)];
                                @endphp
                                <div class="h-9 w-9 rounded-full bg-{{ $color }}-100 flex items-center justify-center text-{{ $color }}-700 font-bold border border-{{ $color }}-200 shrink-0">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <div class="font-medium text-slate-700">{{ $user->unit->name ?? '-' }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $user->unit->code ?? '' }}</div>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <div class="text-slate-700">{{ $user->position->name ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                                {{ $user->role->name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle text-right">
                            <div class="flex items-center justify-end gap-2">
                                <x-ui.button wire:click="edit({{ $user->id }})" variant="subtle" class="!px-2 !py-1 text-xs">Edit Profil</x-ui.button>
                                <x-ui.button wire:click="delete({{ $user->id }})" wire:confirm="Yakin ingin menghapus?" variant="subtle" class="!px-2 !py-1 text-xs text-red-600 hover:bg-red-50 hover:text-red-700">Hapus</x-ui.button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-500">Tidak ada data pengguna yang ditemukan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-5 py-3 border-t border-[var(--color-border)] bg-white text-xs text-slate-500">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Modal: Tambah Pengguna -->
    <x-ui.modal id="slide-user" title="{{ $userId ? 'Edit Pengguna' : 'Registrasi Pengguna Baru' }}" width="max-w-lg">
        <form wire:submit="save" id="userForm" class="space-y-6">

            <!-- Section 1: Data Diri -->
            <div>
                <h4 class="text-sm font-bold text-slate-800 mb-4 border-b border-slate-200 pb-2">Informasi Akun Dasar</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Masukkan nama lengkap">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Username <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="username" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 font-mono focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="Nomor Induk Pegawai">
                            @error('username') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Email Karyawan</label>
                            <input type="email" wire:model="email" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="email@perusahaan.com">
                            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Password <span class="text-red-500">*</span></label>
                        <input type="password" wire:model="password" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors" placeholder="••••••••">
                        @if($userId)
                            <p class="text-[11px] text-slate-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
                        @else
                            <p class="text-[11px] text-slate-500 mt-1">Gunakan password standar untuk karyawan baru. Karyawan akan diminta mengubahnya saat login pertama kali.</p>
                        @endif
                        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Otorisasi & Posisi -->
            <div>
                <h4 class="text-sm font-bold text-slate-800 mb-4 border-b border-slate-200 pb-2">Posisi & Otorisasi Sistem</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Unit Kerja / Departemen <span class="text-red-500">*</span></label>
                        <select wire:model="unit_id" class="w-full bg-slate-50 border border-slate-200 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors">
                            <option value="">-- Pilih Unit Kerja --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->code }})</option>
                            @endforeach
                        </select>
                        @error('unit_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider mb-1.5">Jabatan / Posisi Organisasi <span class="text-red-500">*</span></label>
                        <select wire:model="position_id" class="w-full bg-slate-50 border border-slate-200 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:bg-white transition-colors">
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                        @error('position_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                        <label class="block text-[0.75rem] font-bold text-emerald-800 uppercase tracking-wider mb-1.5">Tetapkan Role Akses E-Arsip</label>
                        <select wire:model="role_id" class="w-full bg-white border border-emerald-300 text-sm text-slate-700 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors shadow-sm">
                            <option value="">-- Pilih Role Sistem --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-emerald-600 mt-1.5">Role ini mengatur secara spesifik batas akses rahasia dan menu navigasi pengguna.</p>
                        @error('role_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

        </form>

        <x-slot:footer>
            <x-ui.button variant="secondary" type="button" @click="$dispatch('close-modal', { id: 'slide-user' })">Batalkan</x-ui.button>
            <x-ui.button variant="primary" form="userForm" type="submit" wire:target="save" wire:loading.attr="disabled">Simpan & Daftarkan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
