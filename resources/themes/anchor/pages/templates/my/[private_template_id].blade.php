<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
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
            'template_name' => $this->template->template_name,
            'description' => $this->template->description,
        ]);

    }

    // Define the form schema
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('template_name')
                    ->label('Template Name')
                    ->placeholder('Enter Template Name')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Enter Template Description')
                    ->rows(5)
                    ->maxLength(1000), // Limit description length
            ])
            ->statePath('data');
    }

    // Save template changes
    public function save(): void
    {
        $data = $this->form->getState();

        // Update template name if provided
        if (!empty($data['template_name'])) {
            $this->template->update(['template_name' => $data['template_name']]);
        }

        // Update description if provided
        if (!empty($data['description'])) {
            $this->template->update(['description' => $data['description']]);
        }

        Notification::make()
            ->success()
            ->title('Template updated successfully')
            ->send();

        $this->redirect('/templates/my'); // Redirect to the user's private templates page
    }


    // Duplicate the template
    public function duplicate(): void
    {
        // Replicate the template
        $newTemplate = $this->template->replicate();
        $newTemplate->template_name = $this->template->template_name . ' (Copy)';
        $newTemplate->save();

        Notification::make()
            ->success()
            ->title('Template duplicated successfully')
            ->send();

        $this->redirect('/templates/my'); // Redirect to templates page after duplication
    }

    // Create a website from this template
    public function createWebsiteFromTemplate(): void
    {
        // Create a new project using the template
        $project = new Project();
        $project->project_name = $this->template->template_name . ' (Created from My Templates)'; // Use template name as project name
        $project->description = $this->template->description; // Use template description
        $project->project_json = $this->template->template_json; // Use template data for the project
        $project->user_id = auth()->id(); // Associate with the logged-in user
        $project->save();

        Notification::make()
            ->success()
            ->title('Website created successfully from template')
            ->send();

        $this->redirect("/websites"); // Redirect to the newly created website
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
        if (empty($this->data['template_name'])) {
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
            'project_name' => $this->data['template_name'],
            'description' => $this->data['description'],
            'project_json' => $templateJson, // Use the template JSON for the new website
            'header_embed' => $headerEmbedGlobal,
            'footer_embed' => $footerEmbedGlobal,
            'robots_txt' => $robotsTxt,
            'favicon' => $favIcon,
        ]);


        if ($this->template->favicon) {
            $sourcePath = "/var/www/ezysite/public/storage/private-templates/{$this->template->id}/logo/{$this->template->favicon}";
            if (File::exists($sourcePath)) {

                $destinationPath = "/var/www/ezysite/public/storage/usersites/{$project->project_id}/logo/{$project->favicon}";
                $result = $this->copyImage($sourcePath, $destinationPath);
                if ($result === 'danger') {
                    Notification::make()
                        ->danger()
                        ->title('Favicon not found')
                        ->send();
                }
            }
        }




        // Create header and footer entries and link them to the project
        $header = PrivateTemplateHf::create([
            'website_id' => $project->project_id,
            'json' => $headerJson,
            'html' => $headerHtml,
            'css' => $headerCss,
            'is_header' => true,
        ]);

        // Create header and footer entries and link them to the project
        $footer = PrivateTemplateHf::create([
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
            $pageCreated = PrivateTempPage::create([
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
                'website_id' => $project->project_id, // Associate the page with the project
            ]);
        }

        Notification::make()
            ->success()
            ->title('Website created successfully using template')
            ->send();

        $this->redirect('/websites');
    }



    // Delete the template
    public function delete(): void
    {
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
            <x-app.heading title="Editing: {{ $this->template->template_name }}"
                description="Update the template name or description below, or delete the template." :border="false" />
                <x-button tag="button" wire:click="createWebsiteFromTemplate" color="primary">Create Website from Template</x-button>
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
    <x-button type="button" wire:click="create" class="text-white bg-primary-600 hover:bg-primary-500">
        Create Website
    </x-button>

    <!-- Dropdown for additional actions -->
    <x-dropdown class="text-gray-500">
        <x-slot name="trigger">
            <x-button type="button" color="gray">
                More Actions
            </x-button>
        </x-slot>

        <!-- Duplicate Template Action -->
        <a href="#" wire:click="duplicate" class="block px-4 py-2  hover:bg-gray-100 text-gray-700 flex items-center">
            <x-icon name="phosphor-copy" class="w-4 h-4 mr-2" /> Duplicate Template
        </a>
        <!-- Delete Template Action -->
        <a href="#" wire:click="delete" class="block px-4 py-2  hover:bg-gray-100 text-gray-700 text-red-600 flex items-center">
            <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Template
        </a>
    </x-dropdown>
</div>

        </form>
    </div>
    </div>
    </x-app.container>
    @endvolt
</x-layouts.app>
