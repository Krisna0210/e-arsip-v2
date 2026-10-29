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
                {{-- <select wire:model.live="filterAction" class="w-full sm:w-auto bg-white border border-slate-200 text-sm text-slate-600 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="">Semua Jenis Aktivitas</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select> --}}
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
                        <th class="px-5 py-3 w-44">Waktu (Timestamp)</th>
                        <th class="px-5 py-3 w-56">Pengguna</th>
                        <th class="px-5 py-3 w-72">Aktivitas</th>
                        <th class="px-5 py-3 w-72">Identitas Dokumen</th>
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
                            @php
                                $actionParts = explode(':', $log->action, 2);
                                $actionTitle = trim($actionParts[0]);
                                $docIdentifier = isset($actionParts[1]) ? trim($actionParts[1]) : null;

                                $badgeClass = match(true) {
                                    str_contains($actionTitle, 'Koreksi') => 'bg-blue-50 text-blue-800 border-blue-200',
                                    str_contains($actionTitle, 'Pembatalan') || str_contains($actionTitle, 'Void') => 'bg-rose-50 text-rose-800 border-rose-200',
                                    str_contains($actionTitle, 'Revisi') => 'bg-purple-50 text-purple-800 border-purple-200',
                                    str_contains($actionTitle, 'Pinjam') || str_contains($actionTitle, 'Sirkulasi') => 'bg-amber-50 text-amber-800 border-amber-200',
                                    str_contains($actionTitle, 'Arsip') || str_contains($actionTitle, 'Final') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200',
                                };

                                $reason = $log->metadata['reason'] ?? null;
                            @endphp

                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeClass }} whitespace-normal leading-relaxed">
                                {{ $actionTitle }}
                            </span>

                            {{-- @if($docIdentifier)
                                <div class="font-mono text-xs font-semibold text-slate-700 mt-1.5 break-all">
                                    {{ $docIdentifier }}
                                </div>
                            @endif --}}

                            @if($reason)
                                <div class="mt-2 p-2 bg-amber-50 border border-amber-200/80 rounded-md text-xs text-amber-900 leading-relaxed">
                                    <span class="font-semibold text-amber-950">Alasan:</span> {{ $reason }}
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4 align-top">
                            @if($log->document)
                                <div class="text-xs text-slate-500 mt-0.5 font-mono">{{ $log->document->number ?: '-' }}</div>
                                <div class="font-medium text-emerald-700 line-clamp-2" title="{{ $log->document->subject }}">{{ $log->document->subject }}</div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 align-top">
                            @php
                                $metadata = $log->metadata ?? [];
                                $before = $metadata['before'] ?? [];
                                $after = $metadata['after'] ?? [];
                                $diffs = [];

                                $fieldLabels = [
                                    'number' => 'Nomor Naskah',
                                    'classification_id' => 'Klasifikasi',
                                    'document_date' => 'Tanggal Dokumen',
                                    'received_date' => 'Tanggal Diterima',
                                    'subject' => 'Perihal',
                                    'sifat' => 'Sifat Dokumen',
                                    'recipient' => 'Penerima',
                                    'recipient_name' => 'Penerima',
                                    'recipient_id' => 'Unit Penerima',
                                    'unit_id' => 'Unit Penerima',
                                    'sender' => 'Instansi Pengirim',
                                ];

                                if (is_array($before) && is_array($after) && !empty($after)) {
                                    foreach ($after as $key => $newVal) {
                                        $oldVal = $before[$key] ?? null;
                                        if ($oldVal != $newVal) {
                                            if ($key === 'classification_id') {
                                                $oldDisplay = $classificationsMap[$oldVal] ?? ($oldVal ?: '-');
                                                $newDisplay = $classificationsMap[$newVal] ?? ($newVal ?: '-');
                                            } elseif (in_array($key, ['recipient_id', 'unit_id', 'recipient']) && is_numeric($oldVal) && is_numeric($newVal)) {
                                                $oldDisplay = $unitsMap[$oldVal] ?? ($oldVal ?: '-');
                                                $newDisplay = $unitsMap[$newVal] ?? ($newVal ?: '-');
                                            } elseif ($key === 'document_date' || $key === 'received_date') {
                                                $oldDisplay = $oldVal ? date('d/m/Y', strtotime($oldVal)) : '-';
                                                $newDisplay = $newVal ? date('d/m/Y', strtotime($newVal)) : '-';
                                            } elseif ($key === 'sifat') {
                                                $oldDisplay = ucfirst($oldVal ?: '-');
                                                $newDisplay = ucfirst($newVal ?: '-');
                                            } else {
                                                $oldDisplay = (string)($oldVal ?: '-');
                                                $newDisplay = (string)($newVal ?: '-');
                                            }

                                            $diffs[] = [
                                                'label' => $fieldLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                                                'old' => $oldDisplay,
                                                'new' => $newDisplay,
                                            ];
                                        }
                                    }
                                }
                            @endphp

                            @if(!empty($diffs))
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 space-y-1.5 text-xs">
                                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mb-1">Perubahan Metadata:</div>
                                    @foreach($diffs as $d)
                                        <div class="border-b border-slate-200/60 pb-1 last:border-b-0 last:pb-0">
                                            <span class="font-medium text-slate-600">{{ $d['label'] }}:</span>
                                            <div class="font-mono mt-0.5 flex items-center gap-1.5 flex-wrap">
                                                <span class="text-slate-400 line-through">{{ Str::limit($d['old'], 40) }}</span>
                                                <span class="text-slate-400">&rarr;</span>
                                                <span class="text-emerald-700 font-semibold">{{ Str::limit($d['new'], 40) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(isset($metadata['previous_version']) && isset($metadata['new_version']))
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-xs space-y-1">
                                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Revisi Berkas Scan:</div>
                                    <div class="font-mono flex items-center gap-1.5">
                                        <span class="text-slate-400">v{{ $metadata['previous_version'] }}</span>
                                        <span class="text-slate-400">&rarr;</span>
                                        <span class="text-emerald-700 font-bold">v{{ $metadata['new_version'] }}</span>
                                    </div>
                                    @if(isset($metadata['file_path']))
                                        <div class="text-[11px] text-slate-500 font-mono mt-1 break-all">{{ basename($metadata['file_path']) }}</div>
                                    @endif
                                </div>
                            @elseif(isset($metadata['cancelled_by']))
                                <div class="bg-rose-50/70 border border-rose-200 rounded-lg p-2.5 text-xs">
                                    <span class="font-semibold text-rose-800">Status Nomor:</span>
                                    <span class="text-rose-700 font-mono font-bold ml-1">Dibatalkan (Void)</span>
                                </div>
                            @elseif(!empty($metadata))
                                @php
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
            {{ $logs->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>
