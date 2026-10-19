<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 tracking-tight">Audit Trail & Log Aktivitas</h2>
            <p class="text-sm text-slate-500 mt-1">Pantau seluruh aktivitas pengguna, perubahan data, dan akses dokumen dalam sistem.</p>
        </div>
    </div>

    <!-- Data Table Logs -->
    <div class="bg-white border border-[var(--color-border)] rounded-xl shadow-xs overflow-hidden flex flex-col">
        <!-- Table Toolbar -->
        <div class="px-5 py-4 border-b border-[var(--color-border)] bg-slate-50 flex flex-col lg:flex-row gap-3 items-center justify-between">
            <!-- Search -->
            <div class="relative w-full lg:max-w-xs">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <input type="text" wire:model.live="search" class="w-full bg-white border border-slate-200 text-sm rounded-lg pl-9 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="Cari aktivitas atau nama...">
            </div>
            
            <!-- Filter -->
            <div class="flex flex-col sm:flex-row items-center gap-2 w-full lg:w-auto">
                <select wire:model.live="filterAction" class="w-full sm:w-auto bg-white border border-slate-200 text-sm text-slate-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Semua Jenis Aktivitas</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input type="date" wire:model.live="filterDateStart" class="w-full sm:w-auto bg-white border border-slate-200 text-sm text-slate-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Mulai Tanggal">
                    <span class="text-slate-400 text-sm">-</span>
                    <input type="date" wire:model.live="filterDateEnd" class="w-full sm:w-auto bg-white border border-slate-200 text-sm text-slate-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Sampai Tanggal">
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-[var(--color-background)] border-b border-[var(--color-border)] text-xs font-semibold text-[var(--color-text-secondary)] uppercase tracking-wider">
                        <th class="px-5 py-3 w-48">Waktu (Timestamp)</th>
                        <th class="px-5 py-3 w-1/5">Pengguna</th>
                        <th class="px-5 py-3 w-40">Aktivitas</th>
                        <th class="px-5 py-3 w-1/4">Terkait Dokumen</th>
                        <th class="px-5 py-3">Detail Referensi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-[var(--color-border)]">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 align-top">
                            <div class="font-medium text-slate-700">{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-5 py-4 align-top">
                            <div class="flex items-center gap-2">
                                @if($log->user)
                                    @php
                                        $initials = Str::upper(substr($log->user->name, 0, 2));
                                        $colors = ['emerald', 'blue', 'slate', 'indigo', 'rose', 'amber'];
                                        $color = $colors[$log->user->id % count($colors)];
                                    @endphp
                                    <div class="h-7 w-7 rounded-full bg-{{ $color }}-100 flex items-center justify-center text-{{ $color }}-700 font-bold border border-{{ $color }}-200 shrink-0 text-xs">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-800">{{ $log->user->name }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $log->user->username ?? 'Sistem' }}</div>
                                    </div>
                                @else
                                    <div class="h-7 w-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold border border-slate-200 shrink-0 text-xs">
                                        SY
                                    </div>
                                    <span class="text-slate-500 italic">Sistem (Otomatis)</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 align-top">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border bg-slate-50 text-slate-700 border-slate-200 whitespace-nowrap">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-5 py-4 align-top">
                            @if($log->document)
                                <div class="font-medium text-emerald-700 line-clamp-1" title="{{ $log->document->subject }}">{{ $log->document->subject }}</div>
                                <div class="text-xs text-slate-500 mt-0.5 font-mono">{{ $log->document->document_number ?? '-' }}</div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 align-top">
                            @if($log->metadata)
                                @php
                                    $metadata = $log->metadata;
                                    if (is_array($metadata) && isset($metadata['type']) && $metadata['type'] === 'internal') {
                                        $metadata['type'] = $log->document?->documentType?->name ?? 'Surat Khusus';
                                    }
                                @endphp
                                <div class="text-xs bg-slate-50 border border-slate-100 p-2 rounded text-slate-600 font-mono overflow-x-auto max-w-xs">
                                    {{ json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                </div>
                            @elseif($log->before_value || $log->after_value)
                                <div class="text-xs space-y-1">
                                    @if($log->before_value)
                                        <div class="text-rose-600"><span class="font-semibold">Before:</span> {{ Str::limit(json_encode($log->before_value), 30) }}</div>
                                    @endif
                                    @if($log->after_value)
                                        <div class="text-emerald-600"><span class="font-semibold">After:</span> {{ Str::limit(json_encode($log->after_value), 30) }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-500">Tidak ada log aktivitas yang ditemukan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-5 py-3 border-t border-[var(--color-border)] bg-white text-xs text-slate-500">
            {{ $logs->links() }}
        </div>
    </div>
</div>
