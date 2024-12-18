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

            $pageInstance->delete();

            Notification::make()->success()->title('Page deleted successfully.')->send();

            // Return a response with the pageId to trigger JS
            return redirect('/websites' . '/' . $this->project->project_id)->with('pageDeleted', $pageInstance->page_id);
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
                        <div
                            class="absolute inset-0 w-full h-full pointer-events-none"
                            style="border: none; transform: scale(0.2); transform-origin: top left; width: 1250px; height: 750px;">
                            @include('builder.index', ['project_name' => $this->project->project_name, 'project_id' => $this->project->project_id])
                        </div>

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
                            <a wire:click="$set('activeTab', 'headerFooter')" href="#header/footer" id="headerfooter"
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
                                            <a href="#" wire:click="pageDelete"
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
                    <div id="page-list" class="space-y-4 mt-4">
                        <!-- Pages List Container -->
                        <ul id="settings-page-list" class="list-group space-y-4">
                            <!-- Pages will be dynamically rendered here by the renderPagesInSettings function -->
                        </ul>
                    </div>
                @endif
            </div>
        </div>

                  
<script>

    function renderPagesInSettings() {
        const settingsPageList = document.getElementById('settings-page-list'); // Assuming the list container in your settings page
        settingsPageList.innerHTML = ""; // Clear current list

        const allPages = pagesApi.getAll(); // Get all pages using the GJS API
        const selectedPageId = pagesApi.getSelected()?.id; // Get the ID of the selected page

        // If there are pages, loop through and add them to the list
        if (allPages.length > 0) {
            allPages.forEach((pageItem) => {
                const listItem = document.createElement("li");
                listItem.className = "settings-page-item"; // Class name for styling
                const isSelected = selectedPageId && pageItem.id === selectedPageId;

                listItem.innerHTML = `
<div class="bg-white p-6 rounded-md shadow-md">
    <div class="settings-page-name-container flex justify-between items-center cursor-pointer">
        <div class="settings-page-name text-lg font-semibold text-left w-full" contenteditable="false">
            ${pageItem.getName()}</div>
    </div>



<div class="settings-sub-menu mt-4">


            <div class="mt-3">
                <input value="${pageItem.getName()}" type="text" class="name-input p-2 border rounded-md w-full mb-2"
                    placeholder="Enter new page name" />
                <input value="${pageItem.get('meta')?.title || ''}" type="text" id="meta-title-${pageItem.id}" placeholder="Title"
                    class="p-2 border rounded-md w-full mb-2" />
                <input value="${pageItem.get('meta')?.description || ''}" type="text" id="meta-description-${pageItem.id}"
                    placeholder="Meta Description" class="p-2 border rounded-md w-full mb-2" />
                <textarea id="meta-og-${pageItem.id}" placeholder="OG Tags"
                    class="p-2 border rounded-md w-full mb-2">${pageItem.get('meta')?.ogTags || ''}</textarea>
                <textarea id="header-embed-${pageItem.id}" placeholder="Header Embed"
                    class="p-2 border rounded-md w-full mb-2">${pageItem.get('meta')?.headerEmbed || ''}</textarea>
                <textarea id="footer-embed-${pageItem.id}" placeholder="Footer Embed"
                    class="p-2 border rounded-md w-full">${pageItem.get('meta')?.footerEmbed || ''}</textarea>
            </div>


            <div class="flex justify-end gap-x-3">
                <!-- Button for Save Changes -->
                <x-button class="save-changes-btn" type="button" color="primary">Save Changes</x-button>
                <!-- Button for Delete -->
                <x-button class="delete-page-btn" type="button" color="danger">Delete</x-button>
                <!-- Button for Cancel -->
                <x-button class="cancel-action-btn" type="button" color="secondary">Cancel</x-button>
            </div>
        </div>
    </div>
</div>
`;

                settingsPageList.appendChild(listItem);

                const settingsSubMenu = listItem.querySelector(".settings-sub-menu");
                const saveChangesBtn = listItem.querySelector(".save-changes-btn");
                const deleteBtn = listItem.querySelector(".delete-page-btn");
                const cancelBtn = listItem.querySelector(".cancel-action-btn");
                const pageNameElement = listItem.querySelector(".settings-page-name");

                // Initially hide the sub-menu
                settingsSubMenu.style.display = "none"; // Hide sub-menu initially

                // Toggle sub-menu visibility on page name div click only
                const pageNameContainer = listItem.querySelector(".settings-page-name-container");
                pageNameContainer.addEventListener("click", function (e) {
                    e.stopPropagation(); // Prevent event bubbling

                    // Close any other open sub-menus
                    document.querySelectorAll(".settings-sub-menu").forEach((menu) => {
                        if (menu !== settingsSubMenu && menu.style.display === "block") {
                            menu.style.animation = "slideUp 0.2s ease-in forwards";
                            setTimeout(() => {
                                menu.style.display = "none"; // Hide after slide-up animation
                                menu.style.animation = ""; // Reset animation
                            }, 200);
                        }
                    });

                    // Toggle current sub-menu visibility
                    if (settingsSubMenu.style.display === "none") {
                        settingsSubMenu.style.display = "block";
                        settingsSubMenu.style.animation = "slideDown 0.2s ease-out forwards";
                    } else {
                        settingsSubMenu.style.animation = "slideUp 0.2s ease-in forwards";
                        setTimeout(() => {
                            settingsSubMenu.style.display = "none";
                            settingsSubMenu.style.animation = "";
                        }, 200);
                    }
                });

                // Handle Cancel button click
                cancelBtn.addEventListener("click", (e) => {
                    e.stopPropagation(); // Prevent li click from triggering page selection
                   
                    pageNameElement.contentEditable = "false"; // Make the page name non-editable again
                    renderPagesInSettings();
                });

                // Handle Save Changes action (Save Name and Title)
                saveChangesBtn.addEventListener("click", () => {
                    const newPageName = listItem.querySelector(".name-input").value.trim();
                    const title = document.getElementById(`meta-title-${pageItem.id}`).value.trim();
                    const description = document.getElementById(`meta-description-${pageItem.id}`).value.trim();
                    const ogTags = document.getElementById(`meta-og-${pageItem.id}`).value.trim();
                    const headerEmbed = document.getElementById(`header-embed-${pageItem.id}`).value.trim();
                    const footerEmbed = document.getElementById(`footer-embed-${pageItem.id}`).value.trim();

                    if (!newPageName) {
                        alert("Page name cannot be empty!");
                        return; // Stop execution if the page name is invalid
                    }

                    // Update the page name
                    pageItem.setName(newPageName);

                    // Save metadata inside the page object using GJS API
                    pageItem.set({
                        meta: {
                            title: title, // Use an empty string if input is blank
                            description: description,
                            ogTags: ogTags,
                            headerEmbed: headerEmbed,
                            footerEmbed: footerEmbed,
                        },
                    });

                    renderPagesInSettings(); // Refresh the list of pages
                    editor.store();
                });


                // Handle Delete button click
                deleteBtn.addEventListener("click", (e) => {
                    e.stopPropagation(); // Prevent li click from triggering page selection
                    if (confirm(`Are you sure you want to delete the page: "${pageItem.getName()}"?`)) {
                        pagesApi.remove(pageItem.id); // Delete the page using the GJS API
                        renderPagesInSettings(); // Re-render the list after deletion
                    }
                });
            });
        } else {
            const noPagesMessage = document.createElement("li");
            noPagesMessage.className = "settings-page-item";
            noPagesMessage.textContent = "No pages available.";
            settingsPageList.appendChild(noPagesMessage);
        }
    }
  
    
    
    var hf = document.getElementById('headerfooter');
    hf.addEventListener('click', function () {
        const checkInterval = setInterval(function () {
            const pageListContainer = document.getElementById('settings-page-list');

            // If the container exists, call the function and stop checking
            if (pageListContainer) {
                renderPagesInSettings();
                clearInterval(checkInterval); // Stop the interval once the element is found
            }
        }, 100); // Check every 100ms
    });

</script>

        

    </x-app.container>
    @endvolt
</x-layouts.app>


