<?php
$user = auth()->user();
$valueBytes = $user->convertToBytes($value);
$maxBytes = $user->convertToBytes($max);

$percentage = ($valueBytes / $maxBytes) * 100;

?>

<div class="rounded-xl bg-white p-6 shadow-lg dark:bg-gray-800 transform transition-all duration-200 ease-in-out" 
    onmouseover="this.style.transform='translateY(-5px)'" 
    onmouseout="this.style.transform='translateY(0)'">
    
    <div class="flex items-center space-x-4">
        <div class="p-3 rounded-lg bg-primary-100 dark:bg-primary-900">
            <x-icon name="{{ $icon }}" class="h-6 w-6 text-primary-600 dark:text-primary-400"/>
        </div>

        <div class="flex-1">
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $title }}</p>

            <div class="flex items-end gap-1">
                <p class="text-2xl font-bold text-black dark:text-white">{{ $value }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">/ {{ $max }}</p>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-1">
                <div class="h-full rounded-full {{ $progressColor }}" style="width: {{ $percentage }}%"></div>
            </div>
        </div>
    </div>
</div>