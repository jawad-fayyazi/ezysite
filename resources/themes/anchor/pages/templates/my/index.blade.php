<?php
use Livewire\Volt\Component;
use App\Models\PrivateTemplate;
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('my'); // Name the route

new class extends Component {
    public $private_templates; // Store private templates

    public function mount(): void
    {
        // Fetch the private templates for the authenticated user
        $this->private_templates = PrivateTemplate::where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->get();



        $user = auth()->user();
        $response = $user->canCreatePage($user, 219);

        if ($response['status'] === 'danger') {
            dd($response);
        }







    }
};
?>
<x-layouts.app>
    @volt('my') <!-- Use the private templates route -->
    <x-app.container>
        <div class="container mx-auto my-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <!-- Page Header -->
                <div class="flex items-center justify-between mb-5">
                    <x-app.heading title="My Templates"
                        description="Browse all templates created by you." :border="false" />
    <x-button tag="a" :href="route('websites.create')">New Website</x-button>
                </div>
                <!-- Check if there are no private templates -->
                @if($private_templates->isEmpty())
                    <p class="text-gray-600">You haven’t created any templates yet.</p>
                @else
                    <!-- Private Templates Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        @foreach($private_templates as $template)
                            <div class="relative block bg-gray-100 p-4 rounded-lg shadow transition-all duration-300"
                                style="transform: scale(1); transition: transform 0.3s, box-shadow 0.3s;"
                                onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0px 4px 20px rgba(0, 0, 0, 0.2)';"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0px 4px 10px rgba(0, 0, 0, 0.1)';"
                                onclick="window.location.href='/templates/my/{{ $template->id }}';">
                                <!-- Prevent Default Click on x-button -->
                                <a href="/templates/my/{{ $template->id }}" class="absolute inset-0 z-0"></a>
                                <!-- Template Name -->
                                <div class="text-center">
                                    <h3 class="text-lg font-bold text-gray-700">{{ $template->template_name }}</h3>
                                </div>
                                <!-- Template Description -->
                                <p class="mt-4 text-sm text-gray-500">
                                    {{ Str::limit($template->description, 100, '...') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>