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
use Illuminate\Support\Str;
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
    public $message = '';
    public $ourDomain = ".template.wpengineers.com";
    public $shouldRedirect = true; // Flag to control redirection
    public $liveData = [];
    public $categoryName;
    public $allPagesId = [];




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
            'template_json' => $this->template->template_json, // Add robots.txt field
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
            'header_json' => $this->header->json,
            'header_html' => $this->header->html,
            'header_css' => $this->header->css,
            'footer_json' => $this->footer->json,
            'footer_html' => $this->footer->html,
            'footer_css' => $this->footer->css,
        ];


        $this->liveData = [
            'domain' => $this->template->domain,
            'pages' => [],    // Default empty array for selected pages
            'header' => true, // Default value for header
            'footer' => true, // Default value for footer
            'global_header_embed' => $this->template->header_embed,
            'global_footer_embed' => $this->template->footer_embed,
            'robots_txt' => $this->template->robots_txt,
        ];


        if ($this->template->live) {
            // If the project is live, select only the pages where `live` is true
            $this->liveData['pages'] = $this->pages->where('live', true)->pluck('id')->toArray();
            $this->liveData['header'] = (bool) $this->header->live;
            $this->liveData['footer'] = (bool) $this->footer->live;
        } else {
            // If the project is not live, select all page IDs
            $this->liveData['pages'] = $this->pages->pluck('id')->toArray();
        }
        $this->allPagesId = $this->pages->pluck('id')->toArray(); // Populate with all page IDs


        $this->categoryName = TemplateCategory::find($this->template->template_category_id)->name;


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
            'template_json' => $this->data['template_json'],
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
            ->title('Template settings updated successfully')
            ->send();

        $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
    }

    public function createPage()
    {

        // Get the total number of pages for the given template_id
        $pageCount = TempPage::where('template_id', $this->template->template_id)->count();



        TempPage::create([
            'page_id' => '',
            'name' => 'Page ' . ($pageCount + 1), // Page name is based on the current page count
            'slug' => 'page-'.($pageCount + 1),
            'title' => 'Page ' . ($pageCount + 1) . '-' . $this->template->template_name,
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
        $this->liveData['pages'] = $this->pages->pluck('id')->toArray(); // Populate with all page IDs


    }

    public function deletePage($pageId)
    {
        $pageInstance = TempPage::find($pageId); // Find the selected page

        
        if ($pageInstance) {


            // Check if this is the last page for the template
        $totalPages = TempPage::where('template_id', $pageInstance->template_id)->count();

        // If it's the last page, prevent deletion
        if ($totalPages == 1) {
            Notification::make()
                ->danger()
                ->title('Cannot delete the last page.')
                ->body('You cannot delete the last page of the template.')
                ->send();

            return $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
        }


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
                'json' => $this->hfData['header_json'],
                'html' => $this->hfData['header_html'],
                'css' => $this->hfData['header_css'],
            ]);
        Notification::make()->success()->title('Header data updated successfully.')->send();
        $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
    }

    public function footerUpdate()
    {

        $this->footer->update([
                'json' => $this->hfData['footer_json'],
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

    public function saveDomain(){
        $domain = $this->liveData['domain'];
        if(!$this->check()){
            Notification::make()
                ->danger()
                ->title('Domain Error')
                ->body('You must select correct domain for your template.')
                ->send();

            return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        }

        $domain = Str::slug($domain);

        // Update template domain
        $this->template->update([
            'domain' => $domain,
        ]);

        

        Notification::make()
            ->success()
            ->title('Domain Reserved')
            ->body('The domain for your template has been successfully reserved.')
            ->send();

        $this->redirect('/templates/starter/edit' . '/' . $this->template->template_id);
    }

    public function check()
    {
        // Check if the subdomain is empty
        if (empty($this->liveData['domain'])) {
            $this->message = '<p class="text-sm text-gray-600 inline-flex items-center">
                                <span class="mr-1"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
    <path d="M8 1a7 7 0 1 0 0 14 7 7 0 0 0 0-14Zm0 1a6 6 0 1 1 0 12 6 6 0 0 1 0-12Zm-.5 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-3zM8 5.5a.5.5 0 1 1 1 0 .5.5 0 0 1-1 0z"/>
    </svg></span> Please enter a subdomain.
                            </p>';
            return false;
        }



        $domianCheck = Str::slug($this->liveData['domain']);


        // Check if the subdomain is less than 3 characters long or more than 20 characters
        if (strlen($domianCheck) < 3 || strlen($domianCheck) > 20) {
            $this->message = '<p class="text-sm text-yellow-600 inline-flex items-center">
                                <span class="mr-1"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
    <path d="M8 1a7 7 0 1 0 0 14 7 7 0 0 0 0-14Zm0 1a6 6 0 1 1 0 12 6 6 0 0 1 0-12Zm-.001 4.5a.5.5 0 0 1 .5-.5h.002a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-.002a.5.5 0 0 1-.5-.5v-4zM7.5 11a.5.5 0 0 1 .5-.5h.002a.5.5 0 0 1 .5.5v.002a.5.5 0 0 1-.5.5h-.002a.5.5 0 0 1-.5-.5v-.002z"/>
    </svg></span> Subdomain must be between 3 and 20 characters long.
                            </p>';
            return false;
        }

        // Query the database to check if the domain already exists
        $domainExists = Template::where('domain', $domianCheck)
                            ->where('template_id', '!=', $this->template->template_id) // Exclude current template
                            ->exists();

        // Check if the domain exists
        if ($domainExists) {
            $this->message = '<p class="text-sm text-red-600 inline-flex items-center">
                                <span class="mr-1"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16"><path d="M8 1a7 7 0 1 0 0 14 7 7 0 0 0 0-14Zm0 1a6 6 0 1 1 0 12 6 6 0 0 1 0-12Zm3.646 4.354a.5.5 0 0 1 0 .708L8.707 8l2.939 2.939a.5.5 0 0 1-.707.707L8 8.707 5.061 11.646a.5.5 0 0 1-.707-.707L7.293 8 4.354 5.061a.5.5 0 1 1 .707-.707L8 7.293l2.939-2.939a.5.5 0 0 1 .707.707L8.707 8l2.939 2.939a.5.5 0 0 1-.707.707L8 8.707 5.061 11.646a.5.5 0 0 1-.707-.707L7.293 8 4.354 5.061a.5.5 0 1 1 .707-.707L8 7.293l2.939-2.939a.5.5 0 0 1 .707.707L8.707 8l2.939 2.939a.5.5 0 0 1-.707.707L8 8.707z"/></svg></span> Domain is taken.
                            </p>';
            return false;
        } else {
            $this->message = '<p class="text-sm text-green-600 inline-flex items-center">
                                <span class="mr-1"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="bi bi-check-circle" viewBox="0 0 16 16">
    <circle cx="8" cy="8" r="7" stroke="green" fill="none"/>
    <path d="M6 8l2 2 4-4" stroke="green" fill="none"/>
    </svg>
    </span> Domain is available.</p>';
            return true;
        }
    }

    public function liveWebsite() 
    {
        // Access liveData array
        $domain = $this->liveData['domain'];
        $pages = $this->liveData['pages']; // Selected pages array
        $header = $this->liveData['header'];
        $footer = $this->liveData['footer'];

        $robotsTxt = $this->liveData['robots_txt'] ?? '# Default robots.txt';
        $globalHeaderEmbed = $this->liveData['global_header_embed'] ?? '';
        $globalFooterEmbed = $this->liveData['global_footer_embed'] ?? '';


        if(!$this->check()){
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('Domain Error')
                    ->body('You must select correct domain for your template to live.')
                    ->send();

                return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

            } else {
                return [
                    'status' => 'danger',
                    'title' => 'Domain Error',
                    "body" => 'You must select correct domain for your template to live.',
                ];
            }
        }

        $domain = Str::slug($domain);

        // Update project domain
        $this->template->update([
            'domain' => $domain,
        ]);


        // Check if at least one page is selected
        if (empty($pages)) {
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('Error: No Pages Selected')
                    ->body('You must select at least one page to make the template live.')
                    ->send();

                return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

            } else {
                return [
                    'status' => 'danger',
                    'title' => 'No Pages Selected',
                    "body" => 'You must select at least one page to make the template live.',
                ];
            }
        }




        
    $mainPageExists = false; // Initialize a flag for the main page

    // Iterate through the selected pages to check if the main page is included
    foreach ($pages as $pageId) {
        $page = TempPage::find($pageId); // Replace `Page` with your actual model name
        if ($page && $page->main) {
            $mainPageExists = true; // Set the flag if the main page is found
            break; // Exit the loop early since we found the main page
        }
    }

    // If no main page is found, handle the error
    if (!$mainPageExists) {
        if ($this->shouldRedirect) {
            Notification::make()
                ->danger()
                ->title('No Main Page')
                ->body('You must select a main page for your template to live.')
                ->send();

            return redirect('/templates/starter/edit' . '/' . $this->template->template_id);
        } else {
            return [
                'status' => 'danger',
                'title' => 'No Main Page',
                "body" => 'You must select a main page for your template to live.',
            ];
        }
    }



        if(!$this->mainPage){
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('No Main Page')
                    ->body('You must select a main page for your template to live.')
                    ->send();

                return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

            } else {
                return [
                    'status' => 'danger',
                    'title' => 'No Main Page',
                    "body" => 'You must select a main page for your template to live.',
                ];
            }
        }

        // Ensure the target folder exists
        $targetFolder = "/var/www/templates/{$domain}";
        if (!file_exists($targetFolder)) {
            if (!mkdir($targetFolder, 0755, true)) {


                if ($this->shouldRedirect) {
                    Notification::make()
                        ->danger()
                        ->title('Domain Configuration Error')
                        ->body("Failed to create template for the domain: {$domain}")
                        ->send();

                    return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

                } else {
                    return [
                        'status' => 'danger',
                        'title' => 'Domain Configuration Error',
                        "body" => "Failed to create template for the domain: {$domain}",
                    ];
                }

            }
        }

        $robotsFilePath = "{$targetFolder}/robots.txt";
        if (!file_put_contents($robotsFilePath, $robotsTxt)) {
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('Robots.txt Error')
                    ->body("Failed to create robots.txt for the domain: {$domain}")
                    ->send();

                return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

            } else {
                return [
                    'status' => 'danger',
                    'title' => 'Robots.txt Error',
                    "body" => "Failed to create robots.txt for the domain: {$domain}",
                ];
            }
        }




        // Fetch header and footer if required
        $headerHtml = '';
        $headerCss = '';
        $footerHtml = '';
        $footerCss = '';

        // Fetch header and footer data
        if ($header) {
            $header = TemplateHeaderFooter::where("template_id", $this->template->template_id)
                ->where("is_header", true)
                ->first();
            $headerHtml = $header->html;
            $headerCss = $header->css;
        }

        if ($footer) {
            $footer = TemplateHeaderFooter::where("template_id", $this->template->template_id)
                ->where("is_header", false)
                ->first();
            $footerHtml = $footer->html;
            $footerCss = $footer->css;
        }
        $favIcon = '';
        $sourcePath = "/var/www/ezysite/public/storage/templates/{$this->template->template_id}/logo/{$this->template->favicon}";
        if (File::exists($sourcePath)) {

            $destinationPath = $targetFolder . "/img/{$this->template->favicon}";
            $result = $this->copyImage($sourcePath, $destinationPath);
            if ($result === 'danger') {
                Notification::make()
                    ->danger()
                    ->title('Favicon not found')
                    ->send();
            }
            $favIcon = "/img/{$this->template->favicon}";
        }


        // Process the HTML to replace base64 images
        $headerHtml = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/i', function ($matches) use ($domain) {
            $imageSrc = $matches[1];

            // Check if the image source starts with "data:"
            if (strpos($imageSrc, 'data:') === 0) {
                // Save the base64 image and get the new file path
                $newSrc = $this->saveBase64Image($imageSrc, $domain);

                // Check if there was an error (false return value)
                if ($newSrc === false) {
                    // If saving the base64 image failed, leave the original src
                    return $matches[0]; // Return the original <img> tag with its current src
                }

                // If the image was successfully saved, replace the src in the <img> tag
                return str_replace($imageSrc, $newSrc, $matches[0]);

            }

            // If it's not a base64 image, return the original HTML
            return $matches[0];

        }, $headerHtml);


        // Process the HTML to replace base64 images
        $footerHtml = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/i', function ($matches) use ($domain) {
            $imageSrc = $matches[1];

            // Check if the image source starts with "data:"
            if (strpos($imageSrc, 'data:') === 0) {
                // Save the base64 image and get the new file path
                $newSrc = $this->saveBase64Image($imageSrc, $domain);

                // Check if there was an error (false return value)
                if ($newSrc === false) {
                    // If saving the base64 image failed, leave the original src
                    return $matches[0]; // Return the original <img> tag with its current src
                }

                // If the image was successfully saved, replace the src in the <img> tag
                return str_replace($imageSrc, $newSrc, $matches[0]);

            }

            // If it's not a base64 image, return the original HTML
            return $matches[0];

        }, $footerHtml);



            // Initialize the sitemap XML structure
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';


        // Loop through each page and add it to the sitemap
        foreach ($pages as $pageId) {
            $page = TempPage::find($pageId);

            if ($page) {
                $url = $page->main ? 'index.html' : "{$page->slug}.html";
                $priority = $page->main ? '1.0' : '0.8';  // Main page gets priority 1.0, others 0.8
                $sitemap .= '<url>';
                $sitemap .= '<loc>https://' . $domain . $this->ourDomain . '/' . $url . '</loc>';
                $sitemap .= '<lastmod>' . now()->toAtomString() . '</lastmod>';  // Date and time of the last modification
                $sitemap .= '<priority>' . $priority . '</priority>'; // Set the priority based on the page
                $sitemap .= '</url>';
            }
        }

        $sitemap .= '</urlset>';

        // Save the sitemap.xml file in the target domain folder
        $sitemapFilePath = "{$targetFolder}/sitemap.xml";
        if (!file_put_contents($sitemapFilePath, $sitemap)) {
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('Sitemap Error')
                    ->body("Failed to create sitemap.xml for the domain: {$domain}")
                    ->send();

                return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

            } else {
                return [
                    'status' => 'danger',
                    'title' => 'Sitemap Error',
                    "body" => "Failed to create sitemap.xml for the domain: {$domain}",
                ];
            }
        }




        // Fetch and process each selected page
        foreach ($pages as $pageId) {
            $page = TempPage::find($pageId);

            if (!$page) {
                if ($this->shouldRedirect) {
                    Notification::make()
                        ->danger()
                        ->title('Page not found')
                        ->body("Failed to fetch page with ID: {$pageId}")
                        ->send();

                    return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

                } else {
                    return [
                        'status' => 'danger',
                        'title' => 'Page not found',
                        "body" => "Failed to fetch page with ID: {$pageId}",
                    ];
                }
            }

            $pageHtml = $page->html;
            $pageCss = $page->css;
            $title = $page->title;
            $slug = $page->slug;
            $metaDescription = $page->meta_description ?? '';
            $ogTags = $page->og ?? '';
            $headerEmbed = $page->embed_code_start ?? '';
            $footerEmbed = $page->embed_code_end ?? '';
            $pageSlug = $page->main ? 'index' : $slug;




            // Process the HTML to replace base64 images
            $updatedPageHtml = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\']/i', function ($matches) use ($domain) {
                $imageSrc = $matches[1];

                // Check if the image source starts with "data:"
                if (strpos($imageSrc, 'data:') === 0) {
                    // Save the base64 image and get the new file path
                    $newSrc = $this->saveBase64Image($imageSrc, $domain);

                    // Check if there was an error (false return value)
                    if ($newSrc === false) {
                        // If saving the base64 image failed, leave the original src
                        return $matches[0]; // Return the original <img> tag with its current src
                    }

                    // If the image was successfully saved, replace the src in the <img> tag
                    return str_replace($imageSrc, $newSrc, $matches[0]);

                }

                // If it's not a base64 image, return the original HTML
                return $matches[0];

            }, $pageHtml);



            // Build full HTML
            $html = $this->buildFullHtml([
                'title' => $title,
                'meta_description' => $metaDescription,
                'og_tags' => $ogTags,
                'fav_icon' => $favIcon,
                'header_embed' => $globalHeaderEmbed . $headerEmbed, // Combine global and page-specific header embeds
                'footer_embed' => $globalFooterEmbed . $footerEmbed, // Combine global and page-specific footer embeds
                'header_html' => $headerHtml,
                'header_css' => $headerCss,
                'footer_html' => $footerHtml,
                'footer_css' => $footerCss,
                'page_html' => $updatedPageHtml,
                'page_css' => $pageCss,
                'page_slug' => $pageSlug,
            ]);

            // Determine filename
            $filename = $page->main ? 'index.html' : "{$slug}.html";

            // Save the HTML file
            if (!file_put_contents("{$targetFolder}/{$filename}", $html)) {
                if ($this->shouldRedirect) {
                    Notification::make()
                        ->danger()
                        ->title('Page file Error')
                        ->body("Failed to write HTML file for page: {$slug}")
                        ->send();

                    return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

                } else {
                    return [
                        'status' => 'danger',
                        'title' => 'Page file Error',
                        "body" => "Failed to write HTML file for page: {$slug}",
                    ];
                }
            }

            $page->update([
                'live' => true,
            ]);
        }

        $uncheckedPageIds = array_diff($this->allPagesId, $pages);
        // Update live status for unchecked pages
        foreach ($uncheckedPageIds as $pageId) {
            $page = TempPage::find($pageId);
            if ($page) {
                $page->live = false; // Mark as not live
                $page->save();
            }
        }



        if ($header) {
            $header->update([
                'live' => true,
            ]);
        } else {
            $this->header->update([
                'live' => false,
            ]);
        }

        if ($footer) {
            $footer->update([
                'live' => true,
            ]);
        } else {
            $this->footer->update([
                'live' => false,
            ]);
        }

        // Update project domain
        $this->template->update([
            'live' => true,
        ]);


        if ($this->shouldRedirect) {
            Notification::make()
                ->success()
                ->title('Template successfully made live at ' . $domain . $this->ourDomain)
                ->send();
            // Redirect or refresh logic
            return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        } else {
            return [
                'status' => 'success',
            ];
        }
    }

    public function addActiveClassToMenu($headerCode, $pageSlug) {
        // Remove the leading slash from the page slug (if any)
        $pageSlug = ltrim($pageSlug, '/');

        // Regular expression to match all <a> tags with href attributes
        $pattern = '/<a\s+([^>]*)href\s*=\s*"([^"]+)"([^>]*)>(.*?)<\/a>/i';

        // Callback function to check if the link matches the pageSlug
        $callback = function ($matches) use ($pageSlug) {
            // Extract href from the matched <a> tag
            $link = $matches[2];

            // Remove leading slash and file extension from the link
            $linkWithoutExt = pathinfo(ltrim($link, '/'), PATHINFO_FILENAME);

            // Remove file extension from the page slug
            $pageSlugWithoutExt = pathinfo($pageSlug, PATHINFO_FILENAME);

            // Extract the existing class (if any)
            preg_match('/class\s*=\s*"([^"]*)"/i', $matches[1] . $matches[3], $classMatches);
            $existingClass = isset($classMatches[1]) ? $classMatches[1] : '';

            // Check if the link matches the page slug (ignoring extensions)
            if ($linkWithoutExt === $pageSlugWithoutExt) {
                // Add the 'active-menu-item' class
                $newClass = $existingClass ? $existingClass . ' active-menu-item' : 'active-menu-item';
                $classAttribute = 'class="' . $newClass . '"';
                
                // Add or replace the class attribute
                $beforeAttributes = preg_replace('/class\s*=\s*"[^"]*"/i', '', $matches[1]);
                $afterAttributes = preg_replace('/class\s*=\s*"[^"]*"/i', '', $matches[3]);
                return '<a ' . trim($beforeAttributes) . ' ' . $classAttribute . ' ' . trim($afterAttributes) . ' href="' . $matches[2] . '">' . $matches[4] . '</a>';
            }

            // If no match, return the <a> tag unchanged
            return '<a ' . trim($matches[1]) . 'href="' . $matches[2] . '" ' . trim($matches[3]) . '>' . $matches[4] . '</a>';
        };

        // Perform the replacement using preg_replace_callback to apply the callback to each match
        return preg_replace_callback($pattern, $callback, $headerCode);
    }

    // Function to handle base64 image and save it
    public function saveBase64Image($base64Image, $domain)
    {
        // Extract the image type from the base64 string (before the comma)
        preg_match('/data:image\/([a-zA-Z]*);base64/', $base64Image, $matches);

        // If no image type is found, return false
        if (!isset($matches[1])) {
            return false;
        }

        // Get the file extension (e.g., jpg, png, etc.)
        $imageExtension = $matches[1];

        // Check if this image has already been processed by checking in the cache
        if (isset($this->imageCache[$base64Image])) {
            // If the image has been processed, reuse the cached file name
            return "/img/{$this->imageCache[$base64Image]}";
        }

        // Generate a unique file name with the correct extension
        $imageName = uniqid() . '.' . $imageExtension;

        // Remove the data:image part of the base64 string to get just the encoded image data
        $imageData = substr($base64Image, strpos($base64Image, ',') + 1);

        // Decode the base64 image data
        $imageDataDecoded = base64_decode($imageData);

        // Ensure the decoding was successful
        if ($imageDataDecoded === false) {
            return false;
        }

        // Define the path where the image will be saved
        $imgPath = "/var/www/templates/{$domain}/img/{$imageName}";

        // Ensure the target directory exists
        if (!file_exists(dirname($imgPath))) {
            mkdir(dirname($imgPath), 0755, true);
        }

        // Save the image to the server
        if (file_put_contents($imgPath, $imageDataDecoded) === false) {
            return false;
        }

        // Store the base64 image content and the generated file name in the cache
        $this->imageCache[$base64Image] = $imageName;


        // Return the relative path of the saved image
        return "/img/{$imageName}";
    }

    private function buildFullHtml($data)
    {


        try {
        // Call the function to add the 'active' class to the menu items
        $data['header_html'] = $this->addActiveClassToMenu($data['header_html'], $data['page_slug']);
    } catch (Exception $e) {
        // Log the error for debugging purposes
        Notification::make()
            ->danger()
            ->title('Menu Class Error')
            ->body("Failed to update menu items with the active class. Please try again later.")
            ->send();            
    }

        $boilerplate = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{$data['meta_description']}">
    {$data['og_tags']}
    <title>{$data['title']}</title>
    <style>
        {$data['header_css']}
        {$data['page_css']}
        {$data['footer_css']}
    </style>
    <!-- Favicon -->
    <link rel="icon" href="{$data['fav_icon']}" type="image/png">
    {$data['header_embed']}
</head>
<body>
    {$data['header_html']}
    {$data['page_html']}
    {$data['footer_html']}
    {$data['footer_embed']}
</body>
</html>
HTML;

        return $boilerplate;
    }

    public function copyImage($sourcePath, $destinationPath)
    {
        // Check if the source file exists
        if (File::exists($sourcePath)) {
            // Create the destination folder if it doesn't exist
            $destinationFolder = dirname($destinationPath);
            if (!File::exists($destinationFolder)) {
                if (!File::makeDirectory($destinationFolder, 0755, true)) {
                    return 'danger';
                }  // Creates the directory with the right permissions
            }

            // Copy the file to the new destination
            if (File::copy($sourcePath, $destinationPath)) {
                return "success";
            } else {
                return 'danger';
            }
        }

        return "danger";
    }

    public function deleteLiveWebsite()
    {
        // Retrieve the domain associated with the project
        $domain = $this->template->domain;

        // If the domain is empty, there's no folder to delete
        if (empty($domain)) {
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('Domain Error')
                    ->body("Domain not found for this template.")
                    ->send();

                return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

            } else {
                return [
                    'status' => 'danger',
                    'title' => 'Domain Error',
                    "body" => "Domain not found for this template.",
                ];
            }
        }

        // Define the target folder path
        $targetFolder = "/var/www/templates/{$domain}";

        // Check if the folder exists
        if (file_exists($targetFolder)) {
            // Recursive function to delete files and directories
            $this->deleteDirectory($targetFolder);

            // Optionally, update the project status to indicate it's no longer live
            $this->template->update([
                'live' => false,
            ]);


            if ($this->shouldRedirect) {
                // Send success notification
                Notification::make()
                    ->success()
                    ->title('template successfully take down')
                    ->send();
                // Redirect or refresh logic
                return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

            } else {
                return [
                    'status' => 'success',
                ];
            }

        } else {

            $this->template->live = false;
            $this->template->save();  // Save the model to the database

            if ($this->shouldRedirect) {
                // Handle case where the folder doesn't exist
                Notification::make()
                    ->danger()
                    ->title('No Live template Found')
                    ->body('There is no active template associated with this domain.')
                    ->send();
                // Redirect or refresh logic
                return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

            } else {
                return [
                    'status' => 'danger',
                    'title' => 'No live template found for this domain',
                    'body' => 'There is no active template associated with this domain.',
                ];
            }
        }
    }

    /**
     * Recursively delete a directory and its contents.
     *
     * @param string $dir
     * @return void
     */
    private function deleteDirectory($dir)
    {
        // Check if the directory exists
        if (!is_dir($dir)) {
            return;
        }

        // Get all files and subdirectories inside the target directory
        $files = array_diff(scandir($dir), ['.', '..']);

        // Loop through files and subdirectories and delete them
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                // Recursively delete subdirectories
                $this->deleteDirectory($filePath);
            } else {
                // Delete the file
                unlink($filePath);
            }
        }

        // Remove the now-empty directory
        rmdir($dir);
    }

    public function updateLiveWebsite()
    {

        // Temporarily disable redirection
        $this->shouldRedirect = false;


        $delete = $this->deleteLiveWebsite();



        if ($delete['status'] === 'success') {

            $live = $this->liveWebsite();

        }
        elseif($delete['status'] === 'danger'){

            $title = $delete['title'];  // Set title for live
            $body = $delete['body'];    // Set body for live
            
            // Send notification
            Notification::make()
                ->danger()
                ->title($title)
                ->body($body)
                ->send();

            return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        }
        else{
            Notification::make()
                ->danger()
                ->title('Something Went Wrong')
                ->body("Please try again later.")
                ->send();
            return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        }

        if ($live['status'] === 'danger' || $delete['status'] === 'danger') {
            // Determine the title and body based on conditions
            $title = '';
            $body = '';

            if ($live['status'] === 'danger') {
                $title = $live['title'];  // Set title for live
                $body = $live['body'];    // Set body for live
            }

            if ($delete['status'] === 'danger') {
                // If both live and delete are danger, append to the title and body
                $title .= ($title ? ' & ' : '') . $delete['title'];  // If title already set, append delete title
                $body .= ($body ? ' ' : '') . $delete['body'];       // If body already set, append delete body
            }

            // Send notification
            Notification::make()
                ->danger()
                ->title($title)
                ->body($body)
                ->send();

            return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        } elseif ($live['status'] === 'success' && $delete['status'] === 'success') {
            Notification::make()
                ->success()
                ->title('Template updated successfully')
                ->send();
            return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        } else {
            Notification::make()
                ->danger()
                ->title('Something Went Wrong')
                ->body("Please try again later.")
                ->send();
            return redirect('/templates/starter/edit' . '/' . $this->template->template_id);

        }
    }

    // Delete the template
    public function delete(): void
    {

        // Check if the project is live
        if ($this->template->live) {
            // Temporarily disable redirection
            $this->shouldRedirect = false;
            $delete = $this->deleteLiveWebsite();

            if ($delete['status'] === 'danger') {

                $title = $delete['title'];  // Set title for live
                $body = $delete['body'];    // Set body for live

                // Send notification
                Notification::make()
                    ->danger()
                    ->title($title)
                    ->body($body)
                    ->send();

                $this->redirect('/websites' . '/' . $this->project->project_id);
                return;
            }
        }


        // Define the target folder path
        $targetFolder = "/var/www/ezysite/public/storage/templates/{$this->template->template_id}";

        // Check if the folder exists
        if (file_exists($targetFolder)) {
            // Recursive function to delete files and directories
            $this->deleteDirectory($targetFolder);
        }
        $this->header->delete();
        $this->footer->delete();
        foreach ($this->pages as $page) {
            $page->delete();
        }
        $this->template->delete();

        Notification::make()
            ->success()
            ->title('Template deleted successfully')
            ->send();

        $this->redirect('/templates/starter/');
    }



}
    ?>
