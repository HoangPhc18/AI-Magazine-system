@props([
    'title', 
    'description' => null,
    'actions' => null
])

<div class="sm:flex sm:items-center sm:justify-between mb-6">
    <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
        @if($description)
            <p class="mt-2 text-sm text-gray-600">{{ $description }}</p>
        @endif
    </div>
    
    @if($actions)
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            {{ $actions }}
        </div>
    @endif
</div> 