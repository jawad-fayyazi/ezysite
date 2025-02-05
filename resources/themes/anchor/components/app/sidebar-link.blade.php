@props([
    'href' => '',
    'icon' => 'phosphor-house-duotone',
    'active' => false,
    'hideUntilGroupHover' => true,
    'target' => '_self',
    'ajax' => true
])

@php
    $isActive = filter_var($active, FILTER_VALIDATE_BOOLEAN);
@endphp

<a {{ $attributes }} href="{{ $href }}" @if((($href ?? false) && $target == '_self') && $ajax) wire:navigate @else @if($ajax) target="_blank" @endif @endif class="
@if($isActive)
{{ 'text-primary-600 shadow-sm bg-primary-50 font-medium dark:bg-primary-900 dark:bg-opacity-50 dark:text-primary-400' }}
@else
{{ 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}
@endif 
transition-colors px-2.5 py-2 flex rounded-lg w-full h-auto text-sm  justify-start items-center space-x-2 overflow-hidden group-hover:autoflow-auto items">
    <x-dynamic-component :component="$icon" class="flex-shrink-0 w-5 h-5" />
    <span class="flex-shrink-0 ease-out duration-50">{{ $slot }}</span>
</a>
