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
use App\Models\Project;
use App\Models\PrivateTemplate;
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
    public ?array $pageData = []; // Pages form data


    // Mount method to set the project_id from the URL and fetch the project
    public function mount($project_id): void
    {
        $this->project_id = $project_id; // Set the project_id dynamically from the URL

        // Retrieve the project using the project_id and authenticate it
        $this->project = auth()->user()->projects()->where('project_id', $this->project_id)->firstOrFail();

        // Retrieve pages for the project
        $this->pages = WebPage::where('website_id', $this->project_id)->get();


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



    // Define the form schema
    public function form(Form $form): Form
    {
        if ($this->activeTab === 'website_settings') {
            return $form
                ->schema([
                    // Rename Website Field
                    TextInput::make('rename')
                        ->label('Website Name')
                        ->placeholder('Enter Website Name')
                        ->maxLength(255),

                    // Description Textarea
                    Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Describe your website')
                        ->rows(5)
                        ->maxLength(1000),

                    // Logo Uploader
                    FileUpload::make('logo')
                        ->label('Upload Logo')
                        ->image()
                        ->directory("usersites/{$this->project->project_id}")
                        ->disk('public')
                        ->maxSize(1024)
                        ->helperText('Upload a logo for your website'),

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

        if ($this->activeTab === 'pages') {

            return $form
                ->schema([
                    TextInput::make('page_name')
                        ->label('Page Name')
                        ->placeholder('Enter page name')
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
                    TextInput::make('page_og_tags')
                        ->label('Open Graph Tags')
                        ->placeholder('Enter OG tags'),
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
        // Default case or another tab condition
        return $form->schema([]); // Or return a different form structure
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

        // Update project details
        $this->project->update([
            'project_name' => $data['rename'],
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



    // Update the page data
    public function pageUpdate()
    {
        $pageInstance = WebPage::find($this->selectedPage); // Find the selected page
        if ($pageInstance) {
            // Update the selected page's data

            $pageInstance->update([
                'name' => $this->pageData['page_name'],
                'title' => $this->pageData['page_title'],
                'meta_description' => $this->pageData['page_meta_description'],
                'og' => $this->pageData['page_og_tags'],
                'embed_code_start' => $this->pageData['page_header_embed_code'],
                'embed_code_end' => $this->pageData['page_footer_embed_code'],
            ]);

            Notification::make()->success()->title('Page data updated successfully.')->send();
            $this->redirect('/websites' . '/' . $this->project->project_id);

        } else {
            Notification::make()->danger()->title('Page not found.')->send();
            $this->redirect('/websites' . '/' . $this->project->project_id);
        }
    }

    // Delete the page
    public function pageDelete()
    {
        $pageInstance = WebPage::find($this->selectedPage); // Find the selected page
        if ($pageInstance) {


            // Update only the fields that are not empty
            $pageInstance->delete();

            Notification::make()->success()->title('Page deleted successfully.')->send();

        } else {
            Notification::make()->danger()->title('Page not found.')->send();
            $this->redirect('/websites' . '/' . $this->project->project_id);

        }
    }

    // Update the main page
    public function pageMain()
    {
        $pageInstance = WebPage::find($this->selectedPage); // Find the selected page
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

    // Duplicate the page
    public function pageDuplicate()
    {
        $pageInstance = WebPage::find($this->selectedPage); // Find the selected page
        if ($pageInstance) {

            $newPage = $pageInstance->replicate();
            $newPage->name = $pageInstance->name . '(Copy)';

            Notification::make()->success()->title('Main Page updated successfully.')->send();
        } else {
            Notification::make()->danger()->title('Page not found.')->send();
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

                    <div class="relative"
                        style="width: 250px; height: 141px; overflow: hidden; border: 1px solid #ccc; border-radius: 8px;">
                        <!-- GrapesJS Builder Embedded in an iframe with scaling -->
                        <iframe
                            src="{{ route('builder', ['project_id' => $this->project->project_id, 'project_name' => $this->project->project_name]) }}"
                            class="absolute inset-0 w-full h-full pointer-events-none"
                            style="border: none; transform: scale(0.2); transform-origin: top left; width: 1250px; height: 750px;">
                        </iframe>

                        <!-- Transparent Overlay with Hover Effect -->
                        <a href="{{ route('builder', ['project_id' => $this->project->project_id, 'project_name' => $this->project->project_name]) }}"
                            target="_blank"
                            class="absolute inset-0 bg-transparent flex items-center justify-center group cursor-pointer">

                            <!-- Transparent background and hover effect -->
                            <div class="flex absolute top-0 left-0 w-full h-full flex items-center justify-center">
                                <!-- Pencil Icon from Phosphor Icons -->
                                <x-icon name="phosphor-pencil" class="w-6 h-6" />
                            </div>

                        </a>

                    </div>


                </div>
                <!-- Tabs Navigation -->
                <div class="mb-6 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                        <li class="mr-2">
                            <a wire:click="$set('activeTab', 'overview')" href="#overview"
                                class="inline-block p-4 rounded-t-lg {{ $activeTab === 'overview' ? 'border-b-2 border-blue-500' : '' }}">Overview</a>
                        </li>
                        <li class="mr-2">
                            <a wire:click="$set('activeTab', 'headerFooter')" href="#header/footer"
                                class="inline-block p-4 rounded-t-lg {{ $activeTab === 'headerFooter' ? 'border-b-2 border-blue-500' : '' }}">Header/Footer</a>
                        </li>
                        <li class="mr-2">
                            <a wire:click="$set('activeTab', 'pages')" href="#pages"
                                class="inline-block p-4 rounded-t-lg {{ $activeTab === 'pages' ? 'border-b-2 border-blue-500' : '' }}">Pages</a>
                        </li>
                        <li class="mr-2">
                            <a wire:click="$set('activeTab', 'website_settings')" href="#webiste_settings"
                                class="inline-block p-4 rounded-t-lg {{ $activeTab === 'website_settings' ? 'border-b-2 border-blue-500' : '' }}">Website Settings</a>
                        </li>
                        <li class="mr-2">
                            <a wire:click="$set('activeTab', 'live_settings')" href="#live_settings"
                                class="inline-block p-4 rounded-t-lg {{ $activeTab === 'live_settings' ? 'border-b-2 border-blue-500' : '' }}">Live Settings</a>
                        </li>
                    </ul>
                </div>
                <!-- Overview Tab Content -->
                @if ($activeTab === 'overview')
                    <div class="space-y-6">
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
                                <a href="{{ 'https://' . $this->project->domain . '.test.wpengineers.com' }}"
                                    class="text-blue-600 hover:underline"
                                    target="_blank">{{ 'https://' . $this->project->domain . '.test.wpengineers.com' }}</a>
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


                @elseif ($activeTab === 'website_settings')
                    <!-- Website Settings Box -->
                    <form wire:submit="edit" class="space-y-6">
                        <h2 class="text-lg font-semibold mb-4">Website Settings</h2>
                        
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
                        
                @elseif($activeTab === 'pages')

                                                        <h3 class="text-lg font-semibold mt-8">Pages</h3>

                                                        <div class="space-y-4 mt-4">
                                                            @foreach($this->pages as $page)
                                                                        <div class="bg-white p-6 rounded-md shadow-md">
                                                                            <!-- Page Title with toggle -->
                                                                            <div class="flex justify-between items-center">
                                                                                <button wire:click="updatePageList({{ $page->id }})"
                                                                                    class="text-lg font-semibold text-left w-full">
                                                                                    {{ $page->name }}
                                                                                </button>
                                                                            </div>

                                                                            <!-- Page Settings (Only show if selectedPage matches the page ID) -->
                                                                            @if ($selectedPage === $page->id)
                                                                                <div class="mt-4">
                                                                                    <div class="space-y-3">
                                                                                       {{$this->form}}
                                                                        <div class="flex justify-end gap-x-3">
                                                                        <!-- Cancel Button -->
                                                                        <x-button tag="a" href="/websites" color="secondary">Cancel</x-button>

                                                                        <!-- Save Changes Button -->
                                                                        <x-button type="button" wire:click="pageUpdate"
                                                                            class="text-white bg-primary-600 hover:bg-primary-500">Save
                                                                            Changes</x-button>

                                                                            <!-- Dropdown for More Actions -->
                                                                            <x-dropdown class="text-gray-500">
                                                                                <x-slot name="trigger">
                                                                                    <x-button type="button" color="gray">More Actions</x-button>
                                                                                </x-slot>

                                                                                <!-- Duplicate Website -->
                                                                            <a href="#" wire:click="pageDuplicate"
                                                                                class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                                                                                <x-icon name="phosphor-copy" class="w-4 h-4 mr-2" /> Duplicate Page
                                                                            </a>

                                                                            <!-- Save as My Template -->
                                                                            <a href="#" wire:click="pageMain"
                                                                                class="block px-4 py-2 text-gray-700 hover:bg-gray-100 flex items-center">
                                                                                <x-icon name="phosphor-star" class="w-4 h-4 mr-2" /> Set as Main Page
                                                                            </a>

                                                                            <!-- Delete Website -->
                                                                            <a href="#" onclick="pageDeleteGJS({{$page->page_id}})"
                                                                            class="block px-4 py-2 text-red-600 hover:bg-gray-100 flex items-center">
                                                                                <x-icon name="phosphor-trash" class="w-4 h-4 mr-2" /> Delete Page
                                                                            </a>
                                                                        </x-dropdown>
                                                                    </div>
                                                                                    </div>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>



                @elseif ($activeTab === 'headerFooter')
                    <p>Header/Footer Content Goes Here.</p>
                @endif
            </div>
        </div>
                    <script>
                        
                       function pageDeleteGJS(pgId) {
                            const page = pagesApi.getAll().find((p) => p.id === pgId); // Find the page by ID
                            const pageName = page ? page.getName() : "Unknown Page"; // Get the page name, fallback if not found

                            // Ask for confirmation
                            if (confirm(`Are you sure you want to delete the page: "${pageName}"?`)) {
                                pagesApi.remove(pgId); // Delete the selected page
                                renderPages(); // Re-render the list after deletion
                            }
                        };
                    </script>
    </x-app.container>
    @endvolt
</x-layouts.app>


