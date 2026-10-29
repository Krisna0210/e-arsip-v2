<div wire:poll.8s="checkLoanEvents"
     x-data="{
        open: false,
        activeNotifTab: 'unread',
        toasts: [],
        playChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;

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
        <!-- Trigger Icon Lonceng Header (Touch target >= 44x44px + Focus ring a11y) -->
        <div class="relative inline-block">
            <button type="button"
                    @click="open = !open"
                    class="relative min-w-11 min-h-11 p-2.5 flex items-center justify-center rounded-lg text-slate-700 hover:text-slate-900 hover:bg-slate-100/70 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#065F46] cursor-pointer"
                    title="Notifikasi"
                    aria-label="Notifikasi">
                <!-- Ikon Lonceng Outline -->
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>

                <!-- PERBAIKAN AUDIT #1: Badge Counter Merah hanya dirender saat $pendingCount > 0 -->
                @if(($pendingCount ?? 0) > 0)
                    <span class="absolute top-1.5 right-1.5 flex h-4 min-w-4 items-center justify-center px-1 rounded-full bg-[#DC2626] text-[11px] font-semibold tabular-nums text-white shadow-xs z-10 select-none">
                        {{ $pendingCount }}
                    </span>
                @endif
            </button>

            <!-- Dropdown Panel Popover (Corner radius 12px / rounded-xl + Hairline border #E2E8F0) -->
            <div x-show="open"
                 @click.outside="open = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 top-full mt-2 rounded-xl bg-white shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08)] border border-slate-200 z-50 text-left focus:outline-none"
                 style="width: 440px; max-width: calc(100vw - 2rem); display: none;">

                <!-- Upward Pointer Beak Indicator -->
                <div class="absolute -top-1.5 right-4 w-3 h-3 bg-white border-t border-l border-slate-200 rotate-45 z-20"></div>

                <!-- Header Popover Row 1: Padding 16px (py-4) x 24px (px-6) sesuai 8px grid -->
                <div class="px-6 py-4 flex items-center justify-between border-b border-slate-200 bg-white rounded-t-xl relative z-10">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <!-- Type scale: 18px (text-lg), font-bold (700), soft black (#0F172A / text-slate-900) -->
                        <h3 class="text-lg font-bold text-slate-900 leading-snug tracking-tight">Notifikasi</h3>
                    </div>

                    <!-- PERBAIKAN AUDIT #2: State Action "Semua selesai" Disabled saat Unread = 0 -->
                    <div>
                        @if(($pendingCount ?? 0) > 0)
                            <button type="button"
                                    wire:click="markAllAsRead"
                                    class="text-sm font-medium text-[#065F46] hover:text-[#047857] rounded px-1.5 py-0.5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#065F46] cursor-pointer">
                                Tandai dibaca ✓
                            </button>
                        @else
                            <span class="text-sm font-medium text-slate-300 cursor-not-allowed select-none" aria-disabled="true">
                                Semua selesai
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Header Popover Row 2: Tabs (Gap 24px / gap-6, tanpa Vertical Divider) -->
                <div class="px-6 border-b border-slate-200 bg-white relative z-10">
                    <div class="flex items-center gap-6 text-sm">
                        <!-- Tab 1: Belum Dibaca -->
                        <button type="button"
                                @click="activeNotifTab = 'unread'"
                                class="py-3 transition-all cursor-pointer flex items-center gap-2 relative border-b-2 -mb-px focus-visible:outline-none"
                                :class="activeNotifTab === 'unread' ? 'text-[#065F46] font-semibold border-[#065F46]' : 'text-slate-500 font-medium hover:text-slate-700 border-transparent'">
                            <span>Belum Dibaca</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums"
                                  :class="activeNotifTab === 'unread' ? 'bg-[#D1FAE5] text-[#065F46]' : 'bg-slate-100 text-slate-600'">
                                {{ $pendingCount ?? 0 }}
                            </span>
                        </button>

                        <!-- PERBAIKAN AUDIT #3: Vertical Divider dihapus, diganti gap-6 (24px lg token) -->

                        <!-- Tab 2: Histori -->
                        <button type="button"
                                @click="activeNotifTab = 'history'"
                                class="py-3 transition-all cursor-pointer flex items-center gap-2 relative border-b-2 -mb-px focus-visible:outline-none"
                                :class="activeNotifTab === 'history' ? 'text-[#065F46] font-semibold border-[#065F46]' : 'text-slate-500 font-medium hover:text-slate-700 border-transparent'">
                            <span>Histori</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums"
                                  :class="activeNotifTab === 'history' ? 'bg-[#D1FAE5] text-[#065F46]' : 'bg-slate-100 text-slate-600'">
                                {{ $historyCount ?? 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Feed Notifikasi Scrollable -->
                <div class="max-h-[440px] overflow-y-auto overscroll-contain rounded-b-xl [scrollbar-width:thin] [scrollbar-color:#cbd5e1_transparent]">
                    <!-- Tab 1: Belum Dibaca -->
                    <div x-show="activeNotifTab === 'unread'" class="divide-y divide-slate-100">
                        @forelse($unreadItems ?? [] as $item)
                            <!-- Content-First Sizing (Rule 5): Height auto + padding konsisten (px-6 py-4) -->
                            <button type="button"
                                    wire:click="dismissAndRedirect('{{ $item['key'] }}', '{{ $item['target_url'] }}')"
                                    @click="open = false"
                                    class="w-full text-left px-6 py-4 hover:bg-slate-50/90 transition-colors group cursor-pointer block relative border-l-2 border-l-[#065F46] bg-emerald-50/10 focus-visible:outline-none focus-visible:bg-slate-50">

                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        @if($item['category'] === 'scan_queue')
                                            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                            </svg>
                                        @elseif($item['badge'] === 'Pinjam Fisik')
                                            <svg class="w-4 h-4 text-[#065F46] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @endif
                                        <span class="text-xs font-semibold text-slate-900 truncate">
                                            {{ $item['title'] }}
                                        </span>
                                    </div>
                                    <!-- WCAG AA Contrast Fix: text-slate-500 (#64748B) + tabular-nums -->
                                    <span class="text-[11px] text-slate-500 tabular-nums whitespace-nowrap shrink-0">
                                        {{ $item['timestamp']->diffForHumans(null, true, true) }}
                                    </span>
                                </div>

                                <div class="mt-1 flex items-center gap-1.5 text-xs truncate">
                                    <span class="font-mono font-bold text-[#065F46] text-xs whitespace-nowrap">
                                        {{ $item['identifier'] }}
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-700 font-medium truncate">
                                        {{ $item['actor'] }}
                                    </span>
                                    <span class="text-slate-500 text-xs truncate">
                                        ({{ $item['unit'] }})
                                    </span>
                                </div>

                                @if(!empty($item['extra']))
                                    <div class="mt-1 text-xs text-slate-600 leading-normal truncate" title="{{ $item['extra'] }}">
                                        {{ $item['extra'] }}
                                    </div>
                                @endif
                            </button>
                        @empty
                            <!-- Empty State: Belum Dibaca (Padding 48px / py-12, Proximity Hierarchy 16px -> 8px) -->
                            <div class="px-6 py-12 text-center">
                                <div class="relative w-24 h-24 mx-auto mb-4 flex items-center justify-center">
                                    <!-- Grounding Shadow Oval -->
                                    <div class="absolute bottom-0 w-16 h-2 bg-slate-200/60 rounded-full blur-[2px]"></div>

                                    <!-- Lingkaran Soft Background (Standard Tailwind w-20 h-20 = 80px) -->
                                    <div class="w-20 h-20 rounded-full bg-[#EAF0F6] flex items-center justify-center relative">
                                        <div class="relative">
                                            <!-- Amplop Biru Muda-Medium -->
                                            <svg class="w-10 h-8" viewBox="0 0 44 34" fill="none">
                                                <rect x="2" y="3" width="40" height="28" rx="4" fill="#6B8CB7" />
                                                <path d="M2 5L22 19L42 5" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>

                                            <!-- Speed Lines Hijau di Kanan Atas -->
                                            <div class="absolute -top-3 -right-2.5 text-[#065F46]">
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                                    <line x1="12" y1="2" x2="12" y2="6" />
                                                    <line x1="18" y1="4.5" x2="15" y2="7.5" />
                                                    <line x1="20" y1="11" x2="16" y2="11" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Headline: 16px (text-base), font-bold (700), gap ke subtext 8px (mt-2) -->
                                <h4 class="text-base font-bold text-slate-900 leading-snug tracking-tight">Tidak ada notifikasi baru.</h4>
                                <p class="text-sm text-slate-500 mt-2 max-w-[300px] mx-auto leading-normal">
                                    Semua permohonan dan berkas telah selesai ditinjau.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Tab 2: Histori -->
                    <div x-show="activeNotifTab === 'history'" class="divide-y divide-slate-100" style="display: none;">
                        @forelse($historyItems ?? [] as $item)
                            <button type="button"
                                    wire:click="dismissAndRedirect('{{ $item['key'] }}', '{{ $item['target_url'] }}')"
                                    @click="open = false"
                                    class="w-full text-left px-6 py-4 hover:bg-slate-50 transition-colors group cursor-pointer block relative bg-white focus-visible:outline-none focus-visible:bg-slate-50">

                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-xs font-medium text-slate-700 truncate">
                                            {{ $item['title'] }}
                                        </span>
                                    </div>
                                    <span class="text-[11px] text-slate-500 tabular-nums whitespace-nowrap shrink-0">
                                        {{ $item['timestamp']->diffForHumans(null, true, true) }}
                                    </span>
                                </div>

                                <div class="mt-1 flex items-center gap-1.5 text-xs truncate">
                                    <span class="font-mono font-semibold text-slate-700 text-xs whitespace-nowrap">
                                        {{ $item['identifier'] }}
                                    </span>
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-600 font-medium truncate">
                                        {{ $item['actor'] }}
                                    </span>
                                    <span class="text-slate-500 text-xs truncate">
                                        ({{ $item['unit'] }})
                                    </span>
                                </div>
                            </button>
                        @empty
                            <!-- Empty State: Histori -->
                            <div class="px-6 py-12 text-center">
                                <div class="relative w-24 h-24 mx-auto mb-4 flex items-center justify-center">
                                    <div class="w-20 h-20 rounded-full bg-[#EAF0F6] flex items-center justify-center relative">
                                        <svg class="w-8 h-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <h4 class="text-base font-bold text-slate-900 leading-snug tracking-tight">Belum ada riwayat notifikasi.</h4>
                                <p class="text-sm text-slate-500 mt-2 max-w-[280px] mx-auto leading-normal">
                                    Notifikasi yang telah selesai ditinjau akan tercatat di sini.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TOAST CONTAINER (Nested Radius Rule 4: Outer 12px, Inner 8px)  -->
        <!-- ============================================================== -->
        <div class="fixed top-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none w-full max-w-sm sm:max-w-md">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="transform translate-x-12 opacity-0 scale-95"
                     x-transition:enter-end="transform translate-x-0 opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="transform translate-x-0 opacity-100 scale-100"
                     x-transition:leave-end="transform translate-x-12 opacity-0 scale-95"
                     class="pointer-events-auto bg-white rounded-xl shadow-[0_4px_20px_-2px_rgba(15,23,42,0.08)] border border-slate-200 p-4 transition-all duration-200 text-left">

                    <div class="flex items-start gap-3">
                        <div class="h-10 w-10 rounded-lg flex items-center justify-center shrink-0 shadow-xs"
                             :class="{
                                 'bg-emerald-50 text-[#065F46] border border-emerald-200': toast.type === 'loan_requested' || toast.type === 'loan_approved_success' || toast.type === 'scan_archived_success',
                                 'bg-amber-50 text-amber-700 border border-amber-200': toast.type === 'scan_requested' || toast.type === 'loan_approved_mail_failed' || toast.type === 'loan_reminder_success',
                                 'bg-rose-50 text-rose-700 border border-rose-200': toast.type === 'loan_cancelled' || toast.type === 'loan_rejected_success' || toast.type === 'cancellation_requested',
                                 'bg-blue-50 text-blue-700 border border-blue-200': toast.type === 'loan_returned_success'
                             }">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h5 class="text-sm font-bold leading-snug text-slate-900" x-text="toast.title"></h5>
                                <button type="button"
                                        @click="removeToast(toast.id)"
                                        class="text-slate-400 hover:text-slate-600 p-1 rounded-md cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <p class="text-xs text-slate-600 mt-1 leading-normal break-words" x-text="toast.body"></p>

                            <div class="mt-3 flex items-center gap-2">
                                <a :href="toast.url"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-white shadow-xs bg-[#065F46] hover:bg-[#047857] transition-colors">
                                    <span x-text="toast.type === 'cancellation_requested' ? 'Tinjau Permohonan →' : 'Tinjau Data →'"></span>
                                </a>
                                <button type="button"
                                        @click="removeToast(toast.id)"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
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
