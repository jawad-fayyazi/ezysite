<?php

if(isset($max)){
$user = auth()->user();
$valueBytes = $user->convertToBytes($value);
$maxBytes = $user->convertToBytes($max);

$percentage = ($valueBytes / $maxBytes) * 100;
}
?>

<div class="card transform transition-all duration-200 ease-in-out hover:-translate-y-1" >
    
    <div class="flex items-center space-x-4">
        <div class="p-3 rounded-lg bg-primary-100 dark:bg-primary-900">
            <x-icon name="{{ $icon }}" class="h-6 w-6 text-primary-600 dark:text-primary-400"/>
        </div>

        <div class="flex-1">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $title }}</p>


        @if(isset($max))
            <div class="flex items-end gap-1">
                <p class="text-2xl font-bold">{{ $value }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">/ {{ $max }}</p>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-1 overflow-hidden">
                <div class="h-full {{ $progressColor }} rounded-full" style="width: {{ $percentage }}%;">
                </div>
            </div>
        @else
            <div class="flex items-end gap-1">
                <p class="text-2xl font-bold">{{ $value }}</p>
            </div>
        @endif

        </div>
    </div>
</div>