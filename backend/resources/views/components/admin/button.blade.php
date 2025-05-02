@props([
    'type' => 'button',
    'color' => 'primary',
    'size' => 'md',
    'disabled' => false,
    'href' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false
])

@php
    $baseClasses = 'inline-flex items-center justify-center rounded-md focus:outline-none transition-colors duration-200';
    
    $colors = [
        'primary' => 'bg-primary-600 hover:bg-primary-700 text-white',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white',
        'success' => 'bg-green-600 hover:bg-green-700 text-white',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white',
        'warning' => 'bg-yellow-500 hover:bg-yellow-600 text-white',
        'info' => 'bg-blue-500 hover:bg-blue-600 text-white',
        'light' => 'bg-gray-200 hover:bg-gray-300 text-gray-800',
        'dark' => 'bg-gray-800 hover:bg-gray-900 text-white',
        'link' => 'bg-transparent hover:bg-gray-100 text-primary-600',
        'outline-primary' => 'bg-transparent hover:bg-primary-50 text-primary-600 border border-primary-600',
        'outline-secondary' => 'bg-transparent hover:bg-gray-50 text-gray-600 border border-gray-600',
        'outline-danger' => 'bg-transparent hover:bg-red-50 text-red-600 border border-red-600',
    ];
    
    $sizes = [
        'xs' => 'px-2.5 py-1.5 text-xs',
        'sm' => 'px-3 py-2 text-sm leading-4',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-4 py-2 text-base',
        'xl' => 'px-6 py-3 text-base',
    ];
    
    $disabledClasses = 'opacity-50 cursor-not-allowed';
    
    $classes = $baseClasses . ' ' . 
               ($colors[$color] ?? $colors['primary']) . ' ' . 
               ($sizes[$size] ?? $sizes['md']) . ' ' . 
               ($disabled ? $disabledClasses : '');
@endphp

@if ($href && !$disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <span class="mr-2">
                {!! $icon !!}
            </span>
        @endif
        
        @if ($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif
        
        {{ $slot }}
        
        @if ($icon && $iconPosition === 'right')
            <span class="ml-2">
                {!! $icon !!}
            </span>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <span class="mr-2">
                {!! $icon !!}
            </span>
        @endif
        
        @if ($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        @endif
        
        {{ $slot }}
        
        @if ($icon && $iconPosition === 'right')
            <span class="ml-2">
                {!! $icon !!}
            </span>
        @endif
    </button>
@endif 