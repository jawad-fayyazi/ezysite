<?php
use function Laravel\Folio\{middleware, name};
use App\Models\Template;
use App\Models\TemplateCategory;
use Livewire\Volt\Component;
middleware('auth');
name('templates');

new class extends Component {
    public $templates;
    public $ourDomain = '.template.wpengineers.com';
    public $categories;


    public function mount()
    {
        // Check if the user is an admin
        if (Gate::allows('create-template')) {
            // Admins can view all templates in the category
            $this->templates = Template::orderBy('template_id', 'desc')
                ->get();
            $this->categories = TemplateCategory::orderBy('id')->get(); // Get all categories in order of their id

        } else {
            // Regular users can only view published templates
            $this->templates = Template::where('is_publish', true)
                ->orderBy('template_name', 'asc')
                ->get();
            $this->categories = TemplateCategory::whereHas('templates', function ($query) {
                // Ensure that there are templates and they are published
                $query->where('is_publish', 1);
            })
                ->orderBy('id')
                ->get();

        }

    }

}
    ?>

<x-layouts.app>
    @volt('templates')
    <x-app.container x-data class="lg:space-y-6" x-cloak>
        <div>
   <x-elements.back-button
        text="Back to Dashboard"
        :href="route('dashboard')"
        />
        <x-app.heading title="Templates" description="Choose from our collection of professional templates" :border="false" />
        </div>
        <div x-data="{ searchQuery: '', selectedCategory: ''  }">
            <div class="flex flex-col md:flex-row justify-between gap-4 mb-8">
                {{-- Search Bar --}}
                <div class="flex-1">
                    <div class="relative">
                        <x-icon name="phosphor-magnifying-glass"
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input type="text" placeholder="Search templates..." class="input pl-10" x-model="searchQuery" />
                    </div>
                </div>
                {{-- Category Filter --}}
        <div class="flex items-center space-x-2">
            <x-icon name="phosphor-funnel" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
            <!-- Bind the dropdown to Alpine's selectedCategory -->
            <select class="input pr-10 appearance-none" name="selectedCategory" id="selectedCategory" x-model="selectedCategory">
    <option value="">All Categories</option>
    @foreach ($categories as $category)
        <option value="{{ $category->name }}">
            {{ $category->name }}
        </option>
    @endforeach
</select>

        </div>


                {{-- Create New Website Button --}}
                <div class="flex gap-4">                 
                    @if(Gate::allows('create-template')) <!-- Check if the user can create a template -->
                    <a href="{{ route('templates.create') }}" class="btn btn-primary whitespace-nowrap">
                        <x-icon name="phosphor-plus" class="h-5 w-5 mr-2" />
                        Create New Template
                    </a>
                    @endif
                </div>
            </div>

            @if($templates->isEmpty())
            <h3 class="text-lg font-semibold">Looks like we dont have any templates available.</h3>
            @else

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($templates as $web)
                <div x-show="
                    (selectedCategory === '' || 
                        {{ json_encode(optional(\App\Models\TemplateCategory::find($web->template_category_id))->name ?? '') }}.toLowerCase() === selectedCategory.toLowerCase()
                    ) &&                    
    (searchQuery === '' ||
    {{ json_encode($web->template_name) }}.toLowerCase().includes(searchQuery.toLowerCase()) ||
    {{ json_encode($web->description ?? '') }}.toLowerCase().includes(searchQuery.toLowerCase()) ||
    {{ json_encode(\App\Models\TemplateCategory::find($web->template_category_id)?->name ?? '') }}.toLowerCase().includes(searchQuery.toLowerCase())
    )
"> <x-app.template-card :website="$web" />
                </div>
                @endforeach
            </div>
            @endif
        </div>



    </x-app.container>
    @endvolt
</x-layouts.app>