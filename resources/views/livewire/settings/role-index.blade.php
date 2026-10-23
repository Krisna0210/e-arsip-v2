<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="mb-4">
        <a href="{{ route('settings.users') }}" class="inline-flex items-center text-xs font-medium text-slate-500 hover:text-emerald-700 transition-colors">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Manajemen Pengguna
        </a>
    </div>

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 tracking-tight">Manajemen Role & Otorisasi Pengguna</h2>
            <p class="text-sm text-slate-500 mt-1">Kelola pembagian peran, hak akses matriks, dan batas kerahasiaan dokumen seluruh karyawan.</p>
        </div>
        <div class="shrink-0">
            <a href="{{ route('settings.roles.create') }}" class="w-full sm:w-auto bg-[var(--color-primary)] text-white hover:bg-[var(--color-primary-hover)] font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Role Baru
            </a>
        </div>
    </div>

    <!-- Data Table Role -->
    <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden flex flex-col">
        <!-- Table Toolbar -->
        <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex flex-col sm:flex-row gap-3 items-center justify-between">
            <!-- Search -->
            <div class="relative w-full sm:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input wire:model.live="search" type="text" class="w-full bg-white border border-slate-200 text-sm rounded-lg pl-9 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="Cari nama role...">
            </div>
            <!-- Filter -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select wire:model.live="filterStatus" class="w-full sm:w-auto bg-white border border-slate-200 text-sm text-slate-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif Saja</option>
                    <option value="inactive">Non-aktif</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-xs font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                        <th class="px-5 py-3 w-1/3">Nama Role</th>
                        <th class="px-5 py-3 w-1/4 items-center">Jumlah Pengguna</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Opsi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--color-border)]">
                    @forelse ($roles as $role)
                    <tr class="hover:bg-slate-50 transition-colors {{ $role->status === 'inactive' ? 'opacity-75' : '' }}">
                        <td class="px-5 py-4 align-middle">
                            <div class="font-bold text-slate-{{ $role->status === 'active' ? '800' : '600' }}">{{ $role->name }}</div>
                            <div class="text-xs text-slate-500 mt-0.5 max-w-sm line-clamp-1">{{ $role->description ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            <span class="inline-flex items-center justify-center {{ $role->status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200' }} font-semibold border px-2 py-1 rounded text-xs">
                                {{ $role->users_count }} Akun Aktif
                            </span>
                        </td>
                        <td class="px-5 py-4 align-middle">
                            @if ($role->status === 'active')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-blue-50 text-blue-700 border-blue-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-slate-100 text-slate-600 border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
                                    Inaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 align-middle text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('settings.roles.edit', $role->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 hover:text-emerald-700 rounded-md text-xs font-medium transition-colors shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Atur Matriks
                                </a>
                                @if($role->users_count === 0)
                                    <button wire:click="deleteRole({{ $role->id }})" wire:confirm="Apakah Anda yakin ingin menghapus role ini?" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-300 text-red-600 hover:bg-red-50 hover:text-red-700 rounded-md text-xs font-medium transition-colors shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-8 text-center text-slate-500 text-sm">Tidak ada role ditemukan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-5 py-3 border-t border-[var(--color-border)] bg-white">
            {{ $roles->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>