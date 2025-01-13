<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\Template;
use App\Models\TemplateHeaderFooter;
use App\Models\TempPage;
use App\Models\Project;
use App\Models\TemplateCategory; // Import TemplateCategory model
use function Laravel\Folio\{middleware, name};

middleware('auth');
name('edit');

new class extends Component implements HasForms {
    use InteractsWithForms;

    public $template_id;
    public $template;
    public ?array $data = [];
    public $pages = [];
    public $pageData = [];
    public $mainPage;
    public $hfData = [];
    public $header;
    public $footer;



    public function mount($template_id): void
    {
        if (!Gate::allows('create-template')) {
            abort(404);
        }
        $this->template_id = $template_id;
        $this->template = Template::where('template_id', $this->template_id)->firstOrFail();
        // Pre-fill the form with existing project data
        $this->form->fill([
            'template_category' => $this->template->template_category_id,
            'template_name' => $this->template->template_name,
            'description' => $this->template->template_description, // Pre-fill the description
            'robots_txt' => $this->template->robots_txt, // Add robots.txt field
            'header_embed' => $this->template->header_embed, // Add embed code for header
            'footer_embed' => $this->template->footer_embed, // Add embed code for footer
            'preview_link' => $this->template->template_preview_link, // Add robots.txt field
            'template_json' => json_decode($this->template->template_json), // Add robots.txt field
            'is_publish' => $this->template->is_publish, // Add robots.txt field

        ]);

        $this->pages = TempPage::where('template_id', $this->template_id)->get();

        // Pre-fill the form with existing page data
        foreach ($this->pages as $page) {
            $this->pageData[$page->id] = [
                'page_id' => $page->page_id,
                'page_name' => $page->name,
                'page_title' => $page->title,
                'meta_description' => $page->meta_description,
                'og_tags' => $page->og,
                'header_embed_code' => $page->embed_code_start,
                'footer_embed_code' => $page->embed_code_end,
                'page_slug' => $page->slug,
                'page_html' => $page->html,
                'page_css' => $page->css,
            ];
        }
        ;



        $this->mainPage = TempPage::where('template_id', $this->template_id)
            ->where('main', true)
            ->first();

        if (!$this->mainPage && $this->pages->isNotEmpty()) {
            $this->mainPage = $this->pages->first();
            $this->mainPage->main = true; // Assuming `main` is the column to mark the main page
            $this->mainPage->save(); // Save the updated main page
        }


        $this->header = TemplateHeaderFooter::where("template_id", $this->template->template_id)
            ->where("is_header", true)
            ->first();
        $this->footer = TemplateHeaderFooter::where("template_id", $this->template->template_id)
            ->where("is_header", false)
            ->first();
        if (!$this->header) {
            $this->header = $this->headerCreate();
        }
        if (!$this->footer) {
            $this->footer = $this->footerCreate();
        }


        // Pre-fill the form with existing data
        $this->hfData = [
            'header_json' => json_decode($this->header->json),
            'header_html' => $this->header->html,
            'header_css' => $this->header->css,
            'footer_json' => json_decode($this->footer->json),
            'footer_html' => $this->footer->html,
            'footer_css' => $this->footer->css,
        ];
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

                FileUpload::make('template_image')
                    ->label('Template Screenshot')
                    ->image()
                    ->directory('templates_ss/screenshots')
                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png',])
                    ->maxSize(1024)
                    ->disk('public'), // Ensure file is saved to the public disk

                TextInput::make('preview_link')
                    ->label('Preview Link')
                    ->url(),

                Textarea::make('template_json')
                    ->label('Template JSON')
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
                Checkbox::make('is_publish') // Checkbox for marking the page as main
                    ->label('Publish')
                    ->default(false),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        if (!$this->template) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Template data is missing.')
                ->send();

            return;
        }


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
        $this->template->update([
            'template_name' => $this->data['template_name'],
            'template_category_id' => $this->data['new_category'],
            'template_description' => $this->data['description'],
            'template_preview_link' => $this->data['preview_link'],
            'template_json' => json_encode($this->data['template_json']),
            'robots_txt' => $this->data['robots_txt'],
            'header_embed' => $this->data['header_embed'],
            'footer_embed' => $this->data['footer_embed'],
            'is_publish' => $this->data['is_publish'] ?? false, // Make sure this is set if needed
        ]);




        // Handle image upload
        $image = $this->data['template_image'] ?? null;

        if ($image) {
            // The image is in an array, extract the file
            $file = reset($image); // Get the first value from the array
            $newPath = "templates/{$this->template->template_id}/screenshot/";


            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                try {
                    $fileExtension = $file->getClientOriginalExtension(); // Get the file's original extension
                    $fileName = "{$this->template->template_id}.{$fileExtension}";
                    // Use storeAs to save the file on the public disk
                    $file->storeAs($newPath, $fileName, 'public');
                    // Update the favicon in the template record
                    $this->template->update([
                        'ss' => $fileName,
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


        $logo = $this->data['logo'] ?? null;

        if ($logo) {
            // The logo is in an array, extract the file
            $file = reset($logo); // Get the first value from the array
            $newPath = "templates/{$this->template->template_id}/logo/";

            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                try {
                    $fileExtension = $file->getClientOriginalExtension(); // Get the file's original extension
                    $fileName = "{$this->template->template_id}.{$fileExtension}";
                    // Use storeAs to save the file on the public disk
                    $file->storeAs($newPath, $fileName, 'public');
                    // Update the favicon in the template record
                    $this->template->update([
                        'favicon' => $fileName,
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
            ->title('Template updated successfully')
            ->send();

        $this->redirect('/templates/starter/');
    }




    public function createPage()
    {

        // Get the total number of pages for the given template_id
        $pageCount = TempPage::where('template_id', $this->template->template_id)->count();



        TempPage::create([
            'page_id' => '',
            'name' => 'Page ' . ($pageCount + 1), // Page name is based on the current page count
            'slug' => '',
            'title' => '',
            'meta_description' => '',
            'template_id' => $this->template->template_id,
            'html' => '',
            'css' => '',
            'og' => '',
            'embed_code_start' => '',
            'embed_code_end' => '',
            'main' => false,
        ]);

        $this->pages = TempPage::where('template_id', $this->template_id)->get();


    }


    public function deletePage($pageId)
    {
        $pageInstance = TempPage::find($pageId); // Find the selected page
        if ($pageInstance) {
            $pageInstance->delete();
            Notification::make()->success()->title('Main Page updated successfully.')->send();
            $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
        } else {
            Notification::make()->danger()->title('Page not found.')->send();
            $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        }
    }


    public function pageUpdate($pageId)
    {
        $page = TempPage::find($pageId);
        if ($page) {
            $data = $this->pageData[$pageId];
            $slug = "";
            $title = "";

            // Check if page_slug is available in the data and convert it to a slug
            $slug = Str::slug($data["page_slug"] ?? $data["page_name"]);
            // Ensure the slug is not empty after conversion
            if (empty($slug)) {
                $slug = Str::slug($data["page_name"]);
            }

            // Ensure the slug is not empty after conversion
            if (empty($slug)) {
                $slug = Str::slug("Default slug");
            }

            // Ensure the slug is unique within the project
            $originalSlug = $slug; // Save the original slug for comparison
            $counter = 1; // Initialize the counter for uniqueness check

            // Check if slug exists and increment the counter
            while (
                TempPage::where('slug', $slug)
                    ->where('template_id', $this->template->template_id)
                    ->where('id', '!=', $pageId) // Exclude the current page
                    ->exists()
            ) {
                // Append the counter to the slug
                $slug = $originalSlug . '-' . $counter;
                $counter++; // Increment the counter for the next iteration
            }

            if (isset($data["page_title"]) && !empty($data["page_title"])) {
                $title = $data["page_title"];
            } else {
                $title = $data["page_name"] . " - " . $this->template->name;
            }




            $page->update([
                'name' => $data['page_name'] ?? '',
                'title' => $title,
                'meta_description' => $data['meta_description'] ?? '',
                'og' => $data['og_tags'] ?? '',
                'embed_code_start' => $data['header_embed_code'] ?? '',
                'embed_code_end' => $data['footer_embed_code'] ?? '',
                'slug' => $slug,
                'page_id' => $data['page_id'] ?? '',
                'html' => $data['page_html'] ?? '',
                'css' => $data['page_css'] ?? '',
            ]);


            Notification::make()->success()->title('Page data updated successfully.')->send();
            $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
        } else {
            Notification::make()->danger()->title('Page not found.')->send();
            $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
        }
    }

    public function pageMain($pageId)
    {
        $pageInstance = TempPage::find($pageId); // Find the selected page
        if ($pageInstance) {


            // Set all pages with the same project_id to false
            TempPage::where('template_id', $pageInstance->template_id)
                ->update(['main' => false]);

            // Set the selected page as main
            $pageInstance->main = true;
            $pageInstance->save();

            Notification::make()->success()->title('Main Page updated successfully.')->send();
            $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        } else {
            Notification::make()->danger()->title('Page not found.')->send();
            $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        }
    }




    public function headerUpdate(){

        $this->header->update([
                'json' => json_encode($this->hfData['header_json']),
                'html' => $this->hfData['header_html'],
                'css' => $this->hfData['header_css'],
            ]);
        Notification::make()->success()->title('Header data updated successfully.')->send();
        $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
    }

    public function footerUpdate()
    {

        $this->footer->update([
                'json' => json_encode($this->hfData['footer_json']),
                'html' => $this->hfData['footer_html'],
                'css' => $this->hfData['footer_css'],
            ]);
        Notification::make()->success()->title('Footer data updated successfully.')->send();
        $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
    }





    public function headerCreate()
    {
        $this->header = TemplateHeaderFooter::create([
            'template_id' => $this->template->template_id,
            'json' => '',
            'html' => '',
            'css' => '',
            'is_header' => true,
        ]);
        return $this->header; // Return the created header
    }

    public function footerCreate()
    {
        $this->footer = TemplateHeaderFooter::create([
            'template_id' => $this->template->template_id,
            'json' => '',
            'html' => '',
            'css' => '',
            'is_header' => false,
        ]);
        return $this->footer; // Return the created footer
    }


}
    ?>
<x-layouts.app>
    @volt('edit')
    <x-app.container>
        <div class="container mx-auto my-6">

            <!-- Back Button -->
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to {{ $this->template->template_category }}"
                href="/templates/starter/{{$this->template->template_category_id}}" />

            <!-- Template Details Box -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="space-y-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center">
                     @if($this->template->favicon)
                        <img src="{{ asset('storage/templates/' . $this->template->template_id . '/logo/' . $this->template->favicon) }}" 
                            alt="Template Favicon" class="w-6 h-6 rounded-full mr-2">
                        @endif
                    <!-- Heading: Template Name -->
                    <x-app.heading title="Editing {{ $template->template_name }}"
                        description="You're editing this template." :border="false" />
                    </div>
                    <!-- Template Image -->
                    <div class="text-center mb-6">
                        @if($this->template->ss)
                        <img src="{{ asset('storage/templates/' . $this->template->template_id . '/screenshot/' . $this->template->ss) }}"
                            alt="{{ $template->template_name }}" class="rounded-md shadow w-32" />
                        @endif
                    </div>
                </div>
                                <!-- Public Status Indicator -->
                <div class="flex items-center">
                    <span class="text-sm font-medium mr-2">Status:</span>
                    @if($this->template->is_publish)
                    <span class="text-green-500">Publish</span>
                    @else
                    <span class="text-red-500">Not Publish</span>
                    @endif
                </div>
            </div>





<div x-data="{ activeTab: 'template-settings' }">
        <div class="mb-6 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                                <li class="mr-2">
                    <a
                        @click.prevent="activeTab = 'template-settings'" 
                        :class="{ 'text-blue-600 border-blue-500': activeTab === 'template-settings' }" 
                        class="tab-btn inline-block p-4 rounded-t-lg border-b-2 cursor-pointer select-none"
                    >
                        Template Settings
                    </a>
                </li>
                <li class="mr-2">
                    <a
                        @click.prevent="activeTab = 'pages'" 
                        :class="{ 'text-blue-600 border-blue-500': activeTab === 'pages' }" 
                        class="tab-btn inline-block p-4 rounded-t-lg border-b-2 cursor-pointer select-none"
                    >
                        Pages
                    </a>
                </li>
                <li class="mr-2">
                    <a
                        @click.prevent="activeTab = 'header-footer'" 
                        :class="{ 'text-blue-600 border-blue-500': activeTab === 'header-footer' }" 
                        class="tab-btn inline-block p-4 rounded-t-lg border-b-2 cursor-pointer select-none"
                    >
                        Header & Footer
                    </a>
                </li>
            </ul>
        </div>




<div 
    x-show="activeTab === 'template-settings'" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    x-cloak 
    id="template-settings" 
    class="space-y-6 tab-panel">

                    <div class="flex items-center justify-between mb-5">
                        <!-- Display the current project name as a heading -->
                        <x-app.heading title="Template Settings" description="Customize your template's configuration." :border="false" />
                    </div>


                <!-- Create Website Form -->
                <form wire:submit="create" class="space-y-6">
                    <!-- Form Fields -->
                    {{ $this->form }}

                    <div class="flex justify-end gap-x-3">
                        <x-button tag="a" href="/templates/starter/{{ $template->template_category_id }}"
                            color="secondary">Cancel</x-button>
                        <x-button type="button" wire:click="create"
                            class="text-white bg-primary-600 hover:bg-primary-500">
                            Save Chnages
                        </x-button>
                         <!-- Dropdown for More Actions -->
                            <x-dropdown class="text-gray-500">
                                <x-slot name="trigger">
                                    <x-button type="button" color="gray">More Actions</x-button>
                                </x-slot>

                                <!-- Duplicate Website -->
                                <a href="#" wire:click="duplicate"
                                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                                    <x-icon name="phosphor-copy" class="w-4 h-4 mr-2" /> Duplicate Template
                                </a>

                                <!-- Delete Website -->
                                <a href="#" wire:click="delete"
                                    class="block px-4 py-2 text-red-600 hover:bg-gray-100 flex items-center" wire:confirm="Are you sure you want to delete this template?">
                                    <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Template
                                </a>
                            </x-dropdown>
                    </div>
                </form>
</div>

                <!-- Pages Box -->
                <div 
    x-show="activeTab === 'pages'" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    x-cloak 
    id="pages" 
    class="space-y-6 tab-panel">
                    <div class="flex items-center justify-between mb-5">
                        <!-- Display the current project name as a heading -->
                        <x-app.heading title="Pages" description="Configure your template's pages." :border="false" />
                    </div>
                    <div class="space-y-4 mt-4">
<x-button type="button" wire:click="createPage" color="gray">
                        Add Pages
                    </x-button>
                    
                        @foreach($this->pages as $page)
                                            <div x-data="{ open: false }" class="mb-5">
                                                <!-- Page Header with Toggle -->
                            <div class="flex justify-between items-center">
                                <div @click="open = !open" class="cursor-pointer text-md font-semibold w-full flex justify-between">
                                    <span>{{ $page->name }} 
                                        @if($page->main)
                                    <x-icon name="phosphor-house-line" class="w-4 h-4 ml-2 inline-block" />
                                    @endif
                                    </span>
                                    
                                    <!-- Arrow Icon for Collapsible -->
                                    <svg x-bind:class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform duration-300" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>

                                                <div x-show="open" x-transition.duration.300ms x-cloak class="mt-4 space-y-3 bg-white-100 p-4 rounded-lg rounded-md shadow-md">




                                                 <!-- Page ID -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_id_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Page ID
                                                                    </span></label>
                                                                <div
                                                                    class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-text-input overflow-hidden">
                                                                    <input placeholder="Enter page ID" type="text"
                                                                        id="page_id_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.page_id"
                                                                        class="form-control fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3">
                                                                </div>
                                                            </div>



                                                            <!-- Page Name -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_name_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Page Name
                                                                    </span></label>
                                                                <div
                                                                    class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-text-input overflow-hidden">
                                                                    <input placeholder="Enter page Name" type="text"
                                                                        id="page_name_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.page_name"
                                                                        class="form-control fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3">
                                                                </div>
                                                            </div>
    



                                                            <!-- Page Title -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_title_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Page Title
                                                                    </span></label>
                                                                <div
                                                                    class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-text-input overflow-hidden">
                                                                    <input placeholder="Enter page title" type="text"
                                                                        id="page_title_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.page_title"
                                                                        class="form-control fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3">
                                                                </div>
                                                            </div>


                                                            <!-- Page Slug -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_slug_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Page Slug
                                                                    </span></label>
                                                                <div
                                                                    class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-text-input overflow-hidden">
                                                                    <input pattern="^[A-Za-z0-9\s-]+$"
                                                                        title="Slug should only contain letters, numbers and hyphens (-)."
                                                                        placeholder="Enter a unique page slug" type="text"
                                                                        id="page_slug_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.page_slug"
                                                                        class="form-control fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3">
                                                                </div>
                                                            </div>


                                                            
                                                            <!-- Html Tags -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_html_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Page HTML
                                                                    </span></label>
                                                                <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                    ">
                                                                    <textarea placeholder="Add html for your page" rows="5"
                                                                        id="page_html_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.page_html" rows="3"
                                                                        class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                        </textarea>
                                                                </div>
                                                            </div>




                                                            <!-- css Tags -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_css_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Page CSS
                                                                    </span></label>
                                                                <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                    ">
                                                                    <textarea placeholder="Add css for your page" rows="5"
                                                                        id="page_css_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.page_css" rows="3"
                                                                        class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                        </textarea>
                                                                </div>
                                                            </div>


                                                            <!-- Meta Description -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_meta_description_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Meta Description
                                                                    </span></label>
                                                                <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                                [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                                [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                                dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                                ">
                                                                    <textarea placeholder="Describe your page" rows="5" id="page_meta_description_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.meta_description" rows="3"
                                                                        class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                                                                                        </textarea>
                                                                </div>
                                                                </div>

                                                            <!-- Open Graph Tags -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_og_tags_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Open Graph Tags
                                                                    </span></label>
                                                                <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                    ">
                                                                    <textarea placeholder="Add open graph tag for your page" rows="5"
                                                                        id="page_og_tags_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.og_tags" rows="3"
                                                                        class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                        </textarea>
                                                                </div>
                                                            </div>

                                                            <!-- Header Embed Code -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_header_embed_code_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Header Embed Code
                                                                    </span></label>
                                                                <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                    ">
                                                                    <textarea placeholder="Add custom embed code for the header" rows="5"
                                                                        id="page_header_embed_code_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.header_embed_code" rows="3"
                                                                        class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                        </textarea>
                                                                </div>
                                                            </div>

                                                            <!-- Footer Embed Code -->
                                                            <div class="form-group grid gap-y-2">
                                                                <label for="page_footer_embed_code_{{ $page->id }}" class="block">
                                                                    <span
                                                                        class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                                        Footer Embed Code
                                                                    </span></label>
                                                                <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                    ">
                                                                    <textarea placeholder="Add custom embed code for the footer" rows="5"
                                                                        id="page_footer_embed_code_{{ $page->id }}"
                                                                        wire:model="pageData.{{$page->id}}.footer_embed_code" rows="3"
                                                                        class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                        </textarea>
                                                                </div>
                                                            </div>

                                                            <div class="flex justify-end gap-x-3">
                                                                <!-- Cancel Button -->
                                                                <x-button @click="open = !open" class="page-cancel" type="button"
                                                                    color="secondary">Cancel</x-button>
                                                                    
                                                                    <!-- Save Changes Button -->
                                                                    <x-button type="button" wire:click="pageUpdate({{ $page->id }})"
                                                                    class="text-white bg-primary-600 hover:bg-primary-500">Save
                                                                    Changes</x-button>


                                                                    <!-- Dropdown for More Actions -->
                            <x-dropdown class="text-gray-500">
                                <x-slot name="trigger">
                                    <x-button type="button" color="gray">More Actions</x-button>
                                </x-slot>

                                 @if (!$page->main)
                                <!-- Duplicate Website -->
                                <a href="#" wire:click="pageMain({{ $page->id }})"
                                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                                    <x-icon name="phosphor-house-line" class="w-4 h-4 mr-2" /> Set as Main
                                                                        Page
                                </a>
                                @endif

                                <!-- Delete Website -->
                                <a href="#" wire:click="deletePage({{ $page->id }})"
                                    class="block px-4 py-2 text-red-600 hover:bg-gray-100 flex items-center">
                                    <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Page
                                </a>
                            </x-dropdown>
                            
                                                            </div>


                                                </div>
                                            </div>
                        @endforeach

                    </div>
                </div>
                                <!-- header adn footer Box -->
                                <div x-show="activeTab === 'header-footer'" x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100"
                                    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform scale-100"
                                    x-transition:leave-end="opacity-0 transform scale-95" x-cloak id="header-footer" class="space-y-6 tab-panel">
                                    <div class="flex items-center justify-between mb-5">
                                        <!-- Display the current project name as a heading -->
                                        <x-app.heading title="Header & Footer" description="Configure your template's header & footer." :border="false" />
                                    </div>
                                    <div class="space-y-4 mt-4">
                                        <div x-data="{ open: false }" class="mb-5">
                                            <!--  Header with Toggle -->
                                            <div class="flex justify-between items-center">
                                                <div @click="open = !open" class="cursor-pointer text-md font-semibold w-full flex justify-between">
                                                    <span>Header
                                                    </span>
                                
                                                    <!-- Arrow Icon for Collapsible -->
                                                    <svg x-bind:class="open ? 'transform rotate-180' : ''"
                                                        class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div x-show="open" x-transition.duration.300ms x-cloak
                                                class="mt-4 space-y-3 bg-white-100 p-4 rounded-lg rounded-md shadow-md">                                
                                                <!-- Header json -->
                                                <div class="form-group grid gap-y-2">
                                                    <label for="header_json" class="block">
                                                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                            Header Json
                                                        </span></label>
                                                    <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                                                    ">
                                                        <textarea placeholder="Enter the JSON structure for the header" rows="5"
                                                            id="header_json"
                                                            wire:model="hfData.header_json" rows="3"
                                                            class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                                                        </textarea>
                                                    </div>
                                                </div>
                                                <!-- Header html -->
                                                <div class="form-group grid gap-y-2">
                                                    <label for="header_html" class="block">
                                                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                            Header HTML
                                                        </span></label>
                                                    <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                                                    ">
                                                        <textarea placeholder="Enter the HTML content for the header" rows="5"
                                                            id="header_html"
                                                            wire:model="hfData.header_html" rows="3"
                                                            class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                                                        </textarea>
                                                    </div>
                                                </div>
                                                <!-- Header css -->
                                                <div class="form-group grid gap-y-2">
                                                    <label for="header_css" class="block">
                                                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                            Header CSS
                                                        </span></label>
                                                    <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                                                    ">
                                                        <textarea placeholder="Enter CSS for the header" rows="5"
                                                            id="header_css"
                                                            wire:model="hfData.header_css" rows="3"
                                                            class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                                                        </textarea>
                                                    </div>
                                                </div>
                                                <div class="flex justify-end gap-x-3">
                                                    <!-- Cancel Button -->
                                                    <x-button @click="open = !open" class="page-cancel" type="button"
                                                        color="secondary">Cancel</x-button>
                                
                                                    <!-- Save Changes Button -->
                                                    <x-button type="button" wire:click="headerUpdate"
                                                        class="text-white bg-primary-600 hover:bg-primary-500">Save
                                                        Changes</x-button>
                                                </div>
                                            </div>
                                        </div>


                                        <div x-data="{ open: false }" class="mb-5">
                                            <!--  Footer with Toggle -->
                                            <div class="flex justify-between items-center">
                                                <div @click="open = !open" class="cursor-pointer text-md font-semibold w-full flex justify-between">
                                                    <span>Footer
                                                    </span>
                                
                                                    <!-- Arrow Icon for Collapsible -->
                                                    <svg x-bind:class="open ? 'transform rotate-180' : ''"
                                                        class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div x-show="open" x-transition.duration.300ms x-cloak
                                                class="mt-4 space-y-3 bg-white-100 p-4 rounded-lg rounded-md shadow-md">
                                                <!-- Footer json -->
                                                <div class="form-group grid gap-y-2">
                                                    <label for="footer_json" class="block">
                                                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                            Footer Json
                                                        </span></label>
                                                    <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                                                    ">
                                                        <textarea placeholder="Enter the JSON structure for the footer" rows="5"
                                                            id="footer_json"
                                                            wire:model="hfData.footer_json" rows="3"
                                                            class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                                                        </textarea>
                                                    </div>
                                                </div>
                                                <!-- Footer html -->
                                                <div class="form-group grid gap-y-2">
                                                    <label for="footer_html" class="block">
                                                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                            Footer HTML
                                                        </span></label>
                                                    <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                                                    ">
                                                        <textarea placeholder="Enter the HTML content for the footer" rows="5"
                                                            id="footer_html"
                                                            wire:model="hfData.footer_html" rows="3"
                                                            class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                                                        </textarea>
                                                    </div>
                                                </div>
                                                <!-- Footer css -->
                                                <div class="form-group grid gap-y-2">
                                                    <label for="footer_css" class="block">
                                                        <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                                            Footer CSS
                                                        </span></label>
                                                    <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20
                                                                                                                    [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600
                                                                                                                    dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-textarea overflow-hidden
                                                                                                                    ">
                                                        <textarea placeholder="Enter CSS for the footer" rows="5"
                                                            id="footer_css"
                                                            wire:model="hfData.footer_css" rows="3"
                                                            class="block h-full w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 form-control">
                                                                                                        </textarea>
                                                    </div>
                                                </div>
                                                <div class="flex justify-end gap-x-3">
                                                    <!-- Cancel Button -->
                                                    <x-button @click="open = !open" class="page-cancel" type="button"
                                                        color="secondary">Cancel</x-button>
                                
                                                    <!-- Save Changes Button -->
                                                    <x-button type="button" wire:click="footerUpdate"
                                                        class="text-white bg-primary-600 hover:bg-primary-500">Save
                                                        Changes</x-button>
                                                </div>
                                            </div>
                                        </div>                                
                                    </div>
                                </div>









</div>
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>