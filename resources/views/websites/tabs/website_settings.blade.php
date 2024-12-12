<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\PrivateTemplate;
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('websites.tabs.website_settings'); // Name the route

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
            'description' => $this->project->description, // Pre-fill the description
            'robots_txt' => $this->project->robots_txt, // Add robots.txt field
            'header_embed' => $this->project->header_embed, // Add embed code for header
            'footer_embed' => $this->project->footer_embed, // Add embed code for footer
        ]);
    }


    // Define the form schema
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Rename Website Field
                TextInput::make('rename')
                    ->label('Website Name')
                    ->placeholder('Enter Website Name')
                    ->maxLength(255),

                // Description Textarea
                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Describe your website')
                    ->rows(5)
                    ->maxLength(1000),

                // Logo Uploader
                FileUpload::make('logo')
                    ->label('Upload Logo')
                    ->image()
                    ->directory("usersites/{$this->project->project_id}")
                    ->disk('public')
                    ->maxSize(1024)
                    ->helperText('Upload a logo for your website'),

                // Robots.txt Textarea
                Textarea::make('robots_txt')
                    ->label('Edit Robots.txt')
                    ->placeholder('Add or modify the Robots.txt content')
                    ->rows(5),

                // Embed Code for Header
                Textarea::make('header_embed')
                    ->label('Embed Code in Header')
                    ->placeholder('Add custom embed code for the header')
                    ->rows(5),

                // Embed Code for Footer
                Textarea::make('footer_embed')
                    ->label('Embed Code in Footer')
                    ->placeholder('Add custom embed code for the footer')
                    ->rows(5),
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

        // Update project details
        $this->project->update([
            'project_name' => $data['rename'],
            'description' => $data['description'],
            'robots_txt' => $data['robots_txt'], // Update robots.txt
            'header_embed' => $data['header_embed'], // Update header embed code
            'footer_embed' => $data['footer_embed'], // Update footer embed code
        ]);

        $logo = $this->data['logo'] ?? null;

        if ($logo) {
            // The logo is in an array, extract the file
            $file = reset($logo); // Get the first value from the array
            $newPath = "usersites/{$this->project->project_id}/logo/{$this->project->project_id}." . pathinfo($file, PATHINFO_EXTENSION);

            if (Storage::disk('public')->exists($file)) {
                try {
                    // Use storeAs to save the file on the public disk
                    Storage::disk('public')->move($file, $newPath);
                    Storage::disk('public')->delete($file);
                } catch (\Exception $e) {
                    Notification::make()
                        ->danger()
                        ->title('Upload Error')
                        ->body("Error: {$e->getMessage()}")
                        ->send();
                }
            }
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


@volt('websites.tabs.webistes_settings')
    <form wire:submit="edit" class="space-y-6">
        <h2 class="text-2xl font-semibold">Website Settings</h2>

        <!-- Render the form fields here -->
        {{ $this->form }}

        <div class="flex justify-end gap-x-3">
            <!-- Cancel Button -->
            <x-button tag="a" href="/websites" color="secondary">Cancel</x-button>

            <!-- Save Changes Button -->
            <x-button type="button" wire:click="edit" class="text-white bg-primary-600 hover:bg-primary-500">Save
                Changes</x-button>

            <!-- Dropdown for More Actions -->
            <x-dropdown class="text-gray-500">
                <x-slot name="trigger">
                    <x-button type="button" color="gray">More Actions</x-button>
                </x-slot>

                <!-- Duplicate Website -->
                <a href="#" wire:click="duplicate"
                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                    <x-icon name="phosphor-copy" class="w-4 h-4 mr-2" /> Duplicate Website
                </a>

                <!-- Save as My Template -->
                <a href="#" wire:click="saveAsPrivateTemplate"
                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                    <x-icon name="phosphor-star" class="w-4 h-4 mr-2" /> Save as My Template
                </a>

                <!-- Delete Website -->
                <a href="#" wire:click="delete"
                    class="block px-4 py-2 text-red-600 hover:bg-gray-100 flex items-center">
                    <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Website
                </a>
            </x-dropdown>
        </div>
    </form>
@endvolt