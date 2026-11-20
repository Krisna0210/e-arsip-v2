/**
 * E-Arsip Secure PDF.js Alpine Component
 * Handles canvas rendering, page navigation, zoom, anti-print, anti-screenshot, and right-click protection.
 */
function pdfViewerComponent(url, initialScale) {
    // Objek PDF.js disimpan di variabel closure (bukan properti objek Alpine)
    // agar Alpine tidak membungkusnya ke dalam Proxy, yang menyebabkan kegagalan akses private member (#d)
    let rawPdfDoc = null;

    return {
        url: url,
        numPages: 0,
        scale: initialScale || 1.1,
        isLoading: true,
        isRendering: false,
        loadError: null,
        isBlurred: false,
        renderToken: 0,
        renderedPages: 0,

        init() {
            // Tangkap dan blokir tombol shortcut Print, Save As, dan Inspect
            const blockKey = (e) => {
                if (!this.$el.isConnected) return;
                const key = e.key ? e.key.toLowerCase() : '';
                if ((e.ctrlKey || e.metaKey) && ['p', 's', 'u'].includes(key)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                if (e.key === 'F12' || ((e.ctrlKey || e.metaKey) && e.shiftKey && ['i', 'j', 'c'].includes(key))) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            };
            window.addEventListener('keydown', blockKey, true);

            // Blokir menu klik kanan secara ketat di fase capturing
            const blockContext = (e) => {
                if (!this.$el.isConnected) return;
                if (this.$el.contains(e.target)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            };
            window.addEventListener('contextmenu', blockContext, true);

            // Anti-Capture Screen Shield saat jendela kehilangan fokus
            window.addEventListener('blur', () => {
                if (this.$el.isConnected) this.isBlurred = true;
            });
            window.addEventListener('focus', () => {
                if (this.$el.isConnected) this.isBlurred = false;
            });

            this.$watch('scale', () => {
                this.renderAllPages();
            });

            this.$watch('url', (newUrl) => {
                if (newUrl) {
                    this.loadPdf();
                }
            });

            this.loadPdf();
        },

        destroy() {
            if (rawPdfDoc) {
                try {
                    rawPdfDoc.destroy();
                } catch (e) {}
                rawPdfDoc = null;
            }
        },

        async ensurePdfJsReady() {
            const workerUrl = window.pdfjsWorkerSrc || '/vendor/pdfjs/pdf.worker.min.js';
            if (window.pdfjsLib) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;
                return;
            }

            return new Promise((resolve, reject) => {
                let script = document.querySelector('script[src*="pdf.min.js"]');
                if (!script) {
                    script = document.createElement('script');
                    script.src = window.pdfjsWorkerSrc
                        ? window.pdfjsWorkerSrc.replace('pdf.worker.min.js', 'pdf.min.js')
                        : '/vendor/pdfjs/pdf.min.js';
                    document.head.appendChild(script);
                }

                const timeout = setTimeout(() => {
                    if (window.pdfjsLib) {
                        window.pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;
                        resolve();
                    } else {
                        reject(new Error('Waktu tunggu pemuatan modul PDF.js habis.'));
                    }
                }, 8000);

                if (window.pdfjsLib) {
                    clearTimeout(timeout);
                    window.pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;
                    resolve();
                    return;
                }

                script.addEventListener('load', () => {
                    clearTimeout(timeout);
                    if (window.pdfjsLib) {
                        window.pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;
                        resolve();
                    } else {
                        reject(new Error('Pustaka PDF.js termuat namun objek pdfjsLib tidak ditemukan.'));
                    }
                });

                script.addEventListener('error', () => {
                    clearTimeout(timeout);
                    reject(new Error('Gagal memuat berkas vendor pdf.min.js dari server.'));
                });
            });
        },

        async loadPdf() {
            if (!this.url) return;
            this.isLoading = true;
            this.loadError = null;
            this.renderedPages = 0;
            this.numPages = 0;

            if (rawPdfDoc) {
                try {
                    rawPdfDoc.destroy();
                } catch (e) {}
                rawPdfDoc = null;
            }

            if (this.$refs.canvasContainer) {
                this.$refs.canvasContainer.innerHTML = '';
            }

            try {
                await this.ensurePdfJsReady();

                const loadingTask = window.pdfjsLib.getDocument({
                    url: this.url,
                    withCredentials: true
                });

                rawPdfDoc = await loadingTask.promise;
                this.numPages = rawPdfDoc.numPages;
                this.isLoading = false;

                this.$nextTick(() => {
                    this.renderAllPages();
                });
            } catch (err) {
                console.error('Gagal memuat dokumen PDF:', err);
                this.loadError = 'Gagal memuat pratinjau dokumen: ' + (err.message || 'Format berkas tidak valid.');
                this.isLoading = false;
            }
        },

        async renderAllPages() {
            if (!rawPdfDoc) return;
            const container = this.$refs.canvasContainer;
            if (!container) return;

            const currentToken = ++this.renderToken;
            this.isRendering = true;
            container.innerHTML = '';
            const pixelRatio = window.devicePixelRatio || 1;

            for (let pageNum = 1; pageNum <= this.numPages; pageNum++) {
                if (this.renderToken !== currentToken) return;

                try {
                    const page = await rawPdfDoc.getPage(pageNum);
                    if (this.renderToken !== currentToken) return;

                    const viewport = page.getViewport({ scale: this.scale });

                    const pageWrapper = document.createElement('div');
                    pageWrapper.setAttribute('data-page-number', pageNum);
                    pageWrapper.className = 'relative flex flex-col items-center mb-6 last:mb-2 w-full';

                    const canvas = document.createElement('canvas');
                    canvas.className = 'shadow-2xl rounded bg-white max-w-full select-none pointer-events-none border border-slate-700/60';

                    const pageLabel = document.createElement('div');
                    pageLabel.className = 'mt-2 text-[10px] font-mono text-slate-400 select-none';
                    pageLabel.innerText = `Halaman ${pageNum} dari ${this.numPages}`;

                    canvas.width = Math.floor(viewport.width * pixelRatio);
                    canvas.height = Math.floor(viewport.height * pixelRatio);
                    canvas.style.width = Math.floor(viewport.width) + 'px';
                    canvas.style.height = Math.floor(viewport.height) + 'px';

                    pageWrapper.appendChild(canvas);
                    pageWrapper.appendChild(pageLabel);
                    container.appendChild(pageWrapper);

                    const transform = pixelRatio !== 1 ? [pixelRatio, 0, 0, pixelRatio, 0, 0] : null;

                    await page.render({
                        canvasContext: canvas.getContext('2d'),
                        transform: transform,
                        viewport: viewport
                    }).promise;

                    if (this.renderToken === currentToken) {
                        this.renderedPages = pageNum;
                    }
                } catch (pageErr) {
                    if (this.renderToken === currentToken) {
                        console.error('Error saat merender halaman ' + pageNum, pageErr);
                        this.loadError = 'Gagal merender halaman ' + pageNum + ': ' + (pageErr.message || pageErr);
                    }
                }
            }

            if (this.renderToken === currentToken) {
                this.isRendering = false;
            }
        },

        zoomIn() {
            if (this.scale < 2.5) {
                this.scale = parseFloat((this.scale + 0.2).toFixed(1));
            }
        },

        zoomOut() {
            if (this.scale > 0.6) {
                this.scale = parseFloat((this.scale - 0.2).toFixed(1));
            }
        },

        resetZoom() {
            this.scale = initialScale || 1.1;
        }
    };
}

if (typeof window !== 'undefined') {
    window.pdfViewerComponent = pdfViewerComponent;
    if (window.Alpine) {
        window.Alpine.data('pdfViewerComponent', pdfViewerComponent);
    } else {
        document.addEventListener('alpine:init', () => {
            if (window.Alpine) {
                window.Alpine.data('pdfViewerComponent', pdfViewerComponent);
            }
        });
    }
}
