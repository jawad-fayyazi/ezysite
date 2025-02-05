@props([
    'title' => '',
    'description' => '',
    'border' => true
])
<div class="@if($border){{ 'pb-5 border-b border-gray-200 dark:border-gray-800' }}@endif mb-8">
    <h1 class="text-3xl font-bold mb-2 ">{{ $title ?? '' }}</h1>
    <p class="text-gray-600 dark:text-gray-400">{{ $description ?? '' }}</p>
</div>