<?php
use function Laravel\Folio\{middleware, name};
use App\Models\Project;
use Livewire\Volt\Component;
middleware('auth');
name('websites');

new class extends Component {
    public $projects;

    public function mount()
    {
        $this->projects = auth()->user()->projects()->orderBy('project_id', 'desc')->get();
    }

}
?>

<x-layouts.app>
    @volt('websites')
    <x-app.container>
        <div class="container mx-auto my-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">

            <div class="flex items-center justify-between mb-5">
                <x-app.heading
                        title="Your Websites"
                        description="Check out your website below"
                        :border="false"
                    />
                <x-button tag="a" 
                :href="route('websites.create')">New Website</x-button>
            </div>


                @if($projects->isEmpty())
                    <p class="text-gray-600">Looks like you dont have any website. Create your first Website now</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        @foreach($projects as $project)
                            <a href="/websites/{{ $project->project_id }}"
                                class="block bg-gray-100 p-4 rounded-lg shadow transition-all duration-300"
                                style="transform: scale(1); transition: transform 0.3s, box-shadow 0.3s;"
                                onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0px 4px 20px rgba(0, 0, 0, 0.2)';"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0px 4px 10px rgba(0, 0, 0, 0.1)';">
                                <div class="text-center">
                                    <!-- Project Name -->
                                    <h3 class="text-lg font-bold text-gray-700">{{ $project->project_name }}</h3>
                                </div>
                                <div class="mt-4">
                                    <!-- Placeholder Image -->
                                    <img src="https://placehold.co/300x200" alt="Website Image"
                                        class="w-full rounded-md shadow">
                                </div>
                                <p class="mt-4 text-sm text-gray-500">
                                    {{ Str::limit($project->description, 100, '...') }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>