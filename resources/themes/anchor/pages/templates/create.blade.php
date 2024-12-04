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
use Illuminate\Support\Facades\Storage;
use function Laravel\Folio\{middleware, name};

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
                    // Existing Category Dropdown
                    Select::make('template_category')
                        ->label('Existing Category')
                        ->options(function () {
                            return Template::select('template_category')
                                ->distinct()
                                ->pluck('template_category', 'template_category')
                                ->toArray();
                        })
                        ->required(),

                    TextInput::make('template_name')
                        ->label('Template Name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Description')
                        ->maxLength(1000),

                    FileUpload::make('template_image')
                        ->label('Template Screenshot')
                        ->image()
                        ->directory('templates_ss/screenshots')
                        ->disk('public'), // Ensure file is saved to the public disk

                    TextInput::make('preview_link')
                        ->label('Preview Link')
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

        // Create a new template entry
        $template = Template::create([
            'template_name' => $this->data['template_name'],
            'template_category' => $this->data['template_category'],
            'template_description' => $this->data['description'],
            'template_preview_link' => $this->data['preview_link'],
            'template_json' => json_encode($this->data['template_json']),
        ]);

    if ($image = $this->data['template_image'] ?? null) {
        if ($image instanceof \Illuminate\Http\UploadedFile) {
            $image->storeAs('templates_ss/screenshots', "{$template->template_id}.png", 'public');
            }
            else {
            // If the uploaded file isn't valid, send an error notification
            Notification::make()
                ->danger()
                ->title('Invalid Image File')
                ->body('The image file is not valid. Please upload a proper image.')
                ->send();
            }
    } else {
        // If no image was uploaded, notify the user
        Notification::make()
            ->warning()
            ->title('No Image Uploaded')
            ->body('No template image was uploaded.')
            ->send();
    }


        Notification::make()
            ->success()
            ->title('Template created successfully')
            ->send();

        $this->redirect('/templates');
    }
}
    ?>

<x-layouts.app>
    @volt('templates.create')
    <x-app.container>
        <div class="container mx-auto my-6">

            <!-- Back Button -->
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to Templates" href="/templates" />

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
                        <x-button tag="a" href="/templates" color="secondary">Cancel</x-button>
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