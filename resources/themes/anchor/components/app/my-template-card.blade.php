@props(['website'])
<div class="card overflow-hidden transition-transform duration-200 transform ease-in-out hover:-translate-y-1">


        <img src="https://placehold.co/300x200/fff/000?text={{ $website->template_name }}"
            alt="{{ $website->template_name }}" class="w-full h-40 object-cover rounded-lg mb-4 dark:hidden" />

        <img src="https://placehold.co/300x200/111827/fff?text={{ $website->template_name }}"
            alt="{{ $website->template_name }}" class="w-full h-40 object-cover rounded-lg mb-4 hidden dark:block" />

    <div class="space-y-2">
        <div class="flex justify-between items-start">
            <h3 class="text-lg font-semibold">{{ $website->template_name }}</h3>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ Str::limit($website->description, 100, '...') }}
        </p>


        <div class="flex justify-between items-center pt-4">
            <a href="/templates/my/{{$website->id}}" class="btn btn-outline py-2 px-4">
                Use Template
                <x-icon name="phosphor-arrow-right" class="h-4 w-4 ml-2 " />
            </a>
                <button wire:click="deleteTemplate({{$website->id}})" wire:confirm="Are you sure you want to delete this template?" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <x-icon name="phosphor-trash" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                </button>
        </div>
    </div>
</div>