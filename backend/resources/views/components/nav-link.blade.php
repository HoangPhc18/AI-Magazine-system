@props(['active' => false, 'href' => '#'])

@php
$classes = ($active ?? false)
    ? 'border-indigo-400 text-gray-900 focus:border-indigo-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>