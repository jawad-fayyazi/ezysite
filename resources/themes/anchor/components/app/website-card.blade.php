@props(['website'])

<?php
// // Remove special characters and split the name
// $cleanedName = preg_replace('/[^\w\s]/', '', $website->project_name); // Keep only alphanumeric and spaces
// $words = explode(' ', trim($cleanedName));

// // Get first letter of the first word and last word, if available
// $firstLetter = isset($words[0]) ? substr($words[0], 0, 1) : '';
// $lastLetter = isset($words[count($words) - 1]) ? substr($words[count($words) - 1], 0, 1) : '';

// // Combine initials, default to "NA" if no valid characters
// $website->placeholder_text = strtoupper($firstLetter . $lastLetter) ?: 'NA';


if (isset($website->domain)) {
    $website->domain = $website->domain . '.test.wpengineers.com';
}
?>

<div class="card overflow-hidden transition-transform duration-200 transform ease-in-out hover:-translate-y-1">
    <img src="https://placehold.co/300x200/fff/000?text={{ $website->project_name }}" alt="{{ $website->project_name }}"
        class="w-full h-40 object-cover rounded-lg mb-4 dark:hidden" />
    
    <img src="https://placehold.co/300x200/111827/fff?text={{ $website->project_name }}"
        alt="{{ $website->project_name }}" class="w-full h-40 object-cover rounded-lg mb-4 hidden dark:block" />

    <div class="space-y-2">
        <div class="flex justify-between items-start">
            <h3 class="text-lg font-semibold">{{ $website->project_name }}</h3>
            @if($website->live)
                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    Live
                </span>
            @else
                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                    Draft
                </span>
            @endif
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ Str::limit($website->description, 100, '...') }}
        </p>

        @if(isset($website->domain))
        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
            <x-icon name="phosphor-globe" class="h-4 w-4 mr-2" />
            {{$website->domain}}
        </div>
        @endif

        <p class="text-xs text-gray-500 dark:text-gray-500" x-data="{ formattedTime: '' }" x-init="
               const utcTime = '{{ \Carbon\Carbon::parse($website->updated_at)->toIso8601String() }}';
               const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
               formattedTime = new Date(utcTime).toLocaleString('en-US', { 
                   timeZone: userTimezone, 
                   month: 'short', 
                   day: 'numeric', 
                   year: 'numeric', 
                   hour: 'numeric', 
                   minute: 'numeric', 
                   hour12: true 
               });
           ">
            Last modified: <span x-text="formattedTime"></span>
        </p>

        <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{route('builder', ['project_id' => $website->project_id, 'project_name' => $website->project_name])}}" target="_blank" class="btn btn-outline py-2 px-4">
                Edit
                <x-icon name="phosphor-arrow-right" class="h-4 w-4 ml-2 " />
            </a>
            <a href='/websites/{{ $website->project_id }}' class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <x-icon name="phosphor-gear" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
            </a>
        </div>
    </div>
</div>
