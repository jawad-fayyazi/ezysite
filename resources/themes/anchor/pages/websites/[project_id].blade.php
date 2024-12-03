<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\Project;
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
            ])
            ->statePath('data');
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
                <x-button tag="a" href="/websites" color="secondary">Cancel</x-button>
                <x-button type="button" wire:click="duplicate" color="gray">
                    Duplicate Website
                </x-button>
                <x-button type="button" wire:click="edit" class="text-white bg-primary-600 hover:bg-primary-500">
                    Save Changes
                </x-button>
                <!-- Delete Button -->
                <x-button type="button" wire:click="delete" color="danger">
                    Delete Website
                </x-button>
            </div>
            </form>
        </div>
    </div>
    </x-app.container>
    @endvolt
</x-layouts.app>