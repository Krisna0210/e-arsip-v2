<div wire:poll.8s="checkLoanEvents"
     x-data="{
        open: false,
        toasts: [],
        playChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;

                // Nada 1 (Lembut & Elegan)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now); // D5
                gain1.gain.setValueAtTime(0.12, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.25);

                // Nada 2 (Harmoni Lonceng)
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, now + 0.1); // A5
                gain2.gain.setValueAtTime(0.15, now + 0.1);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.1);
                osc2.stop(now + 0.45);
            } catch (e) {
                console.warn('Web Audio chime not supported or blocked:', e);
            }
        },
        addToast(detail) {
            this.playChime();
            const id = Date.now() + Math.random();
            const toast = {
                id: id,
                title: detail.title || 'Pemberitahuan Tugas',
                body: detail.body || '',
                type: detail.type || 'loan_requested',
                url: detail.url || '{{ route('borrowings.index') }}',
                loan_code: detail.loan_code || '',
            };
            this.toasts.push(toast);

            // Otomatis tutup toast setelah 7 detik
            setTimeout(() => {
                this.removeToast(id);
            }, 7000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
     }"
     x-init="
        window.addEventListener('desktop-notify', (event) => {
            const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
            addToast(data);
        });
     "
     class="relative">

    @if(auth()->check() && auth()->user()->isTapersip())
        <!-- Tombol Icon Lonceng Notifikasi di Header -->
        <div class="relative">
            <button type="button"
                    @click="open = !open"
                    class="relative p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none cursor-pointer"
                    title="Notifikasi Tugas Tapersip"
                    aria-label="Notifikasi">
                <!-- Ikon Lonceng -->
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>

                <!-- Badge Counter Merah Mencolok (Jika ada notifikasi belum dibaca) -->
                @if($pendingCount > 0)
                    <span class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center px-1 rounded-full bg-rose-500 text-[10px] font-bold text-white shadow-xs z-10 select-none">
                        {{ $pendingCount }}
                    </span>
                    <span class="absolute top-1 right-1 flex h-4 w-4 rounded-full bg-rose-400 opacity-75 animate-ping z-10"></span>
                @endif
            </button>

            <!-- Dropdown Panel Popover (Lebar Pasti 380px, Tidak Bertumpuk, Tanpa Footerbar) -->
            <div x-show="open"
                 @click.outside="open = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 top-full mt-2 rounded-2xl bg-white shadow-2xl border border-slate-200/90 z-50 overflow-hidden text-left focus:outline-none"
                 style="width: 380px; max-width: calc(100vw - 2rem); display: none;">

                <!-- Header Popover: Bersih & Proporsional -->
                <div class="px-5 py-3.5 bg-white border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-slate-800 font-jakarta tracking-tight">Notifikasi</span>
                        @if($pendingCount > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200/60 font-mono">
                                {{ $pendingCount }} baru
                            </span>
                        @endif
                    </div>

                    @if($pendingCount > 0)
                        <button type="button"
                                wire:click="markAllAsRead"
                                class="text-xs font-semibold text-slate-400 hover:text-slate-700 hover:underline transition-colors cursor-pointer">
                            Tandai dibaca ✓
                        </button>
                    @else
                        <span class="text-xs text-slate-400 font-normal">Semua selesai</span>
                    @endif
                </div>

                <!-- Feed Notifikasi Scrollable (Maks 10+ Data dengan Scroll Halus) -->
                <div class="max-h-[460px] overflow-y-auto overscroll-contain divide-y divide-slate-100 [scrollbar-width:thin] [scrollbar-color:#cbd5e1_transparent]">
                    @forelse($feedItems as $item)
                        <button type="button"
                                wire:click="dismissAndRedirect('{{ $item['key'] }}', '{{ $item['target_url'] }}')"
                                @click="open = false"
                                class="w-full text-left px-5 py-3.5 hover:bg-slate-50 transition-colors group cursor-pointer block relative {{ $item['is_unread'] ? 'border-l-[3px] border-l-[var(--color-primary)] bg-emerald-50/15' : 'bg-white' }}">

                            <!-- Baris 1: Ikon Aksi + Judul + Waktu Relatif -->
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    @if($item['category'] === 'scan_queue')
                                        <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                    @elseif($item['badge'] === 'Pinjam Fisik')
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    @else
                                        <svg class="w-3.5 h-3.5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    @endif

                                    <span class="text-xs font-semibold text-slate-800 truncate">
                                        {{ $item['title'] }}
                                    </span>
                                </div>

                                <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap shrink-0">
                                    {{ $item['timestamp']->diffForHumans(null, true, true) }}
                                </span>
                            </div>

                            <!-- Baris 2: Identifier + Pemohon & Unit -->
                            <div class="mt-1 flex items-center gap-1.5 text-xs truncate">
                                <span class="font-mono font-bold text-[var(--color-primary)] text-xs whitespace-nowrap">
                                    {{ $item['identifier'] }}
                                </span>
                                <span class="text-slate-300">•</span>
                                <span class="text-slate-700 font-medium truncate">
                                    {{ $item['actor'] }}
                                </span>
                                <span class="text-slate-400 text-[11px] truncate">
                                    ({{ $item['unit'] }})
                                </span>
                            </div>

                            <!-- Baris 3: Perihal Ringkas (Jika Ada) -->
                            @if(!empty($item['extra']))
                                <div class="mt-1 text-[11px] text-slate-500 leading-snug truncate" title="{{ $item['extra'] }}">
                                    {{ $item['extra'] }}
                                </div>
                            @endif
                        </button>
                    @empty
                        <div class="px-5 py-10 text-center text-slate-400">
                            <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                                <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                            </div>
                            <p class="text-xs font-semibold text-slate-700">Tidak ada notifikasi baru.</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Semua permohonan dan berkas telah selesai ditinjau.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- FLOATING IN-APP VISUAL TOAST NOTIFICATION CONTAINER            -->
        <!-- (Muncul di Pojok Kanan Atas Layar Web saat ada Event Real-Time)-->
        <!-- ============================================================== -->
        <div class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none w-full max-w-sm sm:max-w-md">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="transform translate-x-12 opacity-0 scale-95"
                     x-transition:enter-end="transform translate-x-0 opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="transform translate-x-0 opacity-100 scale-100"
                     x-transition:leave-end="transform translate-x-12 opacity-0 scale-95"
                     class="pointer-events-auto bg-white rounded-2xl shadow-2xl border p-4 transition-all duration-200 text-left"
                     :class="{
                         'border-emerald-200 ring-2 ring-emerald-500/10': toast.type === 'loan_requested' || toast.type === 'loan_approved_success' || toast.type === 'scan_archived_success',
                         'border-amber-200 ring-2 ring-amber-500/10': toast.type === 'scan_requested' || toast.type === 'loan_approved_mail_failed' || toast.type === 'loan_reminder_success',
                         'border-rose-200 ring-2 ring-rose-500/10': toast.type === 'loan_cancelled' || toast.type === 'loan_rejected_success',
                         'border-blue-200 ring-2 ring-blue-500/10': toast.type === 'loan_returned_success'
                     }">

                    <div class="flex items-start gap-3">
                        <!-- Icon Badge -->
                        <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
                             :class="{
                                 'bg-emerald-50 text-emerald-600 border border-emerald-200': toast.type === 'loan_requested' || toast.type === 'loan_approved_success' || toast.type === 'scan_archived_success',
                                 'bg-amber-50 text-amber-600 border border-amber-200': toast.type === 'scan_requested' || toast.type === 'loan_approved_mail_failed' || toast.type === 'loan_reminder_success',
                                 'bg-rose-50 text-rose-600 border border-rose-200': toast.type === 'loan_cancelled' || toast.type === 'loan_rejected_success',
                                 'bg-blue-50 text-blue-600 border border-blue-200': toast.type === 'loan_returned_success'
                             }">
                            <template x-if="toast.type === 'loan_requested'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </template>
                            <template x-if="toast.type === 'loan_approved_success' || toast.type === 'scan_archived_success'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            <template x-if="toast.type === 'loan_approved_mail_failed'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </template>
                            <template x-if="toast.type === 'loan_reminder_success'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            <template x-if="toast.type === 'loan_rejected_success'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            <template x-if="toast.type === 'loan_returned_success'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </template>
                            <template x-if="toast.type === 'scan_requested'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </template>
                            <template x-if="toast.type === 'loan_cancelled'">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </template>
                        </div>

                        <!-- Konten Toast -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1">
                                <h5 class="text-xs font-bold font-jakarta leading-tight"
                                    :class="{
                                        'text-emerald-900': toast.type === 'loan_requested' || toast.type === 'loan_approved_success' || toast.type === 'scan_archived_success',
                                        'text-amber-900': toast.type === 'scan_requested' || toast.type === 'loan_approved_mail_failed' || toast.type === 'loan_reminder_success',
                                        'text-rose-900': toast.type === 'loan_cancelled' || toast.type === 'loan_rejected_success',
                                        'text-blue-900': toast.type === 'loan_returned_success'
                                    }"
                                    x-text="toast.title"></h5>
                                <button type="button"
                                        @click="removeToast(toast.id)"
                                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <p class="text-xs text-slate-600 mt-1 leading-snug break-words" x-text="toast.body"></p>

                            <!-- Aksi Cepat Toast -->
                            <div class="mt-2.5 flex items-center gap-2">
                                <a :href="toast.url"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white shadow-xs transition-colors"
                                   :class="{
                                       'bg-[var(--color-primary)] hover:bg-emerald-800': toast.type === 'loan_requested' || toast.type === 'loan_approved_success' || toast.type === 'scan_archived_success',
                                       'bg-amber-600 hover:bg-amber-700': toast.type === 'scan_requested' || toast.type === 'loan_approved_mail_failed' || toast.type === 'loan_reminder_success',
                                       'bg-rose-600 hover:bg-rose-700': toast.type === 'loan_rejected_success',
                                       'bg-blue-600 hover:bg-blue-700': toast.type === 'loan_returned_success',
                                       'bg-slate-800 hover:bg-slate-900': toast.type === 'loan_cancelled'
                                   }">
                                    <span x-text="toast.type === 'scan_archived_success' ? 'Buka Arsip &rarr;' : ((toast.type && (toast.type.startsWith('loan_approved') || toast.type === 'loan_rejected_success' || toast.type === 'loan_returned_success' || toast.type === 'loan_reminder_success')) ? 'Periksa Meja &rarr;' : 'Tinjau Data &rarr;')"></span>
                                </a>
                                <button type="button"
                                        @click="removeToast(toast.id)"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-500 hover:bg-slate-100 transition-colors">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @endif
</div>
