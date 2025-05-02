@props([
    'striped' => false,
    'hover' => true
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-gray-200']) }}>
        @isset($header)
        <thead class="bg-gray-50">
            {{ $header }}
        </thead>
        @endisset
        
        <tbody class="bg-white divide-y divide-gray-200 {{ $striped ? 'divide-y-0' : '' }}">
            @if(isset($body))
                {{ $body }}
            @else
                {{ $slot }}
            @endif
        </tbody>
        
        @isset($footer)
        <tfoot class="bg-gray-50 border-t border-gray-200">
            {{ $footer }}
        </tfoot>
        @endisset
    </table>
</div>

@if($hover)
<style>
    .hover-row:hover {
        background-color: #f9fafb;
    }
</style>
@endif 