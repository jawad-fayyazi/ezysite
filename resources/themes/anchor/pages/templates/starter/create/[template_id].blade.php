<?php

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use App\Models\Template;
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

        $templateJson = json_decode($this->template->template_json, true);

        auth()->user()->projects()->create([
            'project_name' => $this->data['name'],
            'description' => $this->data['description'],
            'project_json' => $templateJson, // Use the template JSON for the new website
        ]);

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
                    <img src="{{ asset('storage/templates_ss/screenshots/' . $template->template_id . '.png') }}"
                        alt="{{ $template->template_name }}" class="rounded-md shadow" />
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