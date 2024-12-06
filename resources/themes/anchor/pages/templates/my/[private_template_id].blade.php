<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\PrivateTemplate;
use App\Models\Project; // Import Project model for creating a website
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('templates.edit'); // Name the route

new class extends Component implements HasForms {
    use InteractsWithForms;

    public $private_template_id; // The private_template_id from the URL
    public PrivateTemplate $template;  // Holds the template instance
    public ?array $data = []; // Holds form data

    // Mount method to set the template_id from the URL and fetch the template
    public function mount($private_template_id): void
    {
        $this->private_template_id = $private_template_id; // Set the private_template_id dynamically from the URL

        // Retrieve the template using the private_template_id and authenticate it
        $this->template = auth()->user()->privateTemplates()->where('id', $this->private_template_id)->firstOrFail();

        // Pre-fill the form with existing template data
        $this->form->fill([
            'template_name' => $this->template->template_name,
            'description' => $this->template->description,
        ]);
    }

    // Define the form schema
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('template_name')
                    ->label('Template Name')
                    ->placeholder('Enter Template Name')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Enter Template Description')
                    ->rows(5)
                    ->maxLength(1000), // Limit description length
            ])
            ->statePath('data');
    }

    // Save template changes
    public function save(): void
    {
        $data = $this->form->getState();

        // Update template name if provided
        if (!empty($data['template_name'])) {
            $this->template->update(['template_name' => $data['template_name']]);
        }

        // Update description if provided
        if (!empty($data['description'])) {
            $this->template->update(['description' => $data['description']]);
        }

        Notification::make()
            ->success()
            ->title('Template updated successfully')
            ->send();

        $this->redirect('/templates/my'); // Redirect to the user's private templates page
    }


    // Duplicate the template
    public function duplicate(): void
    {
        // Replicate the template
        $newTemplate = $this->template->replicate();
        $newTemplate->template_name = $this->template->template_name . ' (Copy)';
        $newTemplate->save();

        Notification::make()
            ->success()
            ->title('Template duplicated successfully')
            ->send();

        $this->redirect('/templates/my'); // Redirect to templates page after duplication
    }

    // Create a website from this template
    public function createWebsiteFromTemplate(): void
    {
        // Create a new project using the template
        $project = new Project();
        $project->project_name = $this->template->template_name . ' (Created from My Templates)'; // Use template name as project name
        $project->description = $this->template->description; // Use template description
        $project->project_json = $this->template->template_json; // Use template data for the project
        $project->user_id = auth()->id(); // Associate with the logged-in user
        $project->save();

        Notification::make()
            ->success()
            ->title('Website created successfully from template')
            ->send();

        $this->redirect("/websites"); // Redirect to the newly created website
    }


    // Delete the template
    public function delete(): void
    {
        $this->template->delete();

        Notification::make()
            ->success()
            ->title('Template deleted successfully')
            ->send();

        $this->redirect('/templates/my'); // Redirect to templates page after deletion
    }
}
?>

<x-layouts.app>
    @volt('templates.edit')
    <x-app.container>
    <div class="container mx-auto my-6">
    <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to My Templates" :href="route('my')" />

        <!-- Box with background, padding, and shadow -->    
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center justify-between mb-5">
            <!-- Display the current template name as a heading -->
            <x-app.heading title="Editing: {{ $this->template->template_name }}"
                description="Update the template name or description below, or delete the template." :border="false" />
                <x-button tag="button" wire:click="createWebsiteFromTemplate" color="primary">Create Website from Template</x-button>
        </div>
        <form wire:submit.prevent="save" class="space-y-6">
            <!-- Form Fields -->
            {{ $this->form }}
            <div class="flex justify-end gap-x-3">
    <!-- Cancel Button -->
    <x-button tag="a" href="/templates/my" color="secondary">
        Cancel
    </x-button>

        <!-- Save Changes Button -->
    <x-button type="button" wire:click="save" class="text-white bg-primary-600 hover:bg-primary-500">
        Save Changes
    </x-button>

    <!-- Dropdown for additional actions -->
    <x-dropdown class="text-gray-500">
        <x-slot name="trigger">
            <x-button type="button" color="gray">
                More Actions
            </x-button>
        </x-slot>

        <!-- Duplicate Template Action -->
        <a href="#" wire:click="duplicate" class="block px-4 py-2  hover:bg-gray-100 text-gray-700 flex items-center">
            <x-icon name="phosphor-copy" class="w-4 h-4 mr-2" /> Duplicate Template
        </a>
        <!-- Delete Template Action -->
        <a href="#" wire:click="delete" class="block px-4 py-2  hover:bg-gray-100 text-gray-700 text-red-600 flex items-center">
            <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Template
        </a>
    </x-dropdown>
</div>

        </form>
    </div>
    </div>
    </x-app.container>
    @endvolt
</x-layouts.app>
