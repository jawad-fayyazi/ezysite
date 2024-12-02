<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\Project;
use function Laravel\Folio\{middleware, name};
middleware('auth');
name('websites.edit');

new class extends Component implements HasForms {
    use InteractsWithForms;

    public ?array $data = [];
    public Project $project;

    // Mount the component and get the project based on project_id from the URL
    public function mount($project_id): void
    {
        // Fetch the project using the provided project_id from the URL
        $this->project = Project::findOrFail($project_id);

        // Pre-fill the form with the project data
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
                    ->label('Rename Project')
                    ->placeholder('New Project Name')
                    ->maxLength(255),
                TextInput::make('domain')
                    ->label('Domain')
                    ->placeholder('New Domain Name')
                    ->maxLength(255),
            ])
            ->statePath('data');
    }

    // Handle the project update
    public function edit(): void
    {
        $data = $this->form->getState();

        // Update project name if provided
        if (!empty($data['rename'])) {
            $this->project->update(['project_name' => $data['rename']]);
        }

        Notification::make()
            ->success()
            ->title('Website updated successfully')
            ->send();

        // Redirect to the projects page
        $this->redirect('/websites');
    }

    // Handle the project duplication
    public function duplicate(): void
    {
        // Duplicate the current project and append '(Copy)' to the project name
        $newProject = $this->project->replicate();
        $newProject->project_name = $this->project->project_name . ' (Copy)';
        $newProject->save();

        Notification::make()
            ->success()
            ->title('Website duplicated successfully')
            ->send();

        $this->redirect('/websites');
    }
}
?>


<x-layouts.app>
    @volt('websites.edit')
    <x-app.container class="max-w-xl">
        <div class="flex items-center justify-between mb-5">
            <!-- Display the current project name as a heading -->
            <x-app.heading title="Editing: {{ $project->project_name }}"
                description="Update the project name or domain below, or duplicate the project." :border="false" />
        </div>
        <form wire:submit="edit" class="space-y-6">
            {{ $this->form }}
            <div class="flex justify-end gap-x-3">
                <x-button tag="a" href="/websites" color="secondary">Cancel</x-button>
                <x-button type="button" wire:click="duplicate" color="gray">
                    Duplicate Website
                </x-button>
                <x-button type="submit" class="text-white bg-primary-600 hover:bg-primary-500">
                    Save Changes
                </x-button>
            </div>
        </form>
    </x-app.container>
    @endvolt
</x-layouts.app>