<?php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use function Laravel\Folio\{middleware, name};
use App\Models\Project;

middleware('auth');
name('websites.create');

new class extends Component implements HasForms {
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->maxLength(1000),
                ])
            ->statePath('data');
    }

    public function create(): void
    {

        $projectJson = [];

        auth()->user()->projects()->create([
            'project_name' => $this->data['name'],
            'description' => $this->data['description'],
            'project_json' => $projectJson,  // Default value for project_json
        ]);

        $this->form->fill();

        Notification::make()
            ->success()
            ->title('Website created successfully')
            ->send();

        $this->redirect('/websites');
    }
}
    ?>

<x-layouts.app>
    @volt('websites.create')
    <x-app.container class="max-w-xl">


    <x-elements.back-button
                class="max-w-full mx-auto mb-3"
                text="Back to Websites"
                :href="route('websites')"
            />


        <div class="flex items-center justify-between mb-5">
            <x-app.heading title="Create Website" description="Fill out the form below to create a new website"
                :border="false" />
        </div>
        <form wire:submit="create" class="space-y-6">
            {{ $this->form }}
            <div class="flex justify-end gap-x-3">
                <x-button tag="a" href="/websites" color="secondary">Cancel</x-button>
                <x-button type="submit" class="text-white bg-primary-600 hover:bg-primary-500">
                    Create Website
                </x-button>
            </div>
        </form>
    </x-app.container>
    @endvolt
</x-layouts.app>