<x-layouts.app>
    @volt('edit')
    <x-app.container>
        <div class="container mx-auto my-6">

            <!-- Back Button -->
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to {{ $this->categoryName }}"
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
                    <x-app.heading title="Editing: {{ $template->template_name }}"
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



                
                 <!-- Domain or URL -->
                <div class="space-y-2">
                    <h3 class="text-lg font-medium">Live Template</h3>
                    @if($this->template->domain)
                    <a href="{{ 'https://' . $this->template->domain . $this->ourDomain}}"
                    class="text-blue-600 hover:underline"
                    target="_blank">{{ 'https://' . $this->template->domain . $this->ourDomain}}</a>
                    @else
                    <p class="text-gray-500">No domain assigned</p>
                    @endif
                </div>
             
                
                <!-- Live Status Indicator -->
                <div class="flex items-center">
                    <span class="text-sm font-medium mr-2">Live Status:</span>
                    @if($this->template->live)
                    <span class="text-green-500">Live</span>
                    @else
                    <span class="text-red-500">Not Live</span>
                    @endif
                </div>








                                <!-- Public Status Indicator -->
                <div class="flex items-center">
                    <span class="text-sm font-medium mr-2">Publish Status:</span>
                    @if($this->template->is_publish)
                    <span class="text-green-500">Published</span>
                    @else
                    <span class="text-red-500">Not Published</span>
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
                <li class="mr-2">
                    <a
                        @click.prevent="activeTab = 'live-settings'" 
                        :class="{ 'text-blue-600 border-blue-500': activeTab === 'live-settings' }" 
                        class="tab-btn inline-block p-4 rounded-t-lg border-b-2 cursor-pointer select-none"
                    >
                        Live Settings
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
                        <x-button type="button" wire:click="delete"
                            color="danger" wire:confirm="Are you sure you want to delete this template?">
                            Delete Template
                        </x-button>
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
                                <a href="#" wire:confirm="Are you sure you want to delete this page?" wire:click="deletePage({{ $page->id }})"
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
                <!-- Live Settings Box -->
                <div 
    x-show="activeTab === 'live-settings'" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    x-cloak 
    id="live-settings" 
    class="space-y-6 tab-panel">
                    <div class="flex items-center justify-between mb-5">
                        <!-- Display the current project name as a heading -->
                        <x-app.heading title="Live Settings" description="Set up your domain and publish your template online." :border="false" />
                    </div>
                
                    <!-- Subdomain Input -->
                    <div class="grid gap-y-2">
                        <label for="subdomain" class="block">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Subdomain</span>
                            </label>
                            <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-text-input overflow-hidden">
                                <input type="text" id="subdomain" name="subdomain" wire:model="liveData.domain" wire:keyup.debounce.1000ms="check"
                                class="form-control fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3"
                                placeholder="Your Subdomain" value="{{ old('subdomain') }}" required>
                        </div>
                            <span>{!! $this->message !!}</span>
                    </div>


                
                    <div x-data="{ open: false }" class="grid gap-y-2">
                        <div class="flex justify-between items-center">
                                <div @click="open = !open" class="cursor-pointer text-md font-semibold w-full flex justify-between">
                                    <span>Advanced Settings</span>
                                    <!-- Arrow Icon for Collapsible -->
                                    <svg x-bind:class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform duration-300" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>

                        <div x-show="open" x-transition.duration.300ms x-cloak class="mt-4 space-y-3 bg-white-100 p-4 rounded-lg rounded-md shadow-md">
                        <!-- Pages Selection -->
                        <div class="grid gap-y-2">
                            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3"><span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                Select Pages
                            </span>
                            </label>
                            @foreach($pages as $page)
                                <div class="flex items-center mb-2">
                                    <label for="{{$page->id}}" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                                    <input
                                    id="{{$page->id}}"
                                    type="checkbox" wire:model="liveData.pages" value="{{ $page->id }}"
                                    wire:loading.attr="disabled" 
                                    class = "fi-checkbox-input rounded border-none bg-white shadow-sm ring-1 transition duration-75 checked:ring-0 focus:ring-2 focus:ring-offset-0 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-current disabled:checked:text-gray-400 dark:bg-white/5 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600 text-primary-600 ring-gray-950/10 focus:ring-primary-600 checked:focus:ring-primary-500/50 dark:text-primary-500 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500 dark:checked:focus:ring-primary-400/50 dark:disabled:ring-white/10">
                                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">{{ $page->name }}</span>
                                        @if($page->main)
                    <span class="text-gray-500 text-xs">(Main Page)</span>
                @endif
                                    </label>
                                </div>
                            @endforeach
                            @error('liveData.pages')
                                <div class="text-red-600 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                    
                        <!-- Header Option -->
                        <div class="grid gap-y-2">
                             <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Select Header & Footer</span>
                            </label>

                            <div class="flex items-center mb-2">
                                <label for="header" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                            <input
                            wire:loading.attr="disabled"
                            type="checkbox"
                            id="header"
                            wire:model="liveData.header" class="fi-checkbox-input rounded border-none bg-white shadow-sm ring-1 transition duration-75 checked:ring-0 focus:ring-2 focus:ring-offset-0 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-current disabled:checked:text-gray-400 dark:bg-white/5 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600 text-primary-600 ring-gray-950/10 focus:ring-primary-600 checked:focus:ring-primary-500/50 dark:text-primary-500 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500 dark:checked:focus:ring-primary-400/50 dark:disabled:ring-white/10">    
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Include Header</span>
                            </label>
                            @error('liveData.header')
                                <div class="text-red-600 text-sm">{{ $message }}</div>
                            @enderror
                            </div>
                    
                            <!-- Footer Option -->
                            <div class="flex items-center mb-2">
                            <label for="footer" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                            <input wire:loading.attr="disabled"
                            type="checkbox"
                            id="footer" wire:model="liveData.footer" class="fi-checkbox-input rounded border-none bg-white shadow-sm ring-1 transition duration-75 checked:ring-0 focus:ring-2 focus:ring-offset-0 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-current disabled:checked:text-gray-400 dark:bg-white/5 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600 text-primary-600 ring-gray-950/10 focus:ring-primary-600 checked:focus:ring-primary-500/50 dark:text-primary-500 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500 dark:checked:focus:ring-primary-400/50 dark:disabled:ring-white/10"> 
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Include Footer</span></label>
                            @error('liveData.footer')
                                <div class="text-red-600 text-sm">{{ $message }}</div>
                            @enderror
                            </div>
                        </div>
                        </div>
                    </div>
                
                    <div class="flex justify-end gap-x-3 mt-3">
                    
                    @if ($this->template->live)
                        <!-- Update Button -->
                        <x-button wire:click="updateLiveWebsite" type="submit" color="primary">
                            Update Site
                        </x-button>
                            <x-button color="danger" type="button" wire:click="deleteLiveWebsite"  wire:confirm="Are you sure you want to take down this template?">Take Down</x-button>
                    @else
                        <!-- Cancel Button -->
                    <x-button tag="a" href="/templates/starter/{{ $template->template_category_id }}" color="secondary">Cancel</x-button>
                    <!-- Make it Live Button -->
                    <x-button wire:click="liveWebsite" type="submit" color="primary">
                        Go Live
                    </x-button>
                    <x-button wire:click="saveDomain" type="submit" color="gray">
                            Reserve Your Domian
                        </x-button>
                    @endif
                    </div>
                </div>








</div>
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>