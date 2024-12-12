<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Button;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use App\Models\WebPage;
use Livewire\Volt\Component;
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('websites.tabs.pages_settings'); // Name the route

class PageSettings extends Component implements HasForms
{
    use InteractsWithForms;

    public $pages;
    public $currentPageId;
    public WebPage $page;

    public function mount($projectId): void
    {
        // Fetch pages related to the current project
        $this->pages = WebPage::where('website_id', $projectId)->get();
    }

    public function pageSettingsForm(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Page Title')
                ->placeholder('Enter the title for the page')
                ->maxLength(255),

            TextInput::make('rename')
                ->label('Rename Page')
                ->placeholder('Enter a new name for the page')
                ->maxLength(255),

            Checkbox::make('is_main')
                ->label('Set as Main Page'),

            Textarea::make('meta_description')
                ->label('Meta Description')
                ->placeholder('Enter meta description for the page')
                ->rows(3),

            Textarea::make('og_tags')
                ->label('OG Tags')
                ->placeholder('Enter Open Graph tags for the page')
                ->rows(3),

            Textarea::make('header_embed')
                ->label('Embed Code for Header')
                ->placeholder('Enter custom header embed code')
                ->rows(5),

            Textarea::make('footer_embed')
                ->label('Embed Code for Footer')
                ->placeholder('Enter custom footer embed code')
                ->rows(5),

            Button::make('save')
                ->label('Save Changes')
                ->type('submit')
                ->color('primary')
                ->action(fn() => $this->savePage())
        ]);
    }

    public function savePage(): void
    {
        // Find the current page and update it
        $this->page->update([
            'title' => $this->title,
            'name' => $this->rename,
            'meta_description' => $this->meta_description,
            'og' => $this->og_tags,
            'embed_code_start' => $this->header_embed,
            'embed_code_end' => $this->footer_embed,
            'main' => $this->is_main,
        ]);

        session()->flash('message', 'Page updated successfully!');
    }

}
?>


@volt('websites.tabs.pages_settings')
<div class="space-y-6">
    <h2 class="text-2xl font-semibold">Page Settings</h2>

    <!-- Collapsible Pages List -->
    <div class="space-y-4">
        @foreach ($pages as $page)
            <div class="bg-gray-100 p-4 rounded-lg shadow-sm">
                <button class="flex items-center justify-between w-full text-left" @click="openPageSettings({{ $page->id }})">
                    <span>{{ $page->name }}</span>
                    <x-icon name="phosphor-caret-down" class="w-4 h-4"/>
                </button>

                <!-- Page Options Form (hidden by default) -->
                <div x-show="selectedPageId === {{ $page->id }}" class="mt-4 space-y-4">
                    <form wire:submit.prevent="savePage">
                        {!! $this->pageSettingsForm($this->form) !!}

                        <!-- Action Buttons -->
                        <div class="flex justify-between mt-4">
                            <x-button type="submit" color="primary">Save Changes</x-button>
                            <x-button 
                                type="button" 
                                color="secondary" 
                                wire:click="duplicatePage({{ $page->id }})"
                            >
                                Duplicate Page
                            </x-button>
                            <x-button 
                                type="button" 
                                color="danger" 
                                wire:click="deletePage({{ $page->id }})"
                            >
                                Delete Page
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Add New Page Button -->
    <div class="mt-6">
        <x-button color="success" wire:click="createPage">
            Add a New Page
        </x-button>
    </div>
</div>

<script>
    function openPageSettings(pageId) {
        this.selectedPageId = pageId;
    }
</script>
@endvolt
