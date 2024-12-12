<?php

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Volt\Component;
use App\Models\Project;
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('websites.tabs.overview'); // Name the route

new class extends Component implements HasForms {
    use InteractsWithForms;

    public $project_id; // The project_id from the URL
    public Project $project;  // Holds the project instance

    // Mount method to set the project_id from the URL and fetch the project
    public function mount($project_id): void
    {
        $this->project_id = $project_id; // Set the project_id dynamically from the URL

        // Retrieve the project using the project_id and authenticate it
        $this->project = auth()->user()->projects()->where('project_id', $this->project_id)->firstOrFail();

    }

}
    ?>
<x-layouts.app>
    @volt('websites.tabs.overview')
    <x-app.container>
        <div class="container mx-auto my-6">
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to Websites" :href="route('websites')" />

            <!-- Box with background, padding, and shadow -->
            <div class="bg-white p-6 rounded-lg shadow-lg">

                <!-- Overview Tab Content -->
                <div class="space-y-6">
                    <!-- Website Name -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold">{{ $this->project->project_name }}</h2>
                        <div class="text-sm text-gray-500">Project Name</div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <h3 class="text-lg font-medium">Description</h3>
                        <p class="text-gray-700">{{ $this->project->description ?? 'No description available' }}</p>
                    </div>

                    <!-- Domain or URL -->
                    <div class="space-y-2">
                        <h3 class="text-lg font-medium">Live Website</h3>
                        @if($this->project->domain)
                        <a href="{{ 'https://' . $this->project->domain . '.test.wpengineers.com' }}"
                            class="text-blue-600 hover:underline" target="_blank">{{ 'https://' . $this->project->domain
                            . '.test.wpengineers.com' }}</a>
                        @else
                        <p class="text-gray-500">No domain assigned</p>
                        @endif
                    </div>

                    <!-- Live Status Indicator -->
                    <div class="flex items-center">
                        <span class="text-sm font-medium mr-2">Status:</span>
                        @if($this->project->live)
                        <span class="text-green-500">Live</span>
                        @else
                        <span class="text-red-500">Not Live</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>