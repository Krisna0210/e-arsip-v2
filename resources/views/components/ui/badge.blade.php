@props([
    'status' => 'draft', // draft, pending, review, approved, rejected, disposed
])

@php
    $baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border';
    
    // Warna sesuai dengan desain di DESIGN.md
    $statuses = [
        'draft' => 'bg-[#F8FAFC] text-[#475569] border-[#CBD5E1]',
        'pending' => 'bg-[#EFF6FF] text-[#1D4ED8] border-[#BFDBFE]',
        'review' => 'bg-[#FFFBEB] text-[#B45309] border-[#FDE68A]',
        'approved' => 'bg-[#ECFDF5] text-[#047857] border-[#A7F3D0]',
        'rejected' => 'bg-[#FEF2F2] text-[#DC2626] border-[#FECACA]',
        'disposed' => 'bg-[#FAF5FF] text-[#7E22CE] border-[#E9D5FF]',
    ];
    
    $classes = $baseClasses . ' ' . ($statuses[$status] ?? $statuses['draft']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
