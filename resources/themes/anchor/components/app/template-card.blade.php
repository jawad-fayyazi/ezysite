@props(['website'])


<?php

if (isset($website->domain)) {
    $website->domain = $website->domain . '.template.wpengineers.com';
}

?>
<div class="card overflow-hidden transition-transform duration-200 transform ease-in-out hover:-translate-y-1">
    
@if($website->ss)
    <img src="{{ asset('storage/templates/' . $website->template_id . '/screenshot/' . $website->ss) }}"
        alt="{{ $website->template_name }}" class="w-full h-40 object-cover rounded-lg mb-4" />
@else
    <img src="https://placehold.co/300x200/fff/000?text={{ $website->template_name }}" alt="{{ $website->template_name }}"
        class="w-full h-40 object-cover rounded-lg mb-4 dark:hidden" />

    <img src="https://placehold.co/300x200/111827/fff?text={{ $website->template_name }}" alt="{{ $website->template_name }}"
        class="w-full h-40 object-cover rounded-lg mb-4 hidden dark:block" />

@endif
    <div class="space-y-2">
        <div class="flex justify-between items-start">
            <h3 class="text-lg font-semibold">{{ $website->template_name }}</h3>
            @if(Gate::allows('create-template'))
                <span
                    class="px-2 py-1 text-xs rounded-full {{ $website->is_publish ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' }}">
                    {{ $website->is_publish ? 'Published' : 'Draft' }}
                </span>
            @endif
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ \App\Models\TemplateCategory::find($website->template_category_id)?->name ?? ''}}
        </p>


        <div class="flex justify-between items-center pt-4">
            <a href="/templates/starter/create/{{$website->template_id}}"
                class="btn btn-outline py-2 px-4">
                Use Template
                <x-icon name="phosphor-arrow-right" class="h-4 w-4 ml-2 " />
            </a>
            @if(Gate::allows('create-template')) <!-- Check if the user can create a template -->
                <a href="/templates/starter/edit/{{$website->template_id}}"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <x-icon name="phosphor-gear" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </a>
            @endif
            @if($website->live)
                <a href="{{ 'https://' . $website->domain}}" target="_blank"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <x-icon name="phosphor-eye" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                </a>
            @endif
        </div>
    </div>
</div>