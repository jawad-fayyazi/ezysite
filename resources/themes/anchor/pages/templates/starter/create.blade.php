<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Validator;
use Livewire\Volt\Component;
use App\Models\Template;
use App\Models\TemplateHeaderFooter;
use App\Models\TempPage;
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
    public $template;

    public function mount(): void
    {

        if (!Gate::allows('create-template')) {
            abort(404);
        }
        
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
                        ->maxLength(1000),
                ])
            ->statePath('data');
    }

    public function create(): void
    {

        // Determine the category: Use new category if provided, otherwise use selected category
        $this->data['new_category'] = $this->data['new_category'] ? TemplateCategory::create(['name' => $this->data['new_category']])->id : $this->data['template_category'];

        




        $validator = Validator::make($this->data, [
            'template_name' => 'required|string|max:255',
            'new_category' => 'required|integer',
            'description' => 'nullable|string|max:1000',
        ]);




        if ($validator->fails()) {
            // Initialize an error message
            $errorMessage = '';

            // Check for specific errors
            if ($validator->errors()->has('template_name')) {
                $errorMessage .= 'Name is required and must not exceed 255 characters. ';
            }

            if ($validator->errors()->has('new_category')) {
                $errorMessage .= 'Category is required and must not exceed 255 characters. ';
            }

            if ($validator->errors()->has('description')) {
                $errorMessage .= 'Description must not exceed 1000 characters. ';
            }

            // Send the error notification
            Notification::make()
                ->danger()
                ->title('Validation Error')
                ->body($errorMessage)
                ->send();

            return; // Stop further execution
        }





        // Create a new template entry
        $this->template = Template::create([
            'template_name' => $this->data['template_name'], // Required
            'template_category_id' => $this->data['new_category'], // Required
            'template_description' => $this->data['description'] ?? '', // Default to empty string if not set
            'template_preview_link' => '', // Set to empty string
            'template_json' => '', // Correctly encodes an empty JSON object
            'robots_txt' => '', // Set to empty string
            'header_embed' => '', // Set to empty string
            'footer_embed' => '', // Set to empty string
        ]);

        $template = $this->template;

        TempPage::create([
            'page_id' => '',
            'name' => 'Page 1', // Page name is based on the current page count
            'slug' => 'page-1',
            'title' => 'page 1 - ' . $this->template->template_name,
            'meta_description' => '',
            'template_id' => $this->template->template_id,
            'html' => '',
            'css' => '',
            'embed_code_start' => '',
            'embed_code_end' => '',
            'main' => true,
        ]);


        // Save Header Data
        $headerTemplate = TemplateHeaderFooter::create([
            'template_id' => $this->template->template_id,
            'json' => '',
            'html' => '',
            'css' => '',
            'is_header' => true,
        ]);
        // Save Footer Data
        $footerTemplate = TemplateHeaderFooter::create([
            'template_id' => $this->template->template_id,
            'json' => '',
            'html' => '',
            'css' => '',
            'is_header' => false,
        ]);


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
                        <x-button type="button" wire:click="create" class="text-white bg-primary-600 hover:bg-primary-500">
                            Create Template
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>