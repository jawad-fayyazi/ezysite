<?php

use Livewire\Volt\Component;
use App\Models\Template;
use App\Models\TemplateCategory; // Include TemplateCategory model
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('templates.category'); // Name the route

new class extends Component {
    public $template_category_id; // Category ID from URL
    public $category_name; // Category name 
    public $templates; // Templates in the category

    public function mount($template_category_id): void
    {
        $this->template_category_id = $template_category_id; // Get the category name dynamically from the URL
        // Fetch templates based on the category
        // Fetch the category name based on the ID
        $category = TemplateCategory::find($this->template_category_id);

        // Check if the category exists
        if (!$category) {
            abort(404); // If no category found, return 404
        }

        $this->category_name = $category->name; // Set the category name for display

        // Fetch templates based on the category ID (template_category_id)
        $this->templates = Template::where('template_category_id', $this->template_category_id)
            ->where('is_publish', true)  // Added condition for is_publish
            ->orderBy('template_id', 'desc')
            ->get();
    }
};
?>

<x-layouts.app>
    @volt('templates.category')
    <x-app.container>
        <div class="container mx-auto my-6">
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to Categories" :href="route('starter')" />
            <div class="bg-white p-6 rounded-lg shadow-lg">            
            <!-- Page Header -->
            <div class="flex items-center justify-between mb-5">
                <x-app.heading title="Starter Templates in {{ ucwords($category_name) }}"
                    description="Browse all starter templates in the {{ ucwords($category_name) }} category." :border="false" />
@if(Gate::allows('create-template')) <!-- Check if the user can create a template -->
    <x-button tag="a" :href="route('templates.create')">New Template</x-button>
@else
    <x-button tag="a" :href="route('websites.create')">New Website</x-button>
@endif
            </div>
            <!-- Check if there are no templates -->
            @if($templates->isEmpty())
                <p class="text-gray-600">Looks like there are no Starter Templates in the "{{ ucwords($category_name) }}" category.</p>
            @else
                            <!-- Templates Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                            @foreach($templates as $template)
                                                <div class="relative block bg-gray-100 p-4 rounded-lg shadow transition-all duration-300"
                                                    style="transform: scale(1); transition: transform 0.3s, box-shadow 0.3s;"
                                                    onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0px 4px 20px rgba(0, 0, 0, 0.2)';"
                                                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0px 4px 10px rgba(0, 0, 0, 0.1)';"
                                                    onclick="window.location.href='create/{{ $template->template_id }}';">
                                                    <!-- Prevent Default Click on x-button -->
                                                    <a href="create/{{ $template->template_id }}" class="absolute inset-0 z-0"></a>

                                                    <!-- Template Name -->
                                                    <div class="text-center">
                                                        <h3 class="text-lg font-bold text-gray-700">{{ $template->template_name }}</h3>
                                                    </div>

                                                    <!-- Template Image -->
                                                    <div class="mt-4">
                                                        <img src="{{ asset('storage/templates_ss/screenshots/' . $template->template_id . '.png') }}" alt="{{ $template->template_name }}" alt="Template Image" class="w-full rounded-md shadow">
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
                                                        @if(Gate::allows('create-template')) <!-- Check if the user can create a template -->
                                <x-button tag="a" href="/templates/starter/edit/{{$template->template_id}}">Edit Template</x-button>
                                @endif

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