<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\Template;
use App\Models\TemplateCategory; // Import TemplateCategory model
use Illuminate\Support\Facades\Storage;
use function Laravel\Folio\{middleware, name};
use Illuminate\Support\Facades\File;


middleware('auth');
name('templates.create');

new class extends Component implements HasForms {
    use Filament\Forms\Concerns\InteractsWithForms;

    public ?array $data = [];
    public $new_category = null;

    public function mount(): void
    {
        // Initialize form state
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Existing Category Dropdown (Optional)
                Select::make('template_category')
                    ->label('Existing Category')
                    ->options(function () {
                        return TemplateCategory::select('id', 'name')  // Changed to use TemplateCategory model
                            ->pluck('name', 'id') // Plucking name and id for the dropdown options
                            ->toArray();
                    })
                    ->nullable()  // Make the existing category optional
                    ->reactive() // Make it reactive to handle new category
                    ->afterStateUpdated(function ($state, $set) {
                        $set('new_category', null); // Clear the new category when an existing one is selected
                    }),

                // New Category Input (Optional)
                TextInput::make('new_category')
                    ->label('New Category')
                    ->placeholder('Enter a new category if needed')
                    ->nullable() // Allow it to be empty if they choose an existing category
                    ->reactive(),

                    TextInput::make('template_name')
                        ->label('Template Name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->maxLength(1000),

                    FileUpload::make('template_image')
                        ->label('Template Screenshot')
                        ->image()
                        ->required()                        
                        ->directory('templates_ss/screenshots')
                        ->disk('public'), // Ensure file is saved to the public disk

                    TextInput::make('preview_link')
                        ->label('Preview Link')
                        ->required()
                        ->url(),

                    Textarea::make('template_json')
                        ->label('Template JSON')
                        ->required()
                        ->rows(10)
                        ->placeholder('Add your template JSON structure here'),
                ])
            ->statePath('data');
    }

    public function create(): void
    {

        // Determine the category: Use new category if provided, otherwise use selected category
        $categoryId = $this->data['new_category'] ? TemplateCategory::create(['name' => $this->data['new_category']])->id : $this->data['template_category'];
        
        // If both are empty, set a default category or handle the error
        if (!$categoryId) {
            Notification::make()
                ->danger()
                ->title('Category Error')
                ->body('You must provide either an existing or a new category.')
                ->send();
            return;
        }

        // Create a new template entry
        $template = Template::create([
            'template_name' => $this->data['template_name'],
            'template_category_id' => $categoryId,
            'template_description' => $this->data['description'],
            'template_preview_link' => $this->data['preview_link'],
            'template_json' => json_encode($this->data['template_json']),
        ]);

        // Handle image upload
        $image = $this->data['template_image'] ?? null;

        if ($image) {
            // The image is in an array, extract the file
            $file = reset($image); // Get the first value from the array

            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                try {
                    // Use storeAs to save the file on the public disk
                    $file->storeAs('templates_ss/screenshots', "{$template->template_id}.png", 'public');
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
            ->title('Template created successfully')
            ->send();

        $this->redirect('/templates/starter');
    }
}
    ?>

<x-layouts.app>
    @volt('templates.create')
    <x-app.container>
        <div class="container mx-auto my-6">

            <!-- Back Button -->
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to Starter Templates" href="/templates/starter" />

            <!-- Template Creation Form -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center justify-between mb-5">
                    <!-- Heading: Template Creation -->
                    <x-app.heading title="Create New Template" description="Fill out the form to create a new template."
                        :border="false" />
                </div>

                <!-- Template Creation Form -->
                <form wire:submit="create" class="space-y-6">
                    <!-- Form Fields -->
                    {{ $this->form }}

                    <div class="flex justify-end gap-x-3">
                        <x-button tag="a" href="/templates/starter" color="secondary">Cancel</x-button>
                        <x-button type="submit" class="text-white bg-primary-600 hover:bg-primary-500">
                            Create Template
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>