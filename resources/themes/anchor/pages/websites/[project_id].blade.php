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


    // Define the form schema
    public function form(Form $form): Form
    {
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
    target="_blank" class="absolute inset-0 bg-transparent flex items-center justify-center group cursor-pointer">

    <!-- Transparent background and hover effect -->
    <div
        class="flex absolute top-0 left-0 w-full h-full flex items-center justify-center">
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
                    <a wire:click="$set('activeTab', 'overview')" href="#"
                        class="inline-block p-4 rounded-t-lg {{ $activeTab === 'overview' ? 'border-b-2 border-blue-500' : '' }}">Overview</a>
                </li>
                <li class="mr-2">
                    <a wire:click="$set('activeTab', 'headerFooter')" href="#"
                        class="inline-block p-4 rounded-t-lg {{ $activeTab === 'headerFooter' ? 'border-b-2 border-blue-500' : '' }}">Header/Footer</a>
                </li>
                <li class="mr-2">
                    <a wire:click="$set('activeTab', 'pages')" href="#"
                        class="inline-block p-4 rounded-t-lg {{ $activeTab === 'pages' ? 'border-b-2 border-blue-500' : '' }}">Pages</a>
                </li>
                <li class="mr-2">
                    <a wire:click="$set('activeTab', 'website_settings')" href="#"
                        class="inline-block p-4 rounded-t-lg {{ $activeTab === 'website_settings' ? 'border-b-2 border-blue-500' : '' }}">Website Settings</a>
                </li>
                <li class="mr-2">
                    <a wire:click="$set('activeTab', 'live_settings')" href="#"
                        class="inline-block p-4 rounded-t-lg {{ $activeTab === 'live_settings' ? 'border-b-2 border-blue-500' : '' }}">Live Settings</a>
                </li>
            </ul>
        </div>


            <!-- Overview Tab Content -->
            @if ($activeTab === 'overview')
                @include('websites.tabs.overview')
            @elseif ($activeTab === 'website_settings')

                @include('websites.tabs.website_settings')

            @elseif ($activeTab === 'pages')
            @include('websites.tabs.pages_settings')
            @elseif ($activeTab === 'headerFooter')
                <p>Header/Footer Content Goes Here.</p>
            @endif
        </div>
    </div>
    </x-app.container>
    @endvolt
</x-layouts.app>