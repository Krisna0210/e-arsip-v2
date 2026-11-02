@php
    $isActive = $activeFolderId === $folder->id;
    $hasChildren = $folder->children && $folder->children->count() > 0;
    $isExpanded = in_array($folder->id, $expandedFolderIds ?? []);
@endphp

<div wire:key="folder-node-{{ $folder->id }}" class="flex flex-col space-y-0.5" x-data="{ isExpanded: {{ $isExpanded ? 'true' : 'false' }} }">
    <div class="flex items-center group">
        <!-- Folder Button -->
        <div class="flex-1 flex items-center justify-between px-2.5 py-1.5 rounded-lg transition-colors {{ $isActive ? 'bg-blue-50 text-[var(--color-primary)] font-bold border border-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            <div class="flex-1 flex items-center gap-1 min-w-0">
                @if($hasChildren)
                    <button type="button" 
                            @click.stop="isExpanded = !isExpanded"
                            class="p-0.5 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-transform cursor-pointer focus:outline-none"
                            :class="{ 'rotate-90': isExpanded }"
                            title="Buka/Tutup Subfolder">
                        <svg class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                @else
                    <div class="w-4 flex-shrink-0"></div>
                @endif

                <button 
                    type="button"
                    wire:click="setActiveFolder({{ $folder->id }})" 
                    @if($hasChildren) @click="isExpanded = true" @endif
                    class="flex-1 flex items-center gap-1.5 text-xs font-medium text-left truncate cursor-pointer">
                    @if($level > 0)
                        <span class="text-slate-400 font-semibold select-none text-[11px] mr-0.5">↳</span>
                    @else
                        <svg class="w-4 h-4 flex-shrink-0 {{ $isActive ? 'text-[var(--color-primary)] dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    @endif
                    <span class="truncate" title="{{ $folder->name }}">{{ $folder->name }}</span>
                </button>
            </div>
            
            <div class="flex items-center gap-1">
                @php
                    $docCount = $folder->documents_count ?? $folder->documents()->count();
                @endphp
                @if($docCount > 0)
                    <span class="text-[10px] tabular-nums font-mono px-1.5 py-0.2 rounded {{ $isActive ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : 'bg-slate-200/70 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                        {{ $docCount }}
                    </span>
                @endif
                <!-- Delete action - only show on hover and if not active -->
                <button 
                    type="button"
                    wire:click="deleteFolder({{ $folder->id }})" 
                    wire:confirm="Apakah Anda yakin ingin menghapus loker ini?"
                    class="p-0.5 opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition-opacity cursor-pointer"
                    title="Hapus Loker">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Recursive children with left border guideline (collapsible with x-show) -->
    @if($hasChildren)
        <div x-show="isExpanded"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex flex-col pl-3 space-y-0.5 border-l-2 border-slate-200 dark:border-slate-700 ml-3.5 mt-0.5">
            @foreach($folder->children as $child)
                @include('livewire.registry.incoming.folder-item', ['folder' => $child, 'level' => $level + 1, 'expandedFolderIds' => $expandedFolderIds])
            @endforeach
        </div>
    @endif
</div>
