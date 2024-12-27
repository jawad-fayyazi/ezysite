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
    public $activeTab = 'overview'; // Active tab (default: overview)
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
            $this->headerCreate();
        }
        if (!$this->footer) {
            $this->footerCreate();
        }
        $this->liveData = [
            'domain' => $this->project->domain,
            'pages' => [],    // Default empty array for selected pages
            'header' => true, // Default value for header
            'footer' => true, // Default value for footer
        ];

        // Retrieve pages for the project
        $this->pages = WebPage::where('website_id', $this->project_id)->get();

        // Pre-fill the form with existing page data
        foreach ($this->pages as $page) {
            $this->pageData[$page->id] = [
                'page_name' => $page->name,
                'page_title' => $page->title,
                'meta_description' => $page->meta_description,
                'og_tags' => $page->og_tags,
                'header_embed_code' => $page->header_embed_code,
                'footer_embed_code' => $page->footer_embed_code,
                'page_slug' => $page->slug,
            ];
        }
        ;

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


    public function pagesSetForm()
    {
        $this->activeTab = "pages";
    }


    public function websiteSettingsSetForm()
    {
        $this->activeTab = "website_settings";
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

        if ($this->activeTab === 'pages') {

            return $form
                ->schema([
                    TextInput::make('page_name')
                        ->label('Page Name')
                        ->readonly()
                        ->maxLength(255),
                    TextInput::make('page_title')
                        ->label('Page Title')
                        ->placeholder('Enter page title')
                        ->maxLength(255),
                    Textarea::make('page_meta_description')
                        ->label('Meta Description')
                        ->placeholder('Enter meta description')
                        ->rows(3)
                        ->maxLength(500),
                    Textarea::make('page_og_tags')
                        ->label('Open Graph Tags')
                        ->placeholder('Enter OG tags')
                        ->rows(3),
                    Textarea::make('page_header_embed_code')
                        ->label('Header Embed Code')
                        ->placeholder('Paste header embed code here')
                        ->rows(3),
                    Textarea::make('page_footer_embed_code')
                        ->label('Footer Embed Code')
                        ->placeholder('Paste footer embed code here')
                        ->rows(3),
                ])
                ->statePath('pageData');
        }
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
            $slug = $this->convertToSlug($data["page_slug"] ?? $data["page_name"]);

            // Ensure the slug is not empty after conversion
            if (empty($slug)) {
                $slug = $this->convertToSlug($data["page_name"]);
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



    public function liveWebsite()
    {
        // Access liveData array
        $domain = $this->liveData['domain'];
        $pages = $this->liveData['pages']; // Selected pages array
        $header = $this->liveData['header'];
        $footer = $this->liveData['footer'];

        // Check if domain is empty
        if (empty($domain)) {
            // Generate a default domain using the project's name or a unique identifier
            $defaultDomain = Str::slug($this->project->project_name) . '-' . uniqid();
            $domain = $defaultDomain;
        } else {
            // Append subdomain suffix if not already present
            $domain = Str::slug($domain);
        }

        // Update project domain
        $this->project->update([
            'domain' => $domain,
        ]);


        // Check if at least one page is selected
        if (empty($pages)) {
            if($this->shouldRedirect){
                Notification::make()
                    ->danger()
                    ->title('Error: No Pages Selected')
                    ->body('You must select at least one page to make the website live.')
                    ->send();

                return redirect('/websites' . '/' . $this->project->project_id);
            }
            else{
                return [
                    'status' => 'danger',
                    'title' => 'No Pages Selected',
                    "body" => 'You must select at least one page to make the website live.',
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
                        ->body("Failed to create directory for the domain: {$domain}")
                        ->send();

                    return redirect('/websites' . '/' . $this->project->project_id);
                } else {
                    return [
                        'status' => 'danger',
                        'title' => 'Domain Configuration Error',
                        "body" => "Failed to create directory for the domain: {$domain}",
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
        $sourcePath = "/var/www/ezysite/public/storage/usersites/{$this->project->project_id}/logo/{$this->project->favicon}";
        if(File::exists($sourcePath)){
            
            $destinationPath = $targetFolder . "/img/{$this->project->favicon}";
            $result = $this->copyImage($sourcePath, $destinationPath);
            if ($result === 'danger'){
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
            $ogTags = $page->og_tags ?? '';
            $headerEmbed = $page->header_embed_code ?? '';
            $footerEmbed = $page->footer_embed_code ?? '';




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
                'header_embed' => $headerEmbed,
                'footer_embed' => $footerEmbed,
                'header_html' => $headerHtml,
                'header_css' => $headerCss,
                'footer_html' => $footerHtml,
                'footer_css' => $footerCss,
                'page_html' => $updatedPageHtml,
                'page_css' => $pageCss,
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
        }
        else{
            return [
                'status' => 'success',
            ];
        }
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
                if(!File::makeDirectory($destinationFolder, 0755, true)){
                    return 'danger';
                }  // Creates the directory with the right permissions
            }

            // Copy the file to the new destination
            if (File::copy($sourcePath, $destinationPath)){
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
            }
            else{
            return [
                'status' => 'success',
            ];
        }

        } else {
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



        if ($delete['status'] === 'success'){

            $live = $this->liveWebsite();

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
        }
         elseif($live['status'] === 'success' && $delete['status'] === 'success'){
            Notification::make()
                ->success()
                ->title('Website updated successfully')
                ->send();
            return redirect('/websites' . '/' . $this->project->project_id);
        }
        else {
            Notification::make()
                ->danger()
                ->title('Something Went Wrong')
                ->body("Please try again later.")
                ->send();
            return redirect('/websites' . '/' . $this->project->project_id);
        }
    }


    public function domainCheck()
{
    if (!$this->liveData['domain']) {
        $this->message = 'Please enter a subdomain.';
        return;
    }

    if (Subdomain::where('subdomain', $this->liveData['domain'])->exists()) {
        $this->message = 'Subdomain is already taken.';
    } else {
        $this->message = 'Subdomain is available.';
    }
}

}
?>

<x-layouts.app>
    @volt('websites.edit')
    <x-app.container>
        <div class="container mx-auto my-6">
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to Websites" :href="route('websites')" />
            <!-- Box with background, padding, and shadow -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center justify-between mb-5">
                    <!-- Display the current project name as a heading -->
                    <x-app.heading title="Website: {{ $this->project->project_name }}"
                        description="Manage your website's details and settings." :border="false" />
                    <x-button tag="a" :href="route('builder', ['project_id' => $this->project->project_id, 'project_name' => $this->project->project_name])" target="_blank">Open In Builder</x-button>
                </div>
                
                <!-- Tabs Navigation -->
                <div class="mb-6 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                        <li class="mr-2">
                            <a href="#" class="tab-btn inline-block p-4 rounded-t-lg" data-tab="overview">Overview</a>
                        </li>
                        <li class="mr-2">
                            <a href="#" class="tab-btn inline-block p-4 rounded-t-lg" data-tab="pages">Pages</a>
                        </li>
                        <li class="mr-2">
                            <a href="#" class="tab-btn inline-block p-4 rounded-t-lg"
                                data-tab="header-footer">Header & Footer</a>
                        </li>
                        <li class="mr-2">
                            <a href="#" class="tab-btn inline-block p-4 rounded-t-lg"
                                data-tab="website-settings">Website Settings</a>
                        </li>
                        <li class="mr-2">
                            <a href="#" class="tab-btn inline-block p-4 rounded-t-lg" data-tab="live-settings">Live
                                Settings</a>
                        </li>
                    </ul>
                </div>
                <!-- Overview Tab Content -->

                <div id="overview" class="hidden space-y-6 tab-panel">
                    <!-- Website Name -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold">{{ $this->project->project_name }}</h2>
                        <div class="text-sm text-gray-500">Project Name</div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <h3 class="text-lg font-medium">Description</h3>
                        <p class="text-gray-700">{{ $this->project->description ?? 'No description available' }}</p>
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



                <!-- Website Settings Box -->
                <div id="website-settings" class="hidden tab-panel">
                    <form wire:submit="edit" class="space-y-6">
                        <h2 class="text-lg font-semibold grid gap-y-2">Website Settings</h2>

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

                                <!-- Delete Website -->
                                <a href="#" wire:click="delete"
                                    class="block px-4 py-2 text-red-600 hover:bg-gray-100 flex items-center">
                                    <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Website
                                </a>
                            </x-dropdown>
                        </div>
                    </form>
                </div>

                <!-- Pages Box -->
                <div id="pages" class="hidden tab-panel">
                    <div class="flex items-center justify-between mb-5">
                        <!-- Display the current project name as a heading -->
                        <h2 class="text-2xl font-semibold">Pages</h2>
                    </div>
                    <div class="space-y-4 mt-4">
                        @foreach($this->pages as $page)
                                            <div x-data="{ open: false }" class="mb-5">
                                                <!-- Page Header with Toggle -->
                            <div class="flex justify-between items-center">
                                <div @click="open = !open" class="cursor-pointer text-md font-semibold w-full flex justify-between">
                                    <span>{{ $page->name }}</span>
                                    @if($page->main)
                                    <span class="ml-2 text-xs font-medium text-white bg-green-500 px-2 py-1 rounded-full">
                                        Main Page
                                    </span>
                                    @endif
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

                                                                <!-- mainpage Button -->
                                                                <x-button type="button" wire:click="pageMain({{ $page->id }})"
                                                                    class="text-white bg-primary-600 hover:bg-primary-500">Set as Main
                                                                    Page</x-button>

                                                                <!-- Save Changes Button -->
                                                                <x-button type="button" wire:click="pageUpdate({{ $page->id }})"
                                                                    class="text-white bg-primary-600 hover:bg-primary-500">Save
                                                                    Changes</x-button>
                                                            </div>


                                                </div>
                                            </div>
                        @endforeach

                    </div>
                </div>

                <!-- Header/Footer Box -->
                <div id="header-footer" class="hidden space-y-6 tab-panel">
                
                    <div class="flex items-center justify-between mb-5">
                        <!-- Display the current project name as a heading -->
                        <x-app.heading title="Header & Footer" description="Edit your website's Header and Footer." :border="false" />
                    </div>
                    
                    <!-- Collapsible Section for Header -->
                    <div x-data="{ open: false }" class="mb-5">
                        <div class="flex justify-between items-center cursor-pointer" @click="open = ! open">
                            <h3 class="text-md font-semibold">Header Preview</h3>
                            <svg x-bind:class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform duration-300"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div x-show="open" x-transition.duration.300ms x-cloak class="bg-white-100 p-4 rounded-md shadow-md mt-3">
                            <!-- Header Preview Content -->
                            <div class="header-preview" style="border: 1px solid #ccc; pointer-events: none; user-select: none;">
                                <style>
                                    {!! $this->header->css !!}
                                </style>
                                {!! $this->header->html !!}
                            </div>
                    
                            <!-- Buttons (only shown when section is open) -->
                            <div class="flex justify-end gap-x-3 mt-3">
                                <x-button tag="a" :href="route('header', ['project_id' => $this->project->project_id])" target="_blank">Edit
                                    Header</x-button>
                                <x-button color="danger" type="button" wire:click="resetHeaderToDefault">Reset Header to Default</x-button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Collapsible Section for Footer -->
                    <div x-data="{ open: false }">
                        <div class="flex justify-between items-center cursor-pointer" @click="open = ! open">
                            <h3 class="text-md font-semibold">Footer Preview</h3>
                            <svg x-bind:class="open ? 'transform rotate-180' : ''" class="w-5 h-5 transition-transform duration-300"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div x-show="open" x-transition.duration.300ms x-cloak class="bg-white-100 p-4 mt-3 rounded-md shadow-md">
                            <!-- Footer Preview Content -->
                            <div class="footer-preview" style="border: 1px solid #ccc; pointer-events: none; user-select: none;">
                                <style>
                                    {!! $this->footer->css !!}
                                </style>
                                {!! $this->footer->html !!}
                            </div>
                    
                            <!-- Buttons (only shown when section is open) -->
                            <div class="flex justify-end gap-x-3 mt-3">
                                <x-button tag="a" :href="route('footer', ['project_id' => $this->project->project_id])" target="_blank">Edit
                                    Footer</x-button>
                                <x-button color="danger" type="button" wire:click="resetFooterToDefault">Reset Footer to Default</x-button>
                            </div>
                        </div>
                    </div>


                </div>



                <!-- Live Settings Box -->
                <div id="live-settings" class="hidden space-y-6 tab-panel">
                    <h1 class="text-2xl font-bold mb-4">Make Your Website Live</h1>
                
                    <!-- Subdomain Input -->
                    <div x-data="subdomainChecker()" class="grid gap-y-2">
                        <label for="subdomain" class="block">
                            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">Subdomain</span>
                            </label>
                            <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-2 ring-gray-950/10 dark:ring-white/20 [&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-600 dark:[&:not(:has(.fi-ac-action:focus))]:focus-within:ring-primary-500 fi-fo-text-input overflow-hidden">
                                <input type="text" id="subdomain" name="subdomain" wire:model="liveData.domain" x-model="subdomain"  @input.debounce.500ms="checkSubdomain"
                                class="form-control fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3"
                                placeholder="Your Subdomain" value="{{ old('subdomain') }}" required>
                        </div>
                        <p x-text="message" class="text-sm font-medium mt-2"></p>
                    </div>
                
                    <!-- Pages Selection -->
                    <div class="grid gap-y-2">
                        <label class="block text-sm font-semibold">Select Pages</label>
                        @foreach($pages as $page)
                            <div class="flex items-center mb-2">
                                <input type="checkbox" wire:model="liveData.pages" value="{{ $page->id }}" class="mr-2">
                                <label for="pages[]" class="text-sm">{{ $page->name }}</label>
                            </div>
                        @endforeach
                        @error('liveData.pages')
                            <div class="text-red-600 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                
                    <!-- Header Option -->
                    <div class="grid gap-y-2">
                        <label class="block text-sm font-semibold">Include Header</label>
                        <input type="checkbox" wire:model="liveData.header" class="mr-2">
                        @error('liveData.header')
                            <div class="text-red-600 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                
                    <!-- Footer Option -->
                    <div class="grid gap-y-2">
                        <label class="block text-sm font-semibold">Include Footer</label>
                        <input type="checkbox" wire:model="liveData.footer" class="mr-2">
                        @error('liveData.footer')
                            <div class="text-red-600 text-sm">{{ $message }}</div>
                        @enderror
                    </div>
                
                    <div class="flex justify-end gap-x-3 mt-3">
                    
                    @if ($this->project->live)
                        <!-- Update Button -->
                        <x-button wire:click="updateLiveWebsite" type="submit" color="primary">
                            Update Site
                        </x-button>
                            <x-button color="danger" type="button" wire:click="deleteLiveWebsite">Take Down</x-button>
                    @else
                        <!-- Make it Live Button -->
                        <x-button wire:click="liveWebsite" type="submit" color="primary">
                            Go Live
                        </x-button>
                    @endif
                    </div>
                </div>



            </div>
        </div>

        @script
        <script>
            const tabs = document.querySelectorAll(".tab-btn");
            const panels = document.querySelectorAll(".tab-panel");

            tabs.forEach(tab => {
                tab.addEventListener("click", () => {
                    // Hide all panels
                    panels.forEach(panel => {
                        panel.classList.add("hidden");
                        console.log("hiding this", panel.id);
                    });
                    console.log("hide complete");

                    // Remove the custom border classes from all tabs
                    tabs.forEach(tab => {
                        tab.classList.remove("border-b-2", "border-blue-500");
                    });


                    const target = document.getElementById(tab.dataset.tab);
                    target.classList.remove("hidden");
                    console.log("showing complete");

                    // Add active class to the clicked tab
                    tab.classList.add("border-b-2", "border-blue-500");
                });

                // Optional: Show the first tab by default
                tabs[0].click();
            })

    function subdomainChecker() {
        return {
            subdomain: '', // User input
            message: '',   // Feedback message
            async checkSubdomain() {
                if (this.subdomain.trim() === '') {
                    this.message = 'Please enter a subdomain.';
                    return;
                }

                try {
                    // Make an API call to check the subdomain
                    let response = await fetch(`/api/check-subdomain?subdomain=${this.subdomain}`);
                    let data = await response.json();

                    if (data.available) {
                        this.message = 'Subdomain is available.';
                    } else {
                        this.message = 'Subdomain is already taken.';
                    }
                } catch (error) {
                    console.error(error);
                    this.message = 'Error checking subdomain. Please try again.';
                }
            }
        };
    }
</script>
@endscript



    </x-app.container>
    @endvolt
</x-layouts.app>