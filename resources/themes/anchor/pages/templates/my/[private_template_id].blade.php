<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\WebPage; // Assuming you have the WebPage model
use App\Models\HeaderFooter;
use App\Models\PrivateTemplate;
use App\Models\PrivateTemplateHf;
use App\Models\PrivateTempPage;
use App\Models\Project; // Import Project model for creating a website
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('templates.edit'); // Name the route

new class extends Component implements HasForms {
    use InteractsWithForms;

    public $private_template_id; // The private_template_id from the URL
    public PrivateTemplate $template;  // Holds the template instance
    public ?array $data = []; // Holds form data
    public $header;
    public $footer;
    public $pages = [];
    public $mainPage;

    // Mount method to set the template_id from the URL and fetch the template
    public function mount($private_template_id): void
    {
        $this->private_template_id = $private_template_id; // Set the private_template_id dynamically from the URL

        // Retrieve the template using the private_template_id and authenticate it
        $this->template = auth()->user()->privateTemplates()->where('id', $this->private_template_id)->firstOrFail();



        $this->header = PrivateTemplateHf::where("private_template_id", $this->template->id)
            ->where("is_header", true)
            ->first();
        $this->footer = PrivateTemplateHf::where("private_template_id", $this->template->id)
            ->where("is_header", false)
            ->first();


        $this->pages = PrivateTempPage::where('private_template_id', $this->template->id)->get();


        $this->mainPage = PrivateTempPage::where('private_template_id', $this->template->id)
            ->where('main', true)
            ->first();

        if (!$this->mainPage && $this->pages->isNotEmpty()) {
            $this->mainPage = $this->pages->first();
            $this->mainPage->main = true; // Assuming `main` is the column to mark the main page
            $this->mainPage->save(); // Save the updated main page
        }

        // Pre-fill the form with existing template data
        $this->form->fill([
            'name' => $this->template->template_name,
            'description' => $this->template->description,
        ]);

    }

    // Define the form schema
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Website Name')
                    ->placeholder('Enter Website Name')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Enter Website Description')
                    ->rows(5)
                    ->maxLength(1000), // Limit description length
            ])
            ->statePath('data');
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



    public function create(): void
    {


        $user = auth()->user();
        $response = $user->canCreateWebsite($user);

        if ($response['status'] === 'danger') {

            Notification::make()
                ->danger()
                ->title($response['title'])
                ->body($response['body'])
                ->send();

            $this->redirect('/websites');
            return;
        }

        if (!$this->template) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Template data is missing.')
                ->send();

            return;
        }

        $robotsTxt = $this->template->robots_txt;
        $headerEmbedGlobal = $this->template->header_embed;
        $footerEmbedGlobal = $this->template->footer_embed;
        $favIcon = $this->template->favicon;

        $headerHtml = $this->header->html;
        $footerHtml = $this->footer->html;
        $headerCss = $this->header->css;
        $footerCss = $this->footer->css;


        // Check if template JSON is missing or invalid
        $templateJson = $this->template->template_json;
        if ($templateJson === null) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Template JSON data is missing or invalid.')
                ->send();
            return;
        }

        // Check if header JSON is missing or invalid
        $headerJson = $this->header->json;
        if ($headerJson === null) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Header JSON data is missing or invalid.')
                ->send();
            return;
        }

        // Check if footer JSON is missing or invalid
        $footerJson = $this->footer->json;
        if ($footerJson === null) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Footer JSON data is missing or invalid.')
                ->send();
            return;
        }

        // Check if the name is missing
        if (empty($this->data['name'])) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Website name is required.')
                ->send();
            return;
        }

        // Check if pages data is missing or invalid
        if (empty($this->pages)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Pages data is missing.')
                ->send();
            return;
        }

        // Validate Header HTML
        if (empty($this->header->html) || !is_string($this->header->html)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body("Header HTML data is missing or invalid.")
                ->send();
            return;
        }

        // Validate Footer HTML
        if (empty($this->footer->html) || !is_string($this->footer->html)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body("Footer HTML data is missing or invalid.")
                ->send();
            return;
        }

        // Validate Header CSS
        if (empty($this->header->css) || !is_string($this->header->css)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body("Header CSS data is missing or invalid.")
                ->send();
            return;
        }

        // Validate Footer CSS
        if (empty($this->footer->css) || !is_string($this->footer->css)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body("Footer CSS data is missing or invalid.")
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


        $project = auth()->user()->projects()->create([
            'project_name' => $this->data['name'],
            'description' => $this->data['description'],
            'project_json' => $templateJson, // Use the template JSON for the new website
            'header_embed' => $headerEmbedGlobal,
            'footer_embed' => $footerEmbedGlobal,
            'robots_txt' => $robotsTxt,
            'favicon' => $favIcon,
        ]);


        // Duplicate associated files
        $sourceFolder = "/var/www/ezysite/public/storage/private-templates/{$this->template->id}";
        $targetFolder = "/var/www/ezysite/public/storage/usersites/{$project->project_id}";

        if (file_exists($sourceFolder)) {
            // Create the target folder if it doesn't exist
            if (!file_exists($targetFolder)) {
                mkdir($targetFolder, 0777, true);
            }

            // Recursive function to copy files and directories
            $this->copyDirectory($sourceFolder, $targetFolder);
        }


        // Create header and footer entries and link them to the project
        $header = HeaderFooter::create([
            'website_id' => $project->project_id,
            'json' => $headerJson,
            'html' => $headerHtml,
            'css' => $headerCss,
            'is_header' => true,
        ]);

        // Create header and footer entries and link them to the project
        $footer = HeaderFooter::create([
            'website_id' => $project->project_id,
            'json' => $footerJson,
            'html' => $footerHtml,
            'css' => $footerCss,
            'is_header' => false,
        ]);


        foreach ($this->pages as $page) {

            if (empty($page['name'])) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body("Template's Page's data is missing or invalid.")
                    ->send();
                return;
            }
            $pageCreated = WebPage::create([
                'page_id' => $page['page_id'],
                'name' => $page['name'],
                'slug' => $page['slug'],
                'title' => $page['title'],
                'meta_description' => $page['meta_description'],
                'main' => $page['main'],
                "og_title" => $page["og_title"],
                "og_url" => $page["og_url"],
                "og_description" => $page["og_description"],
                "og_img" => $page["og_img"],
                'embed_code_start' => $page['embed_code_start'],
                'embed_code_end' => $page['embed_code_end'],
                'html' => $page['html'],
                'css' => $page['css'],
                'website_id' => $project->project_id, // Associate the page with the project
            ]);
        }

        Notification::make()
            ->success()
            ->title('Website created successfully using template')
            ->send();

        $this->redirect('/websites');
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
            $files = array_diff(scandir($source), [".", ".."]);

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

    // Delete the template
    public function delete(): void
    {

        // Define the target folder path
        $targetFolder = "/var/www/ezysite/public/storage/private-templates/{$this->template->id}";

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
                    <x-app.heading title="Creating from: {{ $this->template->template_name }}"
                        description="{{$this->template->description}}" :border="false" />
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
                        <x-button type="button" wire:click="create"
                            class="text-white bg-primary-600 hover:bg-primary-500">
                            Create Website
                        </x-button>
                        <x-button type="button" wire:click="delete" color="danger"
                            wire:confirm="Are you sure you want to delete this template?">
                            Delete Template
                        </x-button>
                    </div>

                </form>
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>