@props([
    'variant' => 'primary', // primary, secondary, subtle
    'type' => 'button',
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium px-4 py-2 rounded-lg text-sm transition-colors shadow-sm gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2';
    
    $variants = [
        'primary' => 'bg-[var(--color-primary)] text-[var(--color-text-inverse)] hover:bg-[var(--color-primary-hover)] focus:ring-[var(--color-primary)]',
        'secondary' => 'bg-[var(--color-surface)] border border-[var(--color-border)] text-[var(--color-text-primary)] hover:bg-slate-50 focus:ring-slate-200',
        'subtle' => 'bg-white border text-slate-700 border-slate-200 hover:bg-slate-50 focus:ring-slate-200',
    ];
    
    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
