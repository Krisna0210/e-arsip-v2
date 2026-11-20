@props([
    'url',
    'watermark' => true,
    'watermarkPatternId' => 'forensic-watermark',
    'watermarkText1' => 'DOKUMEN ARSIP PERUSAHAAN • RAHASIA',
    'watermarkText2' => null,
    'watermarkText3' => null,
    'height' => 'h-full',
    'initialScale' => 1.1,
])

@php
    $watermarkText2 = $watermarkText2 ?? ((Auth::user()->name ?? 'User') . ' • ' . (Auth::user()->unit?->name ?? 'Internal') . ' • ' . request()->ip());
    $watermarkText3 = $watermarkText3 ?? (now()->format('d/m/Y H:i') . ' WIB');
@endphp

@once
    <script src="{{ asset('vendor/pdfjs/pdf.min.js') }}"></script>
    <script>
        window.pdfjsWorkerSrc = window.pdfjsWorkerSrc || "{{ asset('vendor/pdfjs/pdf.worker.min.js') }}";
        if (window.pdfjsLib) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = window.pdfjsWorkerSrc;
        }
    </script>
    <script src="{{ asset('vendor/pdfjs/pdf-viewer.js') }}?v={{ filemtime(public_path('vendor/pdfjs/pdf-viewer.js')) }}"></script>
@endonce

<div x-data="pdfViewerComponent(@js($url), {{ $initialScale }})"
     @contextmenu.prevent
     class="flex flex-col {{ $height }} w-full min-h-[500px] bg-slate-950 select-none relative overflow-hidden"
>
    <!-- Style Proteksi Pencetakan (CSS Anti-Print) -->
    <style>
        @media print {
            body * {
                display: none !important;
                visibility: hidden !important;
            }
            body::before {
                content: "DOKUMEN DILINDUNGI: Berkas digital bersifat rahasia dan dilarang dicetak sesuai kebijakan keamanan informasi perusahaan.";
                display: block !important;
                font-family: sans-serif;
                font-size: 16pt;
                font-weight: bold;
                text-align: center;
                color: #b91c1c;
                padding-top: 150px;
            }
        }
    </style>

    <!-- Area Pratinjau Vertikal Scroll -->
    <div x-ref="scrollContainer"
         class="flex-1 w-full h-full overflow-y-auto overflow-x-hidden p-6 flex flex-col items-center relative scroll-smooth"
    >
        <!-- Canvas Container Tempat Render Halaman PDF -->
        <div x-ref="canvasContainer" class="flex flex-col items-center w-full max-w-5xl z-10"></div>

        <!-- Skeleton / Loading Indicator -->
        <div x-show="isLoading"
             class="absolute inset-0 z-20 bg-slate-950/80 backdrop-blur-sm flex flex-col items-center justify-center text-center p-6"
             style="display: none;"
        >
            <div class="inline-block animate-spin rounded-full h-9 w-9 border-2 border-emerald-500 border-t-transparent mb-3"></div>
            <h4 class="text-xs font-semibold text-slate-200">Memuat Dokumen PDF...</h4>
            <p class="text-[11px] text-slate-400 mt-1">Menyiapkan halaman dokumen untuk dibaca</p>
        </div>

        <!-- Error State -->
        <div x-show="loadError"
             class="absolute inset-0 z-20 bg-slate-950/90 flex flex-col items-center justify-center text-center p-6"
             style="display: none;"
        >
            <div class="p-3 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 mb-3">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h4 class="text-xs font-bold text-rose-300" x-text="loadError"></h4>
            <button type="button"
                    @click="loadPdf()"
                    class="mt-4 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 transition cursor-pointer"
            >
                Coba Muat Ulang
            </button>
        </div>
    </div>

    <!-- Floating Zoom & Page Toolbar -->
    <div x-show="!isLoading && !loadError && numPages > 0"
         class="absolute bottom-5 left-1/2 -translate-x-1/2 z-40 bg-slate-900/90 backdrop-blur-md border border-slate-700/80 rounded-full px-3.5 py-1.5 shadow-2xl flex items-center gap-3 text-slate-200 text-xs select-none"
    >
        <!-- Zoom Out -->
        <button type="button"
                @click="zoomOut()"
                :disabled="scale <= 0.6"
                class="p-1 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
                title="Perkecil (-)"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/>
            </svg>
        </button>

        <!-- Current Zoom Percentage & Reset -->
        <button type="button"
                @click="resetZoom()"
                class="font-mono text-xs px-1 hover:text-emerald-400 transition cursor-pointer"
                title="Reset Zoom"
        >
            <span x-text="Math.round(scale * 100) + '%'"></span>
        </button>

        <!-- Zoom In -->
        <button type="button"
                @click="zoomIn()"
                :disabled="scale >= 2.5"
                class="p-1 hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
                title="Perbesar (+)"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
        </button>

        <div class="w-px h-4 bg-slate-700"></div>

        <!-- Total Pages -->
        <span class="font-mono text-[11px] text-slate-400"
              x-text="numPages > 0 ? (renderedPages + '/' + numPages + ' Hlm') : ''"
        ></span>
    </div>

    <!-- Forensic Dynamic Watermark Overlay -->
    @if($watermark)
        <div class="absolute inset-0 z-30 pointer-events-none overflow-hidden select-none" style="opacity: 0.24;">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="{{ $watermarkPatternId }}" width="340" height="200" patternUnits="userSpaceOnUse" patternTransform="rotate(-35)">
                        <text x="15" y="45" font-family="monospace" font-size="12" font-weight="bold" fill="#0f172a" stroke="#ffffff" stroke-width="0.25" letter-spacing="1">
                            {{ $watermarkText1 }}
                        </text>
                        <text x="15" y="65" font-family="monospace" font-size="10" font-weight="semibold" fill="#1e293b" stroke="#ffffff" stroke-width="0.2">
                            {{ $watermarkText2 }}
                        </text>
                        <text x="15" y="85" font-family="monospace" font-size="9" fill="#334155" stroke="#ffffff" stroke-width="0.15">
                            {{ $watermarkText3 }}
                        </text>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#{{ $watermarkPatternId }})" />
            </svg>
        </div>
    @endif

    <!-- Anti-Capture Screen Shield (Aktif saat jendela kehilangan fokus / membuka snipping tool) -->
    <div x-show="isBlurred"
         x-transition.opacity
         class="absolute inset-0 z-50 bg-slate-950/95 backdrop-blur-2xl flex flex-col items-center justify-center text-center p-6"
         style="display: none;"
    >
        <div class="p-3 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 mb-3">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-slate-100">Tampilan Pratinjau Diamankan</h3>
        <p class="text-xs text-slate-400 mt-1 max-w-sm">Konten disamarkan secara otomatis saat jendela browser kehilangan fokus.</p>
    </div>
</div>
