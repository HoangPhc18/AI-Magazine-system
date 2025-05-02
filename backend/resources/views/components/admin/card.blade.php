@props([
    'header' => null,
    'footer' => null,
    'noPadding' => false,
    'hover' => false
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-gray-200 overflow-hidden transition-all duration-200 ' . ($hover ? 'hover:shadow-lg transform hover:-translate-y-1' : 'shadow-sm')]) }}>
    @if($header)
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 bg-gray-50">
            {{ $header }}
        </div>
    @endif
    
    <div class="{{ $noPadding ? '' : 'px-4 py-5 sm:p-6' }}">
        {{ $slot }}
    </div>
    
    @if($footer)
        <div class="px-4 py-4 sm:px-6 bg-gray-50 border-t border-gray-200">
            {{ $footer }}
        </div>
    @endif
</div> 