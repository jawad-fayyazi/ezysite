<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\Template;
use App\Models\TemplateHeaderFooter;
use App\Models\TempPage;
use App\Models\WebPage; // Assuming you have the WebPage model
use App\Models\HeaderFooter;
use App\Models\Project;
use App\Models\TemplateCategory; // Import TemplateCategory model
use function Laravel\Folio\{middleware, name};

middleware('auth');
name('create');

new class extends Component implements HasForms {
    use InteractsWithForms;

    public $template_id;
    public $template;
    public $template_category; // New variable to hold the template category name
    public ?array $data = [];
    public $header;
    public $footer;
    public $pages = [];
    public $mainPage;

    public function mount($template_id): void
    {
        // Only fetch the template if it is not already loaded
        if (!$this->template) {
            $this->template_id = $template_id;
            $this->template = Template::with('category')
                ->where('template_id', $this->template_id)
                ->firstOrFail();

            $this->template_category = $this->template->category->name; // Set the category name from the relationship
        }
        $this->form->fill();


        $this->header = TemplateHeaderFooter::where("template_id", $this->template->template_id)
            ->where("is_header", true)
            ->first();
        $this->footer = TemplateHeaderFooter::where("template_id", $this->template->template_id)
            ->where("is_header", false)
            ->first();

        $this->pages = TempPage::where('template_id', $this->template_id)->get();


        $this->mainPage = TempPage::where('template_id', $this->template_id)
            ->where('main', true)
            ->first();

        if (!$this->mainPage && $this->pages->isNotEmpty()) {
            $this->mainPage = $this->pages->first();
            $this->mainPage->main = true; // Assuming `main` is the column to mark the main page
            $this->mainPage->save(); // Save the updated main page
        }
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Website Name'),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->label('Description'),
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
        $templateJson = json_decode($this->template->template_json, true);
        if ($templateJson === null) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Template JSON data is missing or invalid.')
                ->send();
            return;
        }

        // Check if header JSON is missing or invalid
        $headerJson = json_decode($this->header->json, true);
        if ($headerJson === null) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Header JSON data is missing or invalid.')
                ->send();
            return;
        }

        // Check if footer JSON is missing or invalid
        $footerJson = json_decode($this->footer->json, true);
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

        $project = auth()->user()->projects()->create([
            'project_name' => $this->data['name'],
            'description' => $this->data['description'],
            'project_json' => $templateJson, // Use the template JSON for the new website
            'header_embed' => $headerEmbedGlobal,
            'footer_embed' => $footerEmbedGlobal,
            'robots_txt' => $robotsTxt,
            'favicon' => $favIcon,
        ]);


        if ($this->template->favicon){
            $sourcePath = "/var/www/ezysite/public/storage/templates/{$this->template->template_id}/logo/{$this->template->favicon}";
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
            $pageCreated = WebPage::create([
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
}
?>
<x-layouts.app>
    @volt('create')
    <x-app.container>
        <div class="container mx-auto my-6">

            <!-- Back Button -->
            <x-elements.back-button class="max-w-full mx-auto mb-3" text="Back to {{ $this->template_category }}"
                href="/templates/starter/{{$this->template->template_category_id}}" />

            <!-- Template Details Box -->
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <div class="flex items-center justify-between mb-5">
                    <!-- Heading: Template Name -->
                    <x-app.heading title="Creating Website from {{ $template->template_name }}"
                        description="You're creating a website using this template." :border="false" />
                        <!-- Preview Button -->
                        <x-button 
                            tag="a" 
                            href="{{ $template->template_preview_link }}" 
                            target="_blank" 
                            color="secondary">
                            Preview Template
                        </x-button>
                </div>

                <!-- Template Image -->
                <div class="text-center mb-6">
                    @if ($this->template->ss)
                    <img src="{{ asset('storage/templates/' . $template->template_id . '/screenshot/' . $template->ss) }}"
                    alt="{{ $template->template_name }}" class="rounded-md shadow" />
                    @endif
                </div>

                <!-- Create Website Form -->
                <form wire:submit="create" class="space-y-6">
                    <!-- Form Fields -->
                    {{ $this->form }}

                    <div class="flex justify-end gap-x-3">
                        <x-button tag="a" href="/templates/starter/{{ $template->template_category_id }}" color="secondary">Cancel</x-button>
                        <x-button type="button" wire:click="create" class="text-white bg-primary-600 hover:bg-primary-500">
                            Create Website from Template
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>