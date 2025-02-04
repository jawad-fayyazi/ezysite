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
use App\Models\WebPage;


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

        
        $pageId = uniqid();

        $projectJson = json_encode([
            "assets" => [],
            "styles" => [],
            "pages" => [
                [
                    "name" => "Page 1",
                    'id' => $pageId,
                ]
            ],
            "symbols" => [],
            "dataSources" => []
        ]);

        $project = auth()->user()->projects()->create([
            'project_name' => $this->data['name'],
            'description' => $this->data['description'],
            'project_json' => $projectJson,  // Default value for project_json
        ]);

        $this->form->fill();



        $name = 'Page 1';
        $projectId = $project->project_id;
        $slug = Str::slug($name);

        $page = WebPage::create([
            'name' => $name,
            'page_id' => $pageId,
            'website_id' => $projectId,
            'main' => 1,  // Default is 0, as it won't be the main page initially
            'title' => $name . " - " . $project->project_name,
            'html' => '<body></body>',
            'css' => '* { box-sizing: border-box; } body {margin: 0;}',
            'slug' => $slug,
        ]);

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
    <x-app.container>
        <div class="container mx-auto my-6">
        
        <x-elements.back-button
        class="max-w-full mx-auto mb-3"
        text="Back to Websites"
        :href="route('websites')"
        />
        
        <!-- Box with background, padding, and shadow -->
    <div class="bg-white p-6 rounded-lg shadow-lg">

        <div class="flex items-center justify-between mb-5">
            <x-app.heading title="Create Website" description="Fill out the form below to create a new website"
                :border="false" />
        </div>
        <form wire:submit="create" class="space-y-6">
            {{ $this->form }}
            <div class="flex justify-end gap-x-3">
                <x-button tag="a" href="/websites" color="secondary">Cancel</x-button>
                <x-button type="button" wire:click="create" class="text-white bg-primary-600 hover:bg-primary-500">
                    Create Website
                </x-button>
            </div>
        </form>
    </div>
    </div>
    </x-app.container>
    @endvolt
</x-layouts.app>