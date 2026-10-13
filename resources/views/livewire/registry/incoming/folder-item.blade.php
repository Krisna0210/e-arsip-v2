@php
    $padding = $level * 1.5;
    $isActive = $activeFolderId === $folder->id;
@endphp

<div class="flex flex-col">
    <div class="flex items-center group">
        <!-- Spacer for indentation -->
        <div style="width: {{ $padding }}rem;" class="flex-shrink-0"></div>
        
        <!-- Folder Button -->
        <div class="flex-1 flex items-center justify-between px-2 py-1.5 rounded-md transition-colors {{ $isActive ? 'bg-blue-50 text-[var(--color-primary)] dark:bg-blue-900/30 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            <button 
                wire:click="setActiveFolder({{ $folder->id }})" 
                class="flex-1 flex items-center gap-2 text-sm font-medium text-left truncate">
                <svg class="w-5 h-5 flex-shrink-0 {{ $isActive ? 'text-[var(--color-primary)] dark:text-blue-400' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-500 dark:group-hover:text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                <span class="truncate">{{ $folder->name }}</span>
            </button>
            
            <!-- Delete action - only show on hover and if not active -->
            <button 
                wire:click="deleteFolder({{ $folder->id }})" 
                wire:confirm="Apakah Anda yakin ingin menghapus loker ini?"
                class="p-1 opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition-opacity"
                title="Hapus Loker">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </div>
    </div>

    <!-- Recursive children -->
    @if($folder->children && $folder->children->count() > 0)
        <div class="flex flex-col mt-0.5 space-y-0.5">
            @foreach($folder->children as $child)
                @include('livewire.registry.incoming.folder-item', ['folder' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
