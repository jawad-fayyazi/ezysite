<?php

use Livewire\Volt\Component;
use App\Models\Template;
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('templates.category'); // Name the route

new class extends Component {
    public $template_category; // Category name from URL
    public $templates; // Templates in the category

    public function mount($template_category): void
    {
        $this->template_category = $template_category; // Get the category name dynamically from the URL
        // Fetch templates based on the category
        $this->templates = Template::where('template_category', $this->template_category)->orderBy('template_id', 'desc')->get();
    }
};
?>

<x-layouts.app>
    @volt('templates.category')
    <x-app.container>
        <div class="container mx-auto my-6">
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to Categories" :href="route('templates')" />
            <div class="bg-white p-6 rounded-lg shadow-lg">            
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-5">
                <x-app.heading title="Templates in {{ ucwords($template_category) }}"
                    description="Browse all templates in the {{ ucwords($template_category) }} category." :border="false" />
                    <x-button tag="a" :href="route('websites.create')">New Website</x-button>
            </div>

            <!-- Check if templates are empty -->
            @if($templates->isEmpty())
                <p class="text-gray-600">No templates found in this category. Try another category!</p>
            @else
                <!-- Templates Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                @foreach($templates as $template)
                    <div class="relative block bg-gray-100 p-4 rounded-lg shadow transition-all duration-300"
                        style="transform: scale(1); transition: transform 0.3s, box-shadow 0.3s;"
                        onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0px 4px 20px rgba(0, 0, 0, 0.2)';"
                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0px 4px 10px rgba(0, 0, 0, 0.1)';"
                        onclick="window.location.href='/create/{{ $template->template_id }}';">
                        <!-- Prevent Default Click on x-button -->
                        <a href="/create/{{ $template->template_id }}" class="absolute inset-0 z-0"></a>

                        <!-- Template Name -->
                        <div class="text-center">
                            <h3 class="text-lg font-bold text-gray-700">{{ $template->template_name }}</h3>
                        </div>

                        <!-- Template Image -->
                        <div class="mt-4">
                            <img src="{{ asset('templates_ss/screenshots/' . $template->template_image . '.png') }}" alt="{{ $template->template_name }}" alt="Template Image" class="w-full rounded-md shadow">
                        </div>

                        <!-- Template Description -->
                        <p class="mt-4 text-sm text-gray-500">
                            {{ Str::limit($template->template_description, 100, '...') }}
                        </p>

                        <!-- Preview Button Inside Card (Separate from the Link) -->
                        <div class="mt-4 text-center">
                            <x-button tag="a" href="{{ $template->template_preview_link }}" target="_blank" color="primary"
                                onclick="event.stopPropagation();">
                                Preview
                            </x-button>
                        </div>
                    </div>
                @endforeach


                </div>
            @endif
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>