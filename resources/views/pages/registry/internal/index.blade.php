<x-layouts.app>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold leading-tight text-slate-800 dark:text-slate-200">
                    Memo Internal
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kelola dan pantau seluruh dokumen memo internal.
                </p>
            </div>
            <div class="flex gap-3">
                <a href="#" class="inline-flex items-center justify-center rounded-lg border border-transparent bg-[var(--color-primary)] px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 sm:w-auto transition-colors">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Registrasi Baru
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-screen-2xl sm:px-6 lg:px-8">
            <livewire:registry.internal.index />
        </div>
    </div>
</x-layouts.app>