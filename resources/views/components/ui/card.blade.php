@props([
    'padding' => 'p-5',
])

<div {{ $attributes->merge(['class' => 'bg-[var(--color-surface)] border border-[var(--color-border)] rounded-[var(--radius-lg)] shadow-xs ' . $padding]) }}>
    {{ $slot }}
</div>
