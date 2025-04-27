<!-- resources/views/components/logo.blade.php -->
<div class="flex items-center space-x-2">
    <svg xmlns="http://www.w3.org/2000/svg" 
        @class([
            'text-white',
            'h-5 w-5' => $size === 'sm',
            'h-7 w-7' => $size === 'md',
            'h-9 w-9' => $size === 'lg',
        ])
        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        <rect x="7" y="7" width="3" height="3"></rect>
        <rect x="14" y="7" width="3" height="3"></rect>
        <rect x="7" y="14" width="3" height="3"></rect>
        <rect x="14" y="14" width="3" height="3"></rect>
    </svg>
    <span @class([
        'font-bold text-white',
        'text-sm' => $size === 'sm',
        'text-xl' => $size === 'md',
        'text-2xl' => $size === 'lg',
    ])>Cool QR Generator</span>
</div>