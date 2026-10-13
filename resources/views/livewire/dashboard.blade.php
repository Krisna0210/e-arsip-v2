<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Dashboard Kearsipan</h1>
            <p class="text-sm text-[var(--color-text-secondary)] mt-1">Ringkasan statistik repositori dokumen digital perusahaan Anda.</p>
        </div>
        {{-- <div class="flex items-center gap-3">
            <x-ui.button variant="secondary" class="bg-white">
                <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export Laporan
            </x-ui.button>
        </div> --}}
    </div>

    <!-- Bento Grid Stats (Conditional on Role Permissions) -->
    @if($visibleCardsCount > 0)
    <div class="grid {{ $gridColsClass }} gap-4">
        <!-- Card 1: Total Surat Masuk -->
        @if($canViewIncoming)
        <x-ui.card class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white border-0 shadow-lg shadow-emerald-500/20 relative overflow-hidden group">
            <div class="absolute right-0 top-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-500"></div>
            <div class="relative z-10 p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium mb-1">Total Surat Masuk</p>
                        <h3 class="text-3xl font-bold tracking-tight">{{ $countIncoming }} <span class="text-lg font-normal text-emerald-100">Arsip</span></h3>
                    </div>
                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                </div>
            </div>
        </x-ui.card>
        @endif

        <!-- Card 2: Total Surat Keluar -->
        @if($canViewOutgoing)
        <x-ui.card class="p-5 border border-slate-200 bg-white hover:border-blue-300 transition-colors shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-500 text-sm font-medium mb-1">Total Surat Keluar</p>
                    <h3 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $countOutgoing }} <span class="text-sm font-normal text-slate-500">Arsip</span></h3>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </div>
            </div>
        </x-ui.card>
        @endif

        <!-- Card 3: Total Memo Internal -->
        @if($canViewInternal)
        <x-ui.card class="p-5 border border-slate-200 bg-white hover:border-purple-300 transition-colors shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-500 text-sm font-medium mb-1">Total Memo Internal</p>
                    <h3 class="text-2xl font-bold text-slate-800 tracking-tight">{{ $countInternal }} <span class="text-sm font-normal text-slate-500">Arsip</span></h3>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                </div>
            </div>
        </x-ui.card>
        @endif

        <!-- Card 4: Antrean Menunggu Upload / Scan -->
        @if($canViewWaiting)
        <x-ui.card class="p-5 border border-amber-200 bg-amber-50 hover:bg-amber-100 transition-colors shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-amber-800 text-sm font-bold mb-1">Menunggu Upload</p>
                    <h3 class="text-2xl font-bold text-amber-900 tracking-tight">{{ $countWaiting }} <span class="text-sm font-normal text-amber-700">Berkas</span></h3>
                </div>
                <div class="p-2 bg-amber-200 rounded-lg text-amber-800">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </x-ui.card>
        @endif

        <!-- Card 5: Peringatan Retensi JRA -->
        @if($canViewRetention)
        <x-ui.card class="p-5 border border-rose-200 bg-rose-50 hover:bg-rose-100 transition-colors shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-rose-600 text-sm font-bold mb-1">Peringatan Retensi</p>
                    <h3 class="text-2xl font-bold text-rose-700 tracking-tight">{{ $countRetention }} <span class="text-sm font-normal text-rose-600">Arsip</span></h3>
                    <p class="text-[11px] text-rose-500 font-medium mt-1">Kadaluwarsa dlm 30 hari</p>
                </div>
                <div class="p-2 bg-rose-200 rounded-lg text-rose-700">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
        </x-ui.card>
        @endif
    </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Kolom Kiri: Tabel Monitoring Arsip Dokumen (Makan 2 kolom) -->
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card class="overflow-hidden shadow-sm border-slate-200">
                <div class="px-5 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
                    <div class="flex items-center">
                        <h2 class="text-lg font-bold text-slate-800 tracking-tight">Monitoring Arsip Dokumen</h2>
                        @if(!$isSuperAdmin)
                            <span class="ml-2.5 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">Naskah Surat Anda</span>
                        @else
                            <span class="ml-2.5 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">Seluruh Naskah Sistem</span>
                        @endif
                    </div>
                    {{-- <a href="{{ route('registry.outgoing.index') }}" class="text-xs font-semibold text-[var(--color-primary)] hover:text-emerald-700 transition-colors">Buka Arsip Dokumen &rarr;</a> --}}
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-[11px] font-bold text-slate-500 bg-slate-50 uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th scope="col" class="py-2.5 pl-5 pr-3 w-1/4">Nomor & Jenis Dokumen</th>
                                <th scope="col" class="px-3 py-2.5 w-[40%]">Perihal & Tujuan</th>
                                <th scope="col" class="px-3 py-2.5 text-center">Tanggal Nomor Surat</th>
                                <th scope="col" class="px-4 py-2.5 text-center">Status Monitoring</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($monitoringDocuments as $doc)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3.5 pl-5 pr-3 align-top">
                                    <div class="font-mono text-[13px] font-bold text-slate-800 dark:text-slate-200">{{ $doc->number ?? 'Tanpa Nomor' }}</div>
                                    <div class="mt-1">
                                        @if($doc->type === 'incoming')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">Surat Masuk</span>
                                        @elseif($doc->type === 'outgoing')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Surat Keluar</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">Memo Internal</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3.5 align-top">
                                    <div x-data="{ expanded: false }">
                                        <div 
                                            class="font-medium text-slate-800 leading-snug transition-all"
                                            :class="expanded ? 'whitespace-normal break-words' : 'line-clamp-2'"
                                            title="{{ $doc->subject }}"
                                        >
                                            {{ $doc->subject }}
                                        </div>
                                        @if(mb_strlen($doc->subject ?? '') > 75)
                                            <button 
                                                type="button" 
                                                @click="expanded = !expanded" 
                                                class="text-[11px] font-bold text-[var(--color-primary)] hover:text-emerald-700 hover:underline mt-1 inline-flex items-center gap-0.5 focus:outline-none cursor-pointer"
                                            >
                                                <span x-text="expanded ? 'Show Less ▴' : 'Show More ▾'"></span>
                                            </button>
                                        @endif
                                    </div>
                                    @if($doc->recipient_name)
                                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                            <span class="text-slate-400">Kepada:</span>
                                            <span class="font-medium text-slate-700">{{ $doc->recipient_name }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5 align-top text-center whitespace-nowrap">
                                    <div class="text-xs font-semibold text-slate-700">{{ $doc->created_at->format('d M Y') }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $doc->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-4 py-3.5 align-top text-center">
                                    @if($doc->status === 'waiting_for_file')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs" title="Fisik surat belum di-upload Tapersip">
                                        Menunggu Upload
                                        </span>
                                    @elseif($doc->status === 'archived')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-600 text-white shadow-2xs" title="Fisik surat sudah di-scan dan diarsipkan">
                                           Selesai Diarsipkan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                            {{ ucfirst($doc->status) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-slate-500">
                                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm font-medium text-slate-700">Belum ada naskah surat yang Anda daftarkan.</p>
                                    <p class="text-xs text-slate-400 mt-1">Registrasikan surat baru melalui menu Input Surat untuk mulai memantau status fisik arsip.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($monitoringDocuments->hasPages())
                    <div class="px-5 py-3 border-t border-slate-200 bg-white">
                        {{ $monitoringDocuments->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </x-ui.card>
        </div>

        <!-- Kolom Kanan: Log Aktivitas (Makan 1 kolom) -->
        <div class="space-y-6">
            <x-ui.card class="shadow-sm border-slate-200 flex flex-col h-full">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-slate-800 tracking-tight">Log Aktivitas Sistem</h2>
                </div>
                <div class="p-5 flex-1">
                    <div class="relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-200 before:to-transparent">
                        @forelse($recentActivities as $activity)
                            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active mb-6 last:mb-0">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full border border-white bg-slate-100 text-slate-500 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] p-4 rounded border border-slate-200 bg-white shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="font-bold text-slate-900 text-sm">
                                            {{ $activity->user->name ?? 'Sistem' }}
                                        </div>
                                        <time class="font-mono text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</time>
                                    </div>
                                    <div class="text-sm text-slate-600 line-clamp-2">
                                        {{ $activity->action }} {{ $activity->document ? 'Dokumen #' . $activity->document->id : '' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-slate-500 py-4 text-sm">Belum ada aktivitas tercatat.</div>
                        @endforelse
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 text-center">
                    <a href="{{ route('settings.audit-trail') }}" class="text-sm font-medium text-slate-500 hover:text-[var(--color-primary)] transition-colors">Lihat Semua Aktivitas</a>
                </div>
            </x-ui.card>
        </div>

    </div>
</div>
