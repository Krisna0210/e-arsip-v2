@php
    $isActive = $activeFolderId === $folder->id;
    $hasChildren = $folder->children && $folder->children->count() > 0;
    $isExpanded = in_array($folder->id, $expandedFolderIds ?? []);
@endphp

<div wire:key="digital-folder-node-{{ $folder->id }}" class="space-y-0.5 group" x-data="{ isExpanded: {{ $isExpanded ? 'true' : 'false' }} }">
    <div class="flex items-center justify-between rounded-lg transition-colors {{ $isActive ? 'bg-emerald-50 text-[var(--color-primary)] font-bold border border-emerald-200' : 'text-slate-700 hover:bg-slate-50' }}">
        <div class="flex-1 flex items-center min-w-0">
            @if($hasChildren)
                <button type="button"
                        @click.stop="isExpanded = !isExpanded"
                        class="pl-2 pr-1 py-2 text-slate-400 hover:text-slate-600 transition-transform cursor-pointer focus:outline-none"
                        :class="{ 'rotate-90': isExpanded }"
                        title="Buka/Tutup Subfolder">
                    <svg class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @else
                <div class="w-5 flex-shrink-0"></div>
            @endif

            <button type="button"
                    wire:click="selectFolder({{ $folder->id }})"
                    @if($hasChildren) @click="isExpanded = true" @endif
                    class="flex-1 text-left py-2 pr-2 text-xs truncate cursor-pointer flex items-center justify-between">
                <span class="truncate" title="{{ $folder->name }}">
                    @if($level > 0)
                        <span class="text-slate-400 font-semibold select-none text-[11px] mr-1">↳</span>
                    @endif
                    {{ $folder->name }}
                </span>
                <span class="text-[10px] tabular-nums font-mono px-1.5 py-0.5 rounded ml-1 {{ $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                    {{ $folder->documents()->count() }}
                </span>
            </button>
        </div>

        @if($canManageFlexibility && !$hasChildren)
            <div class="hidden group-hover:flex items-center gap-0.5 pr-2">
                <button type="button"
                        wire:click.stop="openMoveFolderModal({{ $folder->id }})"
                        class="p-1 rounded text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition-colors cursor-pointer"
                        title="Pindahkan Hierarki Folder">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </button>
                <button type="button"
                        wire:click.stop="confirmDeleteFolder({{ $folder->id }})"
                        class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                        title="Hapus Folder">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <!-- Recursive Sub-folders -->
    @if($hasChildren)
        <div x-show="isExpanded"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="flex flex-col pl-3 space-y-0.5 border-l-2 border-slate-200 ml-4 mt-0.5">
            @foreach($folder->children as $child)
                @include('livewire.library.folder-item', ['folder' => $child, 'level' => $level + 1, 'expandedFolderIds' => $expandedFolderIds])
            @endforeach
        </div>
    @endif
</div>
