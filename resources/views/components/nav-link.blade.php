@props(['active', 'loading' => false])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-emerald-500 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-emerald-700 transition duration-200 ease-in-out relative'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-200 ease-in-out relative';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <!-- Loading spinner -->
    @if($loading)
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-emerald-500"></div>
        </div>
        <span class="opacity-0">{{ $slot }}</span>
    @else
        {{ $slot }}
    @endif
</a>
