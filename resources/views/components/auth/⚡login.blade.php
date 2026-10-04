<?php

use Livewire\Component;

new class extends Component {
    public $email = '';
    public $password = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Mock login
        return redirect()->route('dashboard');
    }
};
?>

<div class="min-h-screen flex items-center justify-center p-4 sm:p-6 bg-[var(--color-background)]">
    <div class="w-full max-w-md bg-white rounded-xl shadow-sm border border-[var(--color-border)] p-6 sm:p-8">
        <div class="text-center mb-6 sm:mb-8">
            <div class="inline-flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-lg bg-emerald-500 text-white font-bold text-xl sm:text-2xl mb-4">
                EA
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">E-Arsip Enterprise</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2">Sistem Manajemen Dokumen Formal Perusahaan</p>
        </div>

        <form wire:submit="login" class="space-y-4 sm:space-y-5">
            <div>
                <label for="email" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5 uppercase tracking-wide">Email Karyawan</label>
                <input wire:model="email" type="email" id="email" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="budi.santoso@perusahaan.com">
                @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5 uppercase tracking-wide">Password</label>
                <input wire:model="password" type="password" id="password" class="w-full bg-slate-50 border border-slate-200 text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" placeholder="••••••••">
                @error('password') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-0 mt-1">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 h-4 w-4">
                    <span class="text-sm text-slate-600 font-medium">Ingat saya</span>
                </label>
                <a href="#" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium self-start sm:self-auto">Lupa password?</a>
            </div>

            <button type="submit" class="w-full bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-medium px-4 py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2 mt-2">
                <span>Masuk Sistem</span>
                <svg wire:loading wire:target="login" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </button>
        </form>

        <div class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-400 gap-2 sm:gap-0 text-center sm:text-left">
            <span>&copy; 2026 Divisi IT & Tapersip</span>
            <span>Versi 1.0.0-rc</span>
        </div>
    </div>
</div>
