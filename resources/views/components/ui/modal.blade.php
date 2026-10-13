@props([
    'title',
    'id' => null,
    'name' => null,
    'width' => 'max-w-xl', // Default lebar modal (max-w-md, max-w-lg, max-w-xl, max-w-2xl)
    'padding' => true
])

@php
$id = $id ?? $name;
@endphp

<div 
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail.id === '{{ $id }}' || $event.detail === '{{ $id }}' || (Array.isArray($event.detail) && $event.detail[0] === '{{ $id }}')) show = true"
    x-on:close-modal.window="if ($event.detail.id === '{{ $id }}' || $event.detail === '{{ $id }}' || (Array.isArray($event.detail) && $event.detail[0] === '{{ $id }}')) show = false"
    x-on:keydown.escape.window="show = false"
    x-cloak
>
    <!-- Modal Container (Removed x-teleport to allow Livewire DOM diffing) -->
    <div 
        x-show="show" 
        style="display: none;" 
        class="fixed inset-0 z-[999] overflow-y-auto" 
        aria-labelledby="modal-title" 
        role="dialog" 
        aria-modal="true"
    >
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <!-- Background backdrop -->
                <div 
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="show = false"
                ></div>

                <!-- Modal panel -->
                <div 
                    x-show="show"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full {{ $width }}"
                >
                    <!-- Header -->
                    <div class="bg-slate-50 px-4 py-4 sm:px-6 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800 tracking-tight" id="modal-title">
                            {{ $title }}
                        </h3>
                        <button type="button" @click="show = false" class="rounded-md text-slate-400 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] transition-colors">
                            <span class="sr-only">Tutup</span>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="{{ $padding ? 'px-4 py-5 sm:p-6 max-h-[70vh] overflow-y-auto' : '' }}">
                        {{ $slot }}
                    </div>

                    <!-- Footer -->
                    @if (isset($footer))
                    <div class="bg-slate-50 px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-200 gap-3">
                        {{ $footer }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
</div>

