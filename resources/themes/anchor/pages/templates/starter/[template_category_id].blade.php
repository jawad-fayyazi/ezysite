<?php

use Livewire\Volt\Component;
use App\Models\Template;
use Filament\Notifications\Notification;
use App\Models\TemplateCategory; // Include TemplateCategory model
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('templates.category'); // Name the route

new class extends Component {
    public $template_category_id; // Category ID from URL
    public $templates; // Templates in the category
    public $category;
    public $ourDomain = ".template.wpengineers.com";

    public function mount($template_category_id): void
    {
        $this->template_category_id = $template_category_id; // Get the category name dynamically from the URL
        // Fetch templates based on the category
        // Fetch the category name based on the ID
        $this->category = TemplateCategory::find($this->template_category_id);

        // Check if the category exists
        if (!$this->category) {
            abort(404); // If no category found, return 404
        }

        // Check if the user is an admin
        if (Gate::allows('create-template')) {
            // Admins can view all templates in the category
            $this->templates = Template::where('template_category_id', $this->template_category_id)
                ->orderBy('template_id', 'desc')
                ->get();
        } else {
            abort(404);
            // Regular users can only view published templates
            $this->templates = Template::where('template_category_id', $this->template_category_id)
                ->where('is_publish', true)
                ->orderBy('template_id', 'desc')
                ->get();
                if($this->templates->isEmpty())
                {
                    abort(404);
                }
        }
    }



    public function delete()
    {
        if (!Gate::allows('create-template')) {
            abort(404);
        }

        if ($this->category) {
            // Perform deletion
            $this->category->delete();



            Notification::make()
                ->success()
                ->title('Category deleted successfully.')
                ->send();
            $this->redirect('/templates/starter');

        } else {
            Notification::make()
                ->danger()
                ->title('Category not found.')
                ->send();
            $this->redirect('/templates/starter');

        }
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
                <x-app.heading title="Starter Templates in {{ $this->category->name }}"
                    description="Browse all starter templates in the {{ $this->category->name }} category." :border="false" />
                    <div class="flex justify-end gap-x-3">
@if(Gate::allows('create-template')) <!-- Check if the user can create a template -->
    <x-button tag="a" :href="route('templates.create')">New Template</x-button>
    <x-button tag="button" wire:click="delete" wire:confirm="Are you sure you want to delete this category?" color="danger">Delete this category</x-button>
@else
    <x-button tag="a" :href="route('websites.create')">New Website</x-button>
@endif
                    </div>
            </div>
            <!-- Check if there are no templates -->
            @if($this->templates->isEmpty())
                <p class="text-gray-600">Looks like there are no Starter Templates in the "{{ $this->category->name }}" category.</p>
            @else
                <!-- Templates Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                @foreach($this->templates as $template)
                                    <div class="relative block bg-gray-100 p-4 rounded-lg shadow transition-all duration-300"
                                        style="transform: scale(1); transition: transform 0.3s, box-shadow 0.3s;"
                                        onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0px 4px 20px rgba(0, 0, 0, 0.2)';"
                                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0px 4px 10px rgba(0, 0, 0, 0.1)';"
                                        onclick="window.location.href='create/{{ $template->template_id }}';">
                                        <!-- Prevent Default Click on x-button -->
                                        <a href="create/{{ $template->template_id }}" class="absolute inset-0 z-0"></a>

                                        <!-- Template Name -->
                                        <div class="flex items-center justify-center space-x-2 group"
                                    @if(Gate::allows('create-template')) title="{{ $template->is_publish ? 'Template is public' : 'Template is not public' }}" @endif>
                                            @if($template->favicon)
                                                <img src="{{ asset('storage/templates/' . $template->template_id . '/logo/' . $template->favicon) }}" alt="Website Favicon"
                                                    class="w-6 h-6 rounded-full">
                                            @endif
                                            <h3 class="text-lg font-bold text-gray-700">{{ $template->template_name }}</h3>
                                            @if(Gate::allows('create-template'))
                                            <span class="w-3 h-3 rounded-full 
                                            {{ $template->is_publish ? 'bg-green-500' : 'bg-red-500' }}" style="display: inline-block;">
                                            </span>
                                            @endif
                                        </div>

                                        <!-- Template Image -->
                                        <div class="mt-4">
                                        @if($template->ss)
                                            <img src="{{ asset('storage/templates/' . $template->template_id . '/screenshot/' . $template->ss) }}"
                                                alt="{{ $template->template_name }}" class="w-full rounded-md shadow" />
                                        @endif
                                        </div>

                                        <!-- Template Description -->
                                        <p class="mt-4 text-sm text-gray-500">
                                            {{ Str::limit($template->template_description, 100, '...') }}
                                        </p>

                                        <!-- Preview Button Inside Card (Separate from the Link) -->
                                        <div class="mt-4 text-center space-x-2">
                                            @if($template->live)
                                            <x-button tag="a" href="{{ 'https://' . $template->domain . $this->ourDomain}}" target="_blank" color="primary"
                                                onclick="event.stopPropagation();">
                                                Preview
                                            </x-button>
                                            @endif
                                            @if(Gate::allows('create-template')) <!-- Check if the user can create a template -->
                    <x-button color="secondary" tag="a" href="/templates/starter/edit/{{$template->template_id}}">Edit Template</x-button>
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