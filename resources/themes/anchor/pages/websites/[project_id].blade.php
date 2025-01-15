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
use Illuminate\Support\Str;
use App\Models\Project;
use App\Models\HeaderFooter;
use App\Models\PrivateTemplate;
use App\Models\Template;
use App\Models\TemplateHeaderFooter;
use App\Models\TempPage;
use App\Models\TemplateCategory; // Import TemplateCategory model
use App\Models\Subdomain;
use App\Models\WebPage; // Assuming you have the WebPage model
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('websites.edit'); // Name the route

new class extends Component implements HasForms {
    use InteractsWithForms;

    public $selectedPage = null;
    public $project_id; // The project_id from the URL
    public Project $project;  // Holds the project instance
    public ?array $data = []; // Holds form data
    public $pages;
    public $pageData = [];
    public $header;
    public $footer;
    public $mainPage;
    public $liveData = [];
    public $shouldRedirect = true; // Flag to control redirection
    public $ourDomain = ".test.wpengineers.com";
    public $imageCache = [];
    public $message = '';
    public $isPreview = false;

    // Mount method to set the project_id from the URL and fetch the project
    public function mount($project_id): void
    {


        $this->project_id = $project_id; // Set the project_id dynamically from the URL

        // Retrieve the project using the project_id and authenticate it
        $this->project = auth()->user()->projects()->where('project_id', $this->project_id)->firstOrFail();
        $this->mainPage = WebPage::where('website_id', $this->project_id)
            ->where('main', true)
            ->first();

        $this->header = HeaderFooter::where("website_id", $this->project_id)
            ->where("is_header", true)
            ->first();
        $this->footer = HeaderFooter::where("website_id", $this->project_id)
            ->where("is_header", false)
            ->first();
        if (!$this->header) {
            $this->header = $this->headerCreate();
        }
        if (!$this->footer) {
            $this->footer = $this->footerCreate();
        }
        



        $this->liveData = [
            'domain' => $this->project->domain,
            'pages' => [],    // Default empty array for selected pages
            'header' => true, // Default value for header
            'footer' => true, // Default value for footer
            'global_header_embed' => $this->project->header_embed,
            'global_footer_embed' => $this->project->footer_embed,
            'robots_txt' => $this->project->robots_txt,
        ];

        // Retrieve pages for the project
        $this->pages = WebPage::where('website_id', $this->project_id)->get();

        // Pre-fill the form with existing page data
        foreach ($this->pages as $page) {
            $this->pageData[$page->id] = [
                'page_name' => $page->name,
                'page_title' => $page->title,
                'meta_description' => $page->meta_description,
                'og_tags' => $page->og,
                'header_embed_code' => $page->embed_code_start,
                'footer_embed_code' => $page->embed_code_end,
                'page_slug' => $page->slug,
            ];
        }
        ;

        if (!$this->mainPage && $this->pages->isNotEmpty()) {
            $this->mainPage = $this->pages->first();
            $this->mainPage->main = true; // Assuming `main` is the column to mark the main page
            $this->mainPage->save(); // Save the updated main page
        }

        $this->liveData['pages'] = $this->pages->pluck('id')->toArray(); // Populate with all page IDs

        // Pre-fill the form with existing project data
        $this->form->fill([
            'rename' => $this->project->project_name,
            'description' => $this->project->description, // Pre-fill the description
            'robots_txt' => $this->project->robots_txt, // Add robots.txt field
            'header_embed' => $this->project->header_embed, // Add embed code for header
            'footer_embed' => $this->project->footer_embed, // Add embed code for footer
        ]);
    }




    public function updatePageList($pgId)
    {

        // Check if the selected page is the same as the clicked page
        if ($this->selectedPage === $pgId) {
            // If it is, deselect it (remove the ID and collapse the page)
            $this->selectedPage = null;
        } else {
            // Otherwise, select the new page and fill the form
            $this->selectedPage = $pgId;

            $pageInstance = WebPage::find($this->selectedPage); // Find the selected page
            if ($pageInstance) {
                $this->form->fill([
                    'page_name' => $pageInstance->name,
                    'page_title' => $pageInstance->title,
                    'page_meta_description' => $pageInstance->meta_description,
                    'page_og_tags' => $pageInstance->og,
                    'page_header_embed_code' => $pageInstance->embed_code_start,
                    'page_footer_embed_code' => $pageInstance->embed_code_end,
                ]);
            }
        }
    }







    // Define the form schema
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Rename Website Field
                TextInput::make('rename')
                    ->label('Website Name')
                    ->placeholder('Enter website name')
                    ->maxLength(255),

                // Description Textarea
                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Describe your website')
                    ->rows(5)
                    ->maxLength(1000),

                // Logo Uploader
                FileUpload::make('logo')
                    ->label('Upload Favicon')
                    ->image()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('260')
                    ->imageResizeTargetHeight('260')
                    ->directory("usersites/{$this->project->project_id}")
                    ->disk('public')
                    ->maxSize(1024)
                    ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png',])
                    ->helperText('Upload a favicon for your website'),

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
            'template_name' => $this->project->project_name . ' - My Template',
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




        public function saveAsPublicTemplate(): void
    {

        if(!Gate::allows('create-template')){

            abort(404);
        }

    $robotsTxt = $this->project->robots_txt;
    $headerEmbedGlobal = $this->project->header_embed;
    $footerEmbedGlobal = $this->project->footer_embed;
    $favIcon = $this->project->favicon;

    $headerHtml = $this->header->html;
    $footerHtml = $this->footer->html;
    $headerCss = $this->header->css;
    $footerCss = $this->footer->css;
    $projectJson = $this->project->project_json;
    $headerJson = $this->header->json;
    $footerJson = $this->footer->json;


        // Check if the name is missing
    if (empty($this->project->project_name)) {
        Notification::make()
            ->danger()
            ->title('Error')
            ->body('Name is invalid or missing.')
            ->send();
        return;
    }

    // Check if pages data is missing or invalid
    if (empty($this->pages)) {
        Notification::make()
            ->danger()
            ->title('Error')
            ->body('Pages are missing.')
            ->send();
        return;
    }

     foreach ($this->pages as $page) {
            // Validate 'name' field
            if (empty($page['name'])) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body("Template's Page 'name' field is missing or invalid.")
                    ->send();
                return;
            }

            // Validate 'page_id' field
            if (is_null($page['page_id'])) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body("Template's Page 'page_id' field is missing or invalid.")
                    ->send();
                return;
            }

            // Validate 'slug' field
            if (empty($page['slug']) || !is_string($page['slug'])) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body("Template's Page 'slug' field is missing or invalid.")
                    ->send();
                return;
            }

            // Validate 'title' field
            if (empty($page['title']) || !is_string($page['title'])) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body("Template's Page 'title' field is missing or invalid.")
                    ->send();
                return;
            }

            // Validate 'html' field
            if (empty($page['html']) || !is_string($page['html'])) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body("Template's Page 'html' field is missing or invalid.")
                    ->send();
                return;
            }

            // Validate 'css' field
            if (empty($page['css']) || !is_string($page['css'])) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body("Template's Page 'css' field is missing or invalid.")
                    ->send();
                return;
            }
        }


        // Check if the Template Category "From Websites" exists
        $category = TemplateCategory::where('name', 'From Websites')->first();
         // If category doesn't exist, create it
        if (!$category) {
            $category = TemplateCategory::create([
                'name' => 'From Websites',
                'description' => 'Templates created from websites.',
            ]);
        }

        


        $template = Template::create([
        'template_name' => $this->project->project_name . ' (Public Template)',
        'description' => $this->project->description,
        'template_json' => $projectJson, // Use the project JSON for the new template
        'header_embed' => $headerEmbedGlobal,
        'footer_embed' => $footerEmbedGlobal,
        'robots_txt' => $robotsTxt,
        'favicon' => $favIcon,
        'template_category_id' => $category->id, // Assign the category ID
    ]);




    if ($this->project->favicon) {
        $sourcePath = "/var/www/ezysite/public/storage/usersites/{$this->project->project_id}/logo/{$this->project->favicon}";
        if (File::exists($sourcePath)) {
            $destinationPath = "/var/www/ezysite/public/storage/templates/{$template->template_id}/logo/{$template->favicon}";
            $result = $this->copyImage($sourcePath, $destinationPath);
            if ($result === 'danger') {
                Notification::make()
                    ->danger()
                    ->title('Favicon not found')
                    ->send();
            }
        }
    }


    // Create header and footer entries and link them to the template
    $header = TemplateHeaderFooter::create([
        'template_id' => $template->template_id,
        'json' => $headerJson,
        'html' => $headerHtml,
        'css' => $headerCss,
        'is_header' => true,
    ]);

     $footer = TemplateHeaderFooter::create([
        'template_id' => $template->template_id,
        'json' => $footerJson,
        'html' => $footerHtml,
        'css' => $footerCss,
        'is_header' => false,
    ]);



    foreach ($this->pages as $page) {
        TempPage::create([
            'page_id' => $page['page_id'],
            'name' => $page['name'],
            'slug' => $page['slug'],
            'title' => $page['title'],
            'meta_description' => $page['meta_description'],
            'main' => $page['main'],
            'og' => $page['og'],
            'embed_code_start' => $page['embed_code_start'],
            'embed_code_end' => $page['embed_code_end'],
            'html' => $page['html'],
            'css' => $page['css'],
            'template_id' => $template->template_id, // Associate the page with the template
        ]);
    }



        Notification::make()
            ->success()
            ->title('Template created successfully from project')
            ->send();

        $this->redirect('/templates/starter/' . $category->id . '/'); // Redirect to the user's private templates page
    }




    // Edit the project details
    public function edit(): void
    {
        $data = $this->form->getState();


        // Only update project_name if it is not empty
        if (!empty($data['rename'])) {
            $this->project->update([
                'project_name' => $data['rename'],
            ]);
        }

        // Update project details
        $this->project->update([
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
                    $this->project->update([
                        'favicon' => "{$this->project->project_id}." . pathinfo($file, PATHINFO_EXTENSION),
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
            ->title('Website updated successfully')
            ->send();

        $this->redirect('/websites' . '/' . $this->project->project_id);
    }

    // Duplicate the project
    public function duplicate(): void
    {
        $newProject = $this->project->replicate();

// Generate a unique project name by appending a number
$newProjectName = $this->project->project_name;
$counter = 1;

// Check if a project with the same name already exists
while (Project::where('project_name', $newProjectName)->exists()) {
    // Append the counter in the correct format
    $newProjectName = $this->project->project_name . ' (' . $counter . ')';
    $counter++;
}

// Set the new unique project name
$newProject->project_name = $newProjectName;

// Check if the project has a domain set
if ($this->project->domain) {
    // Initialize the base domain name
    $domainBase = $this->project->domain;
    $newDomain = $domainBase;
    $counter = 1;

    // Check if the domain already exists in the database
    while (Project::where('domain', $newDomain)->exists()) {
        // Append the counter to the base domain until a unique domain is found
        $newDomain = $domainBase . $counter;
        $counter++;
    }

    // Set the new unique domain
    $newProject->domain = $newDomain;
}

if ($newProject->live) {
    $newProject->live = false;
}

$newProject->save();


        // Duplicate associated files
    $sourceFolder = "/var/www/ezysite/public/storage/usersites/{$this->project->project_id}";
    $targetFolder = "/var/www/ezysite/public/storage/usersites/{$newProject->project_id}";

    if (file_exists($sourceFolder)) {
        // Create the target folder if it doesn't exist
        if (!file_exists($targetFolder)) {
            mkdir($targetFolder, 0777, true);
        }

        // Recursive function to copy files and directories
        $this->copyDirectory($sourceFolder, $targetFolder);
    }

    // Duplicate header and footer
    $newHeader = $this->header->replicate();
    $newHeader->website_id = $newProject->project_id;
    $newHeader->save();
    $newFooter = $this->footer->replicate();
    $newFooter->website_id = $newProject->project_id;
    $newFooter->save();

    foreach ($this->pages as $page) {
        $newPage = $page->replicate();
        $newPage->website_id = $newProject->project_id;
        $newPage->save();
    }


        Notification::make()
            ->success()
            ->title('Website duplicated successfully')
            ->send();

        $this->redirect('/websites' . '/' . $newProject->project_id);
    }

    // Recursive function to copy files and directories
    public function copyDirectory($source, $target)
    {
        // Check if the source is a file or directory
        if (is_file($source)) {
            copy($source, $target);
        } elseif (is_dir($source)) {
            // Create the target directory if it doesn't exist
            if (!is_dir($target)) {
                mkdir($target, 0777, true);
            }

            // Get all files and subdirectories inside the source directory
            $files = array_diff(scandir($source), ['.', '..']);

            // Loop through files and subdirectories and copy them
            foreach ($files as $file) {
                $filePath = $source . DIRECTORY_SEPARATOR . $file;
                $targetPath = $target . DIRECTORY_SEPARATOR . $file;

                if (is_dir($filePath)) {
                    // Recursively copy subdirectories
                    $this->copyDirectory($filePath, $targetPath);
                } else {
                    // Copy the file
                    copy($filePath, $targetPath);
                }
            }
        }
    }

    // Delete the project
    public function delete(): void
    {

         // Check if the project is live
    if ($this->project->live) {
        // Temporarily disable redirection
        $this->shouldRedirect = false;
    $delete = $this->deleteLiveWebsite();



        if($delete['status'] === 'danger'){

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
        $targetFolder = "/var/www/ezysite/public/storage/usersites/{$this->project->project_id}";

        // Check if the folder exists
        if (file_exists($targetFolder)) {
            // Recursive function to delete files and directories
            $this->deleteDirectory($targetFolder);
        }
        $this->deletePreview();
        $this->header->delete();
        $this->footer->delete();
        foreach ($this->pages as $page) {
            $page->delete();
        }
        $this->project->delete();

        Notification::make()
            ->success()
            ->title('Website deleted successfully')
            ->send();

        $this->redirect('/websites');
    }





    public function convertToSlug($string)
    {
        // Convert to lowercase
        $string = strtolower($string);

        // Replace spaces with hyphens
        $string = str_replace(' ', '-', $string);

        // Escape special characters and remove unwanted characters
        $string = preg_replace('/[^a-z0-9-]/', '', $string);

        // Remove multiple hyphens if they exist
        $string = preg_replace('/-+/', '-', $string);

        // Trim hyphens from the beginning and end of the string
        $string = trim($string, '-');

        return $string;
    }


    // Update the page data
    public function pageUpdate($pageId)
    {
        $page = WebPage::find($pageId);
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
                WebPage::where('slug', $slug)
                    ->where('website_id', $this->project->project_id)
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
                $title = $data["page_name"] . " - " . $this->project->project_name;
            }



            $page->update([
                'title' => $title,
                'meta_description' => $data['meta_description'],
                'og' => $data['og_tags'],
                'embed_code_start' => $data['header_embed_code'],
                'embed_code_end' => $data['footer_embed_code'],
                'slug' => $slug,
            ]);

            Notification::make()->success()->title('Page data updated successfully.')->send();
            $this->redirect('/websites' . '/' . $this->project->project_id);

        } else {
            Notification::make()->danger()->title('Page not found.')->send();
            $this->redirect('/websites' . '/' . $this->project->project_id);
        }
    }

    // Update the main page
    public function pageMain($pageId)
    {
        $pageInstance = WebPage::find($pageId); // Find the selected page
        if ($pageInstance) {


            // Set all pages with the same project_id to false
            WebPage::where('website_id', $pageInstance->website_id)
                ->update(['main' => false]);

            // Set the selected page as main
            $pageInstance->main = true;
            $pageInstance->save();

            Notification::make()->success()->title('Main Page updated successfully.')->send();
            $this->redirect('/websites' . '/' . $this->project->project_id);

        } else {
            Notification::make()->danger()->title('Page not found.')->send();
            $this->redirect('/websites' . '/' . $this->project->project_id);

        }
    }



    public function headerCreate()
    {


        // Get the data from the request
        $websiteId = $this->project->project_id;  // Website ID
        $jsonData = json_encode([
            "assets" => [],
            "styles" => [],
            "pages" => [
                [
                    "name" => "Header",
                ]
            ],
            "symbols" => [],
            "dataSources" => []
        ]);
        $html = '<body></body>';              // HTML content
        $css = '* { box-sizing: border-box; } body {margin: 0;}';                // CSS content


        // Create a new header and footer record
        $this->header = HeaderFooter::create([
            'website_id' => $websiteId,
            'json' => $jsonData,
            'html' => $html,
            'css' => $css,
            'is_header' => true,
        ]);
    }

    public function footerCreate()
    {


        // Get the data from the request
        $websiteId = $this->project->project_id;  // Website ID
        $jsonData = json_encode([
            "assets" => [],
            "styles" => [],
            "pages" => [
                [
                    "name" => "Footer",
                ]
            ],
            "symbols" => [],
            "dataSources" => []
        ]);
        $html = '<body></body>';              // HTML content
        $css = '* { box-sizing: border-box; } body {margin: 0;}';                // CSS content


        // Create a new header and footer record
        $this->footer = HeaderFooter::create([
            'website_id' => $websiteId,
            'json' => $jsonData,
            'html' => $html,
            'css' => $css,
            'is_header' => false,
        ]);
    }


    public function resetHeaderToDefault()
    {

        if ($this->header) {
            $this->header->delete();  // Delete the current header
        }

        Notification::make()
            ->success()
            ->title('Header updated successfully')
            ->send();

        $this->redirect('/websites' . '/' . $this->project->project_id);
    }

    public function resetFooterToDefault()
    {

        if ($this->footer) {
            $this->footer->delete();  // Delete the current header and footer
        }

        Notification::make()
            ->success()
            ->title('Footer updated successfully')
            ->send();

        $this->redirect('/websites' . '/' . $this->project->project_id);
    }



    public function previewWebsite()
    {
        // Access liveData array
        $domain = $this->project->project_id;
        $pages = $this->pages->pluck('id')->toArray(); // Selected pages array
        $header = true;
        $footer = true;
        $globalHeaderEmbed = $this->liveData['global_header_embed'] ?? '';
        $globalFooterEmbed = $this->liveData['global_footer_embed'] ?? '';


         if(!$this->mainPage){
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('No Main Page')
                    ->body('You must select a main page for your website to preview.')
                    ->send();

                return redirect('/websites' . '/' . $this->project->project_id);
            } else {
                return [
                    'status' => 'danger',
                    'title' => 'No Main Page',
                    "body" => 'You must select a main page for your website to preview.',
                ];
            }
        }


        // Ensure the target folder exists
        $targetFolder = "/var/www/ezysite/resources/views/preview/{$domain}";
        if (!file_exists($targetFolder)) {
            if (!mkdir($targetFolder, 0755, true)) {


                if ($this->shouldRedirect) {
                    Notification::make()
                        ->danger()
                        ->title('Domain Configuration Error')
                        ->body("Failed to create preview for the domain: {$domain}")
                        ->send();

                    return redirect('/websites' . '/' . $this->project->project_id);
                } else {
                    return [
                        'status' => 'danger',
                        'title' => 'Domain Configuration Error',
                        "body" => "Failed to create preview for the domain: {$domain}",
                    ];
                }

            }
        }



        // Fetch header and footer if required
        $headerHtml = '';
        $headerCss = '';
        $footerHtml = '';
        $footerCss = '';

        // Fetch header and footer data
        if ($header) {
            $header = HeaderFooter::where("website_id", $this->project->project_id)
                ->where("is_header", true)
                ->first();
            $headerHtml = $header->html;
            $headerCss = $header->css;
        }

        if ($footer) {
            $footer = HeaderFooter::where("website_id", $this->project->project_id)
                ->where("is_header", false)
                ->first();
            $footerHtml = $footer->html;
            $footerCss = $footer->css;
        }
        $favIcon = '';

        // Fetch and process each selected page
        foreach ($pages as $pageId) {
            $page = WebPage::find($pageId);

            if (!$page) {
                if ($this->shouldRedirect) {
                    Notification::make()
                        ->danger()
                        ->title('Page not found')
                        ->body("Failed to fetch page with ID: {$pageId}")
                        ->send();

                    return redirect('/websites' . '/' . $this->project->project_id);
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
                'page_html' => $pageHtml,
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

                    return redirect('/websites' . '/' . $this->project->project_id);
                } else {
                    return [
                        'status' => 'danger',
                        'title' => 'Page file Error',
                        "body" => "Failed to write HTML file for page: {$slug}",
                    ];
                }
            }
        }



        if ($this->shouldRedirect) {
            Notification::make()
                ->success()
                ->title('Website successfully made live at ' . $domain . $this->ourDomain)
                ->send();
            // Redirect or refresh logic
            return redirect('/websites' . '/' . $this->project->project_id);
        } else {
            return [
                'status' => 'success',
            ];
        }
    }


        public function deletePreview()
    {
        // Retrieve the domain associated with the project
        $domain = $this->project->prokect_id;


        // Define the target folder path
        $targetFolder = "/var/www/ezysite/resources/views/preview/{$domain}";

        // Check if the folder exists
        if (file_exists($targetFolder)) {
            // Recursive function to delete files and directories
            $this->deleteDirectory($targetFolder);



                return [
                    'status' => 'success',
                ];

        }
    }


    
    public function saveDomain(){
        $domain = $this->liveData['domain'];
        if(!$this->check()){
            Notification::make()
                ->danger()
                ->title('Domain Error')
                ->body('You must select correct domain for your website.')
                ->send();

            return redirect('/websites' . '/' . $this->project->project_id);
        }

        $domain = Str::slug($domain);

        // Update project domain
        $this->project->update([
            'domain' => $domain,
        ]);

        

        Notification::make()
            ->success()
            ->title('Domain Reserved')
            ->body('The domain for your website has been successfully reserved.')
            ->send();

        $this->redirect('/websites' . '/' . $this->project->project_id);
    }





    public function liveWebsite() {
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
                    ->body('You must select correct domain for your website to live.')
                    ->send();

                return redirect('/websites' . '/' . $this->project->project_id);
            } else {
                return [
                    'status' => 'danger',
                    'title' => 'Domain Error',
                    "body" => 'You must select correct domain for your website to live.',
                ];
            }
        }

        $domain = Str::slug($domain);

        // Update project domain
        $this->project->update([
            'domain' => $domain,
        ]);


        // Check if at least one page is selected
        if (empty($pages)) {
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('Error: No Pages Selected')
                    ->body('You must select at least one page to make the website live.')
                    ->send();

                return redirect('/websites' . '/' . $this->project->project_id);
            } else {
                return [
                    'status' => 'danger',
                    'title' => 'No Pages Selected',
                    "body" => 'You must select at least one page to make the website live.',
                ];
            }
        }





    $mainPageExists = false; // Initialize a flag for the main page

    // Iterate through the selected pages to check if the main page is included
    foreach ($pages as $pageId) {
        $page = WebPage::find($pageId); // Replace `Page` with your actual model name
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
                ->body('You must select a main page for your website to live.')
                ->send();

            return redirect('/websites' . '/' . $this->project->project_id);
        } else {
            return [
                'status' => 'danger',
                'title' => 'No Main Page',
                "body" => 'You must select a main page for your website to live.',
            ];
        }
    }


        if(!$this->mainPage){
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('No Main Page')
                    ->body('You must select a main page for your website to live.')
                    ->send();

                return redirect('/websites' . '/' . $this->project->project_id);
            } else {
                return [
                    'status' => 'danger',
                    'title' => 'No Main Page',
                    "body" => 'You must select a main page for your website to live.',
                ];
            }
        }

        // Ensure the target folder exists
        $targetFolder = "/var/www/domain/{$domain}";
        if (!file_exists($targetFolder)) {
            if (!mkdir($targetFolder, 0755, true)) {


                if ($this->shouldRedirect) {
                    Notification::make()
                        ->danger()
                        ->title('Domain Configuration Error')
                        ->body("Failed to create website for the domain: {$domain}")
                        ->send();

                    return redirect('/websites' . '/' . $this->project->project_id);
                } else {
                    return [
                        'status' => 'danger',
                        'title' => 'Domain Configuration Error',
                        "body" => "Failed to create website for the domain: {$domain}",
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

                return redirect('/websites' . '/' . $this->project->project_id);
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
            $header = HeaderFooter::where("website_id", $this->project->project_id)
                ->where("is_header", true)
                ->first();
            $headerHtml = $header->html;
            $headerCss = $header->css;
        }

        if ($footer) {
            $footer = HeaderFooter::where("website_id", $this->project->project_id)
                ->where("is_header", false)
                ->first();
            $footerHtml = $footer->html;
            $footerCss = $footer->css;
        }
        $favIcon = '';
        $sourcePath = "/var/www/ezysite/public/storage/usersites/{$this->project->project_id}/logo/{$this->project->favicon}";
        if (File::exists($sourcePath)) {

            $destinationPath = $targetFolder . "/img/{$this->project->favicon}";
            $result = $this->copyImage($sourcePath, $destinationPath);
            if ($result === 'danger') {
                Notification::make()
                    ->danger()
                    ->title('Favicon not found')
                    ->send();
            }
            $favIcon = "/img/{$this->project->favicon}";
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
            $page = WebPage::find($pageId);

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

                return redirect('/websites' . '/' . $this->project->project_id);
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
            $page = WebPage::find($pageId);

            if (!$page) {
                if ($this->shouldRedirect) {
                    Notification::make()
                        ->danger()
                        ->title('Page not found')
                        ->body("Failed to fetch page with ID: {$pageId}")
                        ->send();

                    return redirect('/websites' . '/' . $this->project->project_id);
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

                    return redirect('/websites' . '/' . $this->project->project_id);
                } else {
                    return [
                        'status' => 'danger',
                        'title' => 'Page file Error',
                        "body" => "Failed to write HTML file for page: {$slug}",
                    ];
                }
            }
        }

        // Update project domain
        $this->project->update([
            'live' => true,
        ]);


        if ($this->shouldRedirect) {
            Notification::make()
                ->success()
                ->title('Website successfully made live at ' . $domain . $this->ourDomain)
                ->send();
            // Redirect or refresh logic
            return redirect('/websites' . '/' . $this->project->project_id);
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
        $imgPath = "/var/www/domain/{$domain}/img/{$imageName}";

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
        $domain = $this->project->domain;

        // If the domain is empty, there's no folder to delete
        if (empty($domain)) {
            if ($this->shouldRedirect) {
                Notification::make()
                    ->danger()
                    ->title('Domain Error')
                    ->body("Domain not found for this project.")
                    ->send();

                return redirect('/websites' . '/' . $this->project->project_id);
            } else {
                return [
                    'status' => 'danger',
                    'title' => 'Domain Error',
                    "body" => "Domain not found for this project.",
                ];
            }
        }

        // Define the target folder path
        $targetFolder = "/var/www/domain/{$domain}";

        // Check if the folder exists
        if (file_exists($targetFolder)) {
            // Recursive function to delete files and directories
            $this->deleteDirectory($targetFolder);

            // Optionally, update the project status to indicate it's no longer live
            $this->project->update([
                'live' => false,
            ]);


            if ($this->shouldRedirect) {
                // Send success notification
                Notification::make()
                    ->success()
                    ->title('Website successfully take down')
                    ->send();
                // Redirect or refresh logic
                return redirect('/websites' . '/' . $this->project->project_id);
            } else {
                return [
                    'status' => 'success',
                ];
            }

        } else {

            $this->project->live = false;
            $this->project->save();  // Save the model to the database

            if ($this->shouldRedirect) {
                // Handle case where the folder doesn't exist
                Notification::make()
                    ->danger()
                    ->title('No Live Website Found')
                    ->body('There is no active website associated with this domain.')
                    ->send();
                // Redirect or refresh logic
                return redirect('/websites' . '/' . $this->project->project_id);
            } else {
                return [
                    'status' => 'danger',
                    'title' => 'No live website found for this domain',
                    'body' => 'There is no active website associated with this domain.',
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

            return redirect('/websites' . '/' . $this->project->project_id);
        }
        else{
            Notification::make()
                ->danger()
                ->title('Something Went Wrong')
                ->body("Please try again later.")
                ->send();
            return redirect('/websites' . '/' . $this->project->project_id);
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

            return redirect('/websites' . '/' . $this->project->project_id);
        } elseif ($live['status'] === 'success' && $delete['status'] === 'success') {
            Notification::make()
                ->success()
                ->title('Website updated successfully')
                ->send();
            return redirect('/websites' . '/' . $this->project->project_id);
        } else {
            Notification::make()
                ->danger()
                ->title('Something Went Wrong')
                ->body("Please try again later.")
                ->send();
            return redirect('/websites' . '/' . $this->project->project_id);
        }
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
    $domainExists = Project::where('domain', $domianCheck)
                           ->where('project_id', '!=', $this->project->project_id) // Exclude current project
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


    public function previewGenerate() {
        $this->isPreview = true;
        $this->shouldRedirect = false;
        $this->deletePreview();
        $live = $this->previewWebsite();

        if ($live['status'] === 'danger') {
                $title = $live['title'];  // Set title for live
                $body = $live['body'];    // Set body for live
                // Send notification
                Notification::make()
                    ->danger()
                    ->title($title)
                    ->body($body)
                    ->send();

                return redirect('/websites' . '/' . $this->project->project_id);
            }
            elseif ($live['status'] === 'success') {
                
                $this->dispatch('redirectToPreview');
                return;
            } else {
                Notification::make()
                    ->danger()
                    ->title('Something Went Wrong')
                    ->body("Please try again later.")
                    ->send();
                return redirect('/websites' . '/' . $this->project->project_id);
            }


        $this->isPreview = false;
        $this->shouldRedirect = true;


    }

}
?>
<x-layouts.app>
    @volt('websites.edit')
    <x-app.container>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>

/* Style the scrollbar itself */
::-webkit-scrollbar {
    width: 8px; /* Width of the scrollbar */
}

/* Style the track (background) of the scrollbar */
::-webkit-scrollbar-track {
    background: #f1f1f1; /* Light grey background */
    border-radius: 10px; /* Rounded corners for the track */
}

/* Style the thumb (draggable part) of the scrollbar */
::-webkit-scrollbar-thumb {
    background: #888; /* Dark grey color for the thumb */
    border-radius: 10px; /* Rounded corners for the thumb */
}

/* Hover effect for the thumb */
::-webkit-scrollbar-thumb:hover {
    background: #555; /* Darker grey when hovering over the thumb */
}

/* Optional: Style for dark mode */
.dark-mode::-webkit-scrollbar-track {
    background: #333; /* Darker background for dark mode */
}

.dark-mode::-webkit-scrollbar-thumb {
    background: #aaa; /* Lighter thumb for dark mode */
}

.dark-mode::-webkit-scrollbar-thumb:hover {
    background: #888; /* Darker thumb hover in dark mode */
}

    </style>
        <div class="container mx-auto my-6">
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to Websites" :href="route('websites')" />
            <!-- Box with background, padding, and shadow -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
            <div class="space-y-6">
                <div class="flex items-center justify-between mb-5">
                        <!-- Favicon or Icon with the project name -->
                    <div class="flex items-center">
                        @if($this->project->favicon)
                        <img src="{{ asset('storage/usersites/' . $this->project->project_id . '/logo/' . $this->project->favicon) }}" 
                            alt="Website Favicon" class="w-6 h-6 rounded-full mr-2">
                        @endif
                        <x-app.heading title="{{ $this->project->project_name }}"
                            description="{{ $this->project->description ?? 'No description available' }}" :border="false" class="mr-8" />
                    </div>
                </div>




                
                <!-- Domain or URL -->
                <div class="space-y-2">
                    <h3 class="text-lg font-medium">Live Website</h3>
                    @if($this->project->domain)
                    <a href="{{ 'https://' . $this->project->domain . $this->ourDomain}}"
                    class="text-blue-600 hover:underline"
                    target="_blank">{{ 'https://' . $this->project->domain . $this->ourDomain}}</a>
                    @else
                    <p class="text-gray-500">No domain assigned</p>
                    @endif
                </div>
             
                
                <!-- Live Status Indicator -->
                <div class="flex items-center">
                    <span class="text-sm font-medium mr-2">Status:</span>
                    @if($this->project->live)
                    <span class="text-green-500">Live</span>
                    @else
                    <span class="text-red-500">Not Live</span>
                    @endif
                </div>
            </div>
<div class="flex justify-end gap-x-3">
    <x-button tag="a" :href="route('builder', ['project_id' => $this->project->project_id, 'project_name' => $this->project->project_name])" target="_blank">Open In Builder</x-button>    
                            <x-button type="button" wire:click="previewGenerate" color="gray">Preview Website</x-button>


</div>

            

    <div x-data="{ activeTab: 'pages' }">
        <!-- Tabs Navigation -->
        <div class="mb-6 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
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
                        @click.prevent="activeTab = 'website-settings'" 
                        :class="{ 'text-blue-600 border-blue-500': activeTab === 'website-settings' }" 
                        class="tab-btn inline-block p-4 rounded-t-lg border-b-2 cursor-pointer select-none"
                    >
                        Website Settings
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



              <!-- Website Settings Box -->
                <div 
    x-show="activeTab === 'website-settings'" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    x-cloak 
    id="website-settings" 
    class="space-y-6 tab-panel">

                    <div class="flex items-center justify-between mb-5">
                        <!-- Display the current project name as a heading -->
                        <x-app.heading title="Website Settings" description="Customize your website's configuration." :border="false" />
                    </div>
                    <form wire:submit="edit" class="space-y-6">

                        <!-- Render the form fields here -->
                        {{ $this->form }}

                        <div class="flex justify-end gap-x-3">
                            <!-- Cancel Button -->
                            <x-button tag="a" href="/websites" color="secondary">Cancel</x-button>

                            <!-- Save Changes Button -->
                            <x-button type="button" wire:click="edit"
                                class="text-white bg-primary-600 hover:bg-primary-500">Save
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

                                @if(Gate::allows('create-template'))
                                <!-- Save as Public Template -->
                                <a href="#" wire:click="saveAsPublicTemplate"
                                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                                    <x-icon name="phosphor-star" class="w-4 h-4 mr-2" /> Save as Public Template
                                </a>
                                @endif

                                <!-- Delete Website -->
                                <a href="#" wire:click="delete"
                                    class="block px-4 py-2 text-red-600 hover:bg-gray-100 flex items-center" wire:confirm="Are you sure you want to delete this website?">
                                    <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Website
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
                        <x-app.heading title="Pages" description="Configure your website's pages." :border="false" />
                    </div>
                    <div class="space-y-4 mt-4">
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
                                                                    @if (!$page->main)
                                                                    <!-- mainpage Button -->
                                                                    <x-button type="button" wire:click="pageMain({{ $page->id }})"
                                                                       color="gray">Set as Main
                                                                        Page</x-button>
                                                                    @endif
                                                            </div>


                                                </div>
                                            </div>
                        @endforeach

                    </div>
                </div>
                <!-- Header/Footer Box -->
                <div 
    x-show="activeTab === 'header-footer'" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    x-cloak 
    id="header-footer" 
    class="space-y-6 tab-panel">
                
                    <div class="flex items-center justify-between mb-5">
                        <!-- Display the current project name as a heading -->
                        <x-app.heading title="Header & Footer" description="Edit your website's Header and Footer." :border="false" />
                    </div>
                    
                    <!-- Collapsible Section for Header -->
                    <div x-data="{ open: false }" class="mb-5">
                        <div class="flex justify-between items-center cursor-pointer" @click="open = ! open">
                            <h3 class="text-md font-semibold">Header</h3>
                            <svg x-bind:class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform duration-300"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div x-show="open" x-transition.duration.300ms x-cloak class="bg-white-100 p-4 rounded-md shadow-md mt-3">                    
                            <!-- Buttons (only shown when section is open) -->
                            <div class="flex justify-end gap-x-3 mt-3">
                                <x-button tag="a" :href="route('header', ['project_id' => $this->project->project_id])" target="_blank">Edit
                                    Header</x-button>
                                <x-button color="danger" type="button" wire:click="resetHeaderToDefault" wire:confirm="Are you sure you want to reset header to default?">Reset Header</x-button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Collapsible Section for Footer -->
                    <div x-data="{ open: false }">
                        <div class="flex justify-between items-center cursor-pointer" @click="open = ! open">
                            <h3 class="text-md font-semibold">Footer</h3>
                            <svg x-bind:class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform duration-300"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div x-show="open" x-transition.duration.300ms x-cloak class="bg-white-100 p-4 mt-3 rounded-md shadow-md">                    
                            <!-- Buttons (only shown when section is open) -->
                            <div class="flex justify-end gap-x-3 mt-3">
                                <x-button tag="a" :href="route('footer', ['project_id' => $this->project->project_id])" target="_blank">Edit
                                    Footer</x-button>
                                <x-button color="danger" type="button" wire:click="resetFooterToDefault" wire:confirm="Are you sure you want to reset footer to default?">Reset Footer</x-button>
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
                        <x-app.heading title="Live Settings" description="Set up your domain and publish your website online." :border="false" />
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
                                    <label for="{{$page->id}}" class="text-sm">
                                    <input type="checkbox" wire:model="liveData.pages" value="{{ $page->id }}" class="fi-checkbox-input rounded border-none bg-white shadow-sm ring-1 transition duration-75 checked:ring-0 focus:ring-2 focus:ring-offset-0 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-current disabled:checked:text-gray-400 dark:bg-white/5 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600 text-primary-600 ring-gray-950/10 focus:ring-primary-600 checked:focus:ring-primary-500/50 dark:text-primary-500 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500 dark:checked:focus:ring-primary-400/50 dark:disabled:ring-white/10"
                                    wire:loading.attr="disabled"
                                    id="{{$page->id}}"
                                    @if($page->main) checked disabled @endif>
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
                            id="header"
                            wire:loading.attr="disabled" 
                            type="checkbox" wire:model="liveData.header" class="fi-checkbox-input rounded border-none bg-white shadow-sm ring-1 transition duration-75 checked:ring-0 focus:ring-2 focus:ring-offset-0 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-current disabled:checked:text-gray-400 dark:bg-white/5 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600 text-primary-600 ring-gray-950/10 focus:ring-primary-600 checked:focus:ring-primary-500/50 dark:text-primary-500 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500 dark:checked:focus:ring-primary-400/50 dark:disabled:ring-white/10">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Include Header</span>
                            </label>
                            
                            @error('liveData.header')
                                <div class="text-red-600 text-sm">{{ $message }}</div>
                            @enderror
                            
                        </div>
                    

                        <div class="flex items-center mb-2">
                        <!-- Footer Option -->
                            <label for="footer" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                            <input
                            id="footer"
                            wire:loading.attr="disabled"
                            type="checkbox" wire:model="liveData.footer" 
                            class="fi-checkbox-input rounded border-none bg-white shadow-sm ring-1 transition duration-75 checked:ring-0 focus:ring-2 focus:ring-offset-0 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-current disabled:checked:text-gray-400 dark:bg-white/5 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600 text-primary-600 ring-gray-950/10 focus:ring-primary-600 checked:focus:ring-primary-500/50 dark:text-primary-500 dark:ring-white/20 dark:checked:bg-primary-500 dark:focus:ring-primary-500 dark:checked:focus:ring-primary-400/50 dark:disabled:ring-white/10">
                                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Include Footer</span></label>
                            @error('liveData.footer')
                                <div class="text-red-600 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                        </div>
                    </div>
                
                    <div class="flex justify-end gap-x-3 mt-3">
                    
                    @if ($this->project->live)
                        <!-- Update Button -->
                        <x-button wire:click="updateLiveWebsite" type="submit" color="primary">
                            Update Site
                        </x-button>
                            <x-button color="danger" type="button" wire:click="deleteLiveWebsite"  wire:confirm="Are you sure you want to take down this website?">Take Down</x-button>
                    @else
                        <!-- Cancel Button -->
                    <x-button tag="a" href="/websites" color="secondary">Cancel</x-button>
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


<a href="/preview/{{$this->project->project_id}}/index.html" target="_blank" id="redirect-button" style="display: none;"></a>




@script
<script>

    $wire.on('redirectToPreview', () => {
        var btn = document.getElementById('redirect-button');
        btn.click();
    });





 document.addEventListener('DOMContentLoaded', function() {
        // Generate the screenshot
        html2canvas(document.getElementById('headerImg')).then(function(canvas) {
            // Convert the canvas to a data URL (image)
            var img = canvas.toDataURL("image/png");
            
            // Display the image in the img tag
            var screenshotImg = document.getElementById('screenshot');
            screenshotImg.src = img;
            screenshotImg.style.display = 'block';
        });
    });





</script>
@endscript


    </x-app.container>
    @endvolt
</x-layouts.app>