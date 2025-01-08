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
                        ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png',])
                        ->maxSize(1024)
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

                // Logo Uploader
                FileUpload::make('logo')
                    ->label('Upload Favicon')
                    ->image()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('260')
                    ->imageResizeTargetHeight('260')
                    ->maxSize(1024)
                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png',])
                    ->helperText('Upload a favicon for Template'),

                // Robots.txt Textarea
                Textarea::make('robots_txt')
                    ->label('Robots.txt')
                    ->placeholder('Add or modify the Robots.txt content')
                    ->rows(6),

                // Embed Code for Header
                Textarea::make('header_embed')
                    ->label('Embed Code in Header')
                    ->placeholder('Add custom embed code for the header')
                    ->rows(6),

                // Embed Code for Footer
                Textarea::make('footer_embed')
                    ->label('Embed Code in Footer')
                    ->placeholder('Add custom embed code for the footer')
                    ->rows(6),
                // Add the Repeater for Pages here:
                Repeater::make('pages') // 'pages' is the array of pages
                    ->label('Pages')
                    ->schema([
                        TextInput::make('page_id') // Unique ID for each page
                            ->label('Page ID')
                            ->required()
                            ->placeholder('Enter a unique page identifier'),

                        TextInput::make('page_name') // Name of the page
                            ->label('Page Name')
                            ->required()
                            ->placeholder('Enter the name of the page'),

                        TextInput::make('slug') // URL slug for the page
                            ->label('Page Slug')
                            ->required()
                            ->placeholder('Enter the page URL slug (e.g., about-us)'),

                        TextInput::make('title') // Title of the page
                            ->label('Page Title')
                            ->required()
                            ->placeholder('Enter the title for the page'),

                        Textarea::make('page_meta_description') // Meta description for the page
                            ->label('Meta Description')
                            ->maxLength(255)
                            ->placeholder('Enter a short description of the page for search engines'),

                        Textarea::make('og') // OG (Open Graph) data
                            ->label('OG Tags')
                            ->rows(6)
                            ->placeholder('<meta property="og:image" content="image_url">'),

                        Textarea::make('page_html') // HTML content for the page
                            ->label('Page HTML')
                            ->rows(6)
                            ->required()
                            ->placeholder('Enter the HTML content for the page'),

                        Textarea::make('page_css') // CSS for the page
                            ->label('Page CSS')
                            ->rows(6)
                            ->required()
                            ->placeholder('Enter custom CSS for the page'),

                        Textarea::make('page_header_embed_code') // Header embed code (e.g., for scripts, styles)
                            ->label('Header Embed Code')
                            ->rows(6)
                            ->required()
                            ->placeholder('Enter HTML or JavaScript for the page header'),

                        Textarea::make('page_footer_embed_code') // Footer embed code (e.g., for scripts, styles)
                            ->label('Footer Embed Code')
                            ->rows(6)
                            ->required()
                            ->placeholder('Enter HTML or JavaScript for the page footer'),
                        Checkbox::make('is_main_page') // Checkbox for marking the page as main
                            ->label('Mark as Main Page')
                            ->default(false)
                            ->hint('Select this option to set this page as the main page.'),
                    ])
                    ->required(),

                // Header Section Fields
                Textarea::make('header_json') // JSON data for the header
                    ->label('Header JSON')
                    ->rows(6)
                    ->required()
                    ->placeholder('Enter the JSON structure for the header'),

                Textarea::make('header_html') // HTML content for the header
                    ->label('Header HTML')
                    ->rows(6)
                    ->required()
                    ->placeholder('Enter the HTML content for the header'),

                Textarea::make('header_css') // CSS for the header
                    ->label('Header CSS')
                    ->rows(6)
                    ->required()
                    ->placeholder('Enter CSS for the header'),

                // Footer Section Fields
                Textarea::make('footer_json') // JSON data for the footer
                    ->label('Footer JSON')
                    ->rows(6)
                    ->required()
                    ->placeholder('Enter the JSON structure for the footer'),

                Textarea::make('footer_html') // HTML content for the footer
                    ->label('Footer HTML')
                    ->rows(6)
                    ->required()
                    ->placeholder('Enter the HTML content for the footer'),

                Textarea::make('footer_css') // CSS for the footer
                    ->label('Footer CSS')
                    ->rows(6)
                    ->required()
                    ->placeholder('Enter CSS for the footer'),
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
        $this->template = Template::create([
            'template_name' => $this->data['template_name'],
            'template_category_id' => $categoryId,
            'template_description' => $this->data['description'],
            'template_preview_link' => $this->data['preview_link'],
            'template_json' => json_encode($this->data['template_json']),
            'robots_txt' => $this->data['robots_txt'],
            'header_embed' => $this->data['header_embed'],
            'footer_embed' => $this->data['footer_embed'],
        ]);
        $template = $this->template;




        // Save each page data
        foreach ($this->data['pages'] as $pageData) {
            TempPage::create([
                'page_id' => $pageData['page_id'],
                'name' => $pageData['page_name'],
                'slug' => $pageData['slug'],
                'title' => $pageData['title'],
                'meta_description' => $pageData['page_meta_description'],
                'template_id' => $template->template_id,
                'html' => $pageData['page_html'],
                'css' => $pageData['page_css'],
                'og' => $pageData['og'],
                'embed_code_start' => $pageData['page_header_embed_code'],
                'embed_code_end' => $pageData['page_footer_embed_code'],
                'main' => $pageData['is_main_page'],
            ]);
        }





        // Save Header Data
        $headerTemplate = TemplateHeaderFooter::create([
            'template_id' => $template->template_id,
            'json' => $this->data['header_json'],
            'html' => $this->data['header_html'],
            'css' => $this->data['header_css'],
            'is_header' => true,
        ]);
        // Save Footer Data
        $footerTemplate = TemplateHeaderFooter::create([
            'template_id' => $template->template_id,
            'json' => $this->data['footer_json'],
            'html' => $this->data['footer_html'],
            'css' => $this->data['footer_css'],
            'is_header' => false,
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

        $logo = $this->data['logo'] ?? null;

        if ($logo) {
            // The logo is in an array, extract the file
            $file = reset($logo); // Get the first value from the array
            $newPath = "templates/{$template->template_id}/logo/";

            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                try {
                    // Use storeAs to save the file on the public disk
                    $file->storeAs($newPath, "{$template->template_id}" . pathinfo($file, PATHINFO_EXTENSION), 'public');
                    $this->template->update([
                        'favicon' => "{$template->template_id}." . pathinfo($file, PATHINFO_EXTENSION),
                    ]);
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