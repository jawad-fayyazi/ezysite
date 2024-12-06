<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\PrivateTemplate;
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('websites.edit'); // Name the route

new class extends Component implements HasForms {
    use InteractsWithForms;

    public $project_id; // The project_id from the URL
    public Project $project;  // Holds the project instance
    public ?array $data = []; // Holds form data

    // Mount method to set the project_id from the URL and fetch the project
    public function mount($project_id): void
    {
        $this->project_id = $project_id; // Set the project_id dynamically from the URL

        // Retrieve the project using the project_id and authenticate it
        $this->project = auth()->user()->projects()->where('project_id', $this->project_id)->firstOrFail();

        // Pre-fill the form with existing project data
        $this->form->fill([
            'rename' => $this->project->project_name,
            'domain' => $this->project->domain,
            'description' => $this->project->description, // Pre-fill the description

        ]);
    }


    // Define the form schema
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('rename')
                    ->label('Rename Website')
                    ->placeholder('New Website Name')
                    ->maxLength(255),
                TextInput::make('domain')
                    ->label('Domain')
                    ->placeholder('New Domain Name')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Add or update the project description')
                    ->rows(5)
                    ->maxLength(1000), // Limit description length
            ])
            ->statePath('data');
    }



    // Save project as a private template
    public function saveAsPrivateTemplate(): void
    {
        // Create a new private template based on the project
        PrivateTemplate::create([
            'template_name' => $this->project->project_name . ' (Created from My Websites)',
            'description' => $this->project->description,
            'template_json' => $this->project->project_json,
            'user_id' => auth()->id(), // Associate with the logged-in user
        ]);

        Notification::make()
            ->success()
            ->title('Template created successfully')
            ->send();

        $this->redirect('/templates/my'); // Redirect to the user's private templates page
    }


    // Edit the project details
    public function edit(): void
    {
        $data = $this->form->getState();

        // Update project name if provided
        if (!empty($data['rename'])) {
            $this->project->update(['project_name' => $data['rename']]);
        }

        // Update domain if provided
        if (!empty($data['domain'])) {
            $this->project->update(['domain' => $data['domain']]);
        }

        // Update description if provided
        if (!empty($data['description'])) {
            $this->project->update(['description' => $data['description']]);
        }

        Notification::make()
            ->success()
            ->title('Website updated successfully')
            ->send();

        $this->redirect('/websites');
    }

    // Duplicate the project
    public function duplicate(): void
    {
        $newProject = $this->project->replicate();
        $newProject->project_name = $this->project->project_name . ' (Copy)';
        $newProject->save();

        Notification::make()
            ->success()
            ->title('Website duplicated successfully')
            ->send();

        $this->redirect('/websites');
    }

    // Delete the project
    public function delete(): void
    {
        $this->project->delete();

        Notification::make()
            ->success()
            ->title('Website deleted successfully')
            ->send();

        $this->redirect('/websites');
    }
}
?>

<x-layouts.app>
    @volt('websites.edit')
    <x-app.container>
    <div class="container mx-auto my-6">
    <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to Websites" :href="route('websites')" />

        <!-- Box with background, padding, and shadow -->    
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center justify-between mb-5">
            <!-- Display the current project name as a heading -->
            <x-app.heading title="Editing: {{ $this->project->project_name }}"
                description="Update the website name or domain below, or duplicate the website." :border="false" />
            <x-button tag="a"
                href="{{ route('builder', ['project_id' => $this->project->project_id, 'project_name' => $this->project->project_name]) }}"
                target="_blank">
                Edit in Builder
            </x-button>
        </div>
        <form wire:submit="edit" class="space-y-6">
            <!-- Form Fields -->
            {{ $this->form }}
            <div class="flex justify-end gap-x-3">
                <!-- Cancel Button -->
                <x-button tag="a" href="/websites" color="secondary">
                    Cancel
                </x-button>
            
                <!-- Highlight the primary action -->
                <x-button type="button" wire:click="edit" class="text-white bg-primary-600 hover:bg-primary-500">
                    Save Changes
                </x-button>
            
            <!-- Dropdown for additional actions -->
            <x-dropdown class="text-gray-500">
                <x-slot name="trigger">
                    <x-button type="button" color="gray">
                        More Actions
                    </x-button>
                </x-slot>
            
                <!-- Dropdown Items with Icons -->
            
                <!-- Duplicate Website -->
                <a href="#" wire:click="duplicate" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                    <x-icon name="phosphor-copy" class="w-4 h-4 mr-2" /> Duplicate Website
                </a>
            
                <!-- Save as My Template -->
                <a href="#" wire:click="saveAsPrivateTemplate"
                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                    <x-icon name="phosphor-star" class="w-4 h-4 mr-2" /> Save as My Template
                </a>
            
                <!-- Delete Website -->
                <a href="#" wire:click="delete" class="block px-4 py-2 text-red-600 hover:bg-gray-100 flex items-center">
                    <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Website
                </a>
            </x-dropdown>

            </div>

            </form>
        </div>
    </div>
    </x-app.container>
    @endvolt
</x-layouts.app>