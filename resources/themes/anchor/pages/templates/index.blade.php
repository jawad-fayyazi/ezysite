<?php
use function Laravel\Folio\{middleware, name};
use App\Models\Template;
use Livewire\Volt\Component;
middleware('auth');
name('templates');

new class extends Component {
    public $templates;
    public $ourDomain = '.template.wpengineers.com';

    public function mount()
    {
        // Check if the user is an admin
        if (Gate::allows('create-template')) {
            // Admins can view all templates in the category
            $this->templates = Template::orderBy('template_id', 'desc')
                ->get();
        } else {
            // Regular users can only view published templates
            $this->templates = Template::where('is_publish', true)
                ->orderBy('template_name', 'asc')
                ->get();

        }

    }

}
    ?>

<x-layouts.app>
    @volt('templates')
    <x-app.container x-data class="lg:space-y-6" x-cloak>

        <x-app.heading title="Templates" description="Manage all your websites in one place" :border="false" />

        <div x-data="{ searchQuery: '' }">
            <div class="flex flex-col md:flex-row justify-between gap-4 mb-8">
                {{-- Search Bar --}}
                <div class="flex-1">
                    <div class="relative">
                        <x-icon name="phosphor-magnifying-glass"
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input type="text" placeholder="Search templates..." class="input pl-10" x-model="searchQuery" />
                    </div>
                </div>

                {{-- Create New Website Button --}}
                <div class="flex gap-4">
                    <a href="{{ route('websites.create') }}" class="btn btn-primary whitespace-nowrap">
                        <x-icon name="phosphor-plus" class="h-5 w-5 mr-2" />
                        Create New Website
                    </a>
                </div>
            </div>

            @if($templates->isEmpty())
            <h3 class="text-lg font-semibold">Looks like you dont have any website. Create your first Website now</h3>
            @else

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($templates as $web)
                <div x-show="
    searchQuery === '' ||
    {{ json_encode($web->template_name) }}.toLowerCase().includes(searchQuery.toLowerCase()) ||
    {{ json_encode($web->description ?? '') }}.toLowerCase().includes(searchQuery.toLowerCase()) ||
    {{ json_encode($web->domain . $this->ourDomain ?? '') }}.toLowerCase().includes(searchQuery.toLowerCase())
"> <x-app.template-card :website="$web" />
                </div>
                @endforeach
            </div>
            @endif
        </div>



    </x-app.container>
    @endvolt
</x-layouts.app>