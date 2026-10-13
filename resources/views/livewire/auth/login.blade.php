<div class="min-h-screen w-full flex items-center justify-center bg-[var(--color-background)] px-4 py-8 sm:px-6 lg:px-8">
    <div class="w-full max-w-[420px] flex flex-col items-center">
        <!-- Logo & Header -->
        <div class="w-full text-center mb-8 flex flex-col items-center">
            <div class="w-40 h-25 flex items-center justify-center">
                <img src="{{ asset('storage/assets/logo_cms_hd_png.svg') }}" alt="Logo Instansi" class="w-40 h-20 object-contain shrink-0">
            </div>
            <h1 class="text-[var(--color-text-primary)] text-[1.5rem] font-bold leading-[1.25] tracking-tight mb-2">
                E-ARSIP
            </h1>
            <p class="text-[var(--color-text-secondary)] text-[0.875rem] font-medium leading-relaxed">
                Sistem Manajemen Arsip<br>Dokumen Perusahaan
            </p>
        </div>

        <!-- Login Card -->
        <div class="w-full bg-[var(--color-surface)] border border-[var(--color-border)] rounded-[var(--radius-lg)] shadow-sm p-6 sm:p-8">
            <form wire:submit="authenticate" class="flex flex-col gap-6 w-full">

                <!-- Pesan Error Umum -->
                @if($errors->has('email') && !$errors->has('password'))
                    <div class="w-full bg-[var(--color-status-rejected-bg)] border border-[var(--color-status-rejected-border)] text-[var(--color-status-rejected-text)] px-4 py-3 rounded-[var(--radius-md)] text-[0.875rem] font-medium" role="alert">
                        {{ $errors->first('email') }}
                    </div>
                @endif

                <!-- Email / Username Field -->
                <div class="w-full flex flex-col gap-2">
                    <label for="email" class="text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider">Username</label>
                    <div class="relative w-full">
                        <input wire:model="email" id="email" type="text"
                            class="w-full h-11 px-4 py-2 border border-[var(--color-border)] rounded-[var(--radius-md)] bg-[var(--color-surface)] text-[var(--color-text-primary)] text-[0.875rem] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent transition-colors placeholder:text-slate-400"
                            placeholder="Masukkan username">
                    </div>
                    @error('email')
                        <span class="text-[0.8125rem] text-[var(--color-status-rejected-text)] font-medium mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="w-full flex flex-col gap-2">
                    <label for="password" class="text-[0.75rem] font-semibold text-[var(--color-text-primary)] uppercase tracking-wider">Password</label>
                    <div class="relative w-full">
                        <input wire:model="password" id="password" type="{{ $showPassword ? 'text' : 'password' }}"
                            class="w-full h-11 pl-4 pr-11 py-2 border border-[var(--color-border)] rounded-[var(--radius-md)] bg-[var(--color-surface)] text-[var(--color-text-primary)] text-[0.875rem] focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)] focus:border-transparent transition-colors placeholder:text-slate-400"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                        <button type="button" wire:click="togglePassword" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none" tabindex="-1">
                            @if($showPassword)
                                <!-- Icon Eye Slash (Hide) -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                            @else
                                <!-- Icon Eye (Show) -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.543 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            @endif
                        </button>
                    </div>
                    @error('password')
                        <span class="text-[0.8125rem] text-[var(--color-status-rejected-text)] font-medium mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="w-full flex flex-row items-center justify-between gap-4 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input wire:model="remember" type="checkbox" class="w-4 h-4 rounded-[var(--radius-xs)] border-slate-300 text-[var(--color-accent)] focus:ring-[var(--color-accent)] cursor-pointer">
                        <span class="text-[0.8125rem] text-[var(--color-text-secondary)] font-medium group-hover:text-[var(--color-text-primary)] transition-colors whitespace-nowrap">Ingat saya</span>
                    </label>
                    <a href="#" class="text-[0.8125rem] font-semibold text-[var(--color-primary)] hover:text-[var(--color-primary-hover)] transition-colors whitespace-nowrap">
                        Lupa password?
                    </a>
                </div>

                <!-- Submit Button -->
                <div class="w-full mt-2">
                    <button type="submit"
                        class="w-full flex items-center justify-center h-11 px-4 bg-[var(--color-primary)] text-[var(--color-text-inverse)] font-semibold text-[0.875rem] rounded-[var(--radius-md)] hover:bg-[var(--color-primary-hover)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)] transition-colors disabled:opacity-70 disabled:cursor-not-allowed relative"
                        wire:loading.attr="disabled"
                        wire:target="authenticate">
                        <span wire:loading.remove wire:target="authenticate">MASUK SISTEM</span>
                        <span wire:loading.flex wire:target="authenticate" class="items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>MEMPROSES...</span>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Footer inside Card as requested -->
            <div class="w-full mt-8 pt-6 border-t border-[var(--color-border)] flex flex-col items-center gap-1 text-center">
                <span class="text-[0.8125rem] text-[var(--color-text-secondary)] font-medium">&copy; 2026 Departemen MGT & IT</span>
                <span class="text-[0.75rem] text-slate-400 font-medium">Version 1.0.0</span>
            </div>
        </div>
    </div>
</div>
