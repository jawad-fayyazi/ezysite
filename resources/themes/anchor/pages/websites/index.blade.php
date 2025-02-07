<?php
use function Laravel\Folio\{middleware, name};
use App\Models\Project;
use Livewire\Volt\Component;
middleware('auth');
name('websites');

new class extends Component {
    public $projects;
    public $ourDomain = '.test.wpengineers.com';

    public function mount()
    {
        $this->projects = auth()->user()->projects()->orderBy('updated_at', 'desc')->get();
        // Generate placeholder initials for each project
        // foreach ($this->projects as $project) {
        //     // Remove special characters and split the name
        //     $cleanedName = preg_replace('/[^\w\s]/', '', $project->project_name); // Keep only alphanumeric and spaces
        //     $words = explode(' ', trim($cleanedName));

        //     // Get first letter of the first word and last word, if available
        //     $firstLetter = isset($words[0]) ? substr($words[0], 0, 1) : '';
        //     $lastLetter = isset($words[count($words) - 1]) ? substr($words[count($words) - 1], 0, 1) : '';

        //     // Combine initials, default to "NA" if no valid characters
        //     $project->placeholder_text = strtoupper($firstLetter . $lastLetter) ?: 'NA';
        // }
    }

}
?>

<x-layouts.app>
    @volt('websites')
<x-app.container x-data class="lg:space-y-6" x-cloak>


        {{--<div class="container mx-auto my-6">
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
                                <div class="flex items-center justify-center space-x-2 group"
                                    title="{{ $project->live ? 'Website is live' : 'Website is not live' }}">
                                            @if($project->favicon)
                                                <img src="{{ asset('storage/usersites/' . $project->project_id . '/logo/' . $project->favicon) }}"
                                                    alt="Website Favicon" class="w-6 h-6 rounded-full">
                                            @endif
                                    <h3 class="text-lg font-bold text-gray-700">{{ $project->project_name }}</h3>
                                    <span class="w-3 h-3 rounded-full 
                                            {{ $project->live ? 'bg-green-500' : 'bg-red-500' }}" style="display: inline-block;">
                                    </span>
                                </div>
                                <div class="mt-4">
                                    <!-- Placeholder Image -->
                                    <img src="https://placehold.co/300x200?text={{ $project->placeholder_text }}" alt="Website Image"
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
        </div>--}}

<div>
        <x-elements.back-button text="Back to Dashboard" :href="route('dashboard')"
        />

              <x-app.heading
         title="My Websites"
         description="Manage all your websites in one place"
         :border="false"
         />
</div>
<div x-data="{ searchQuery: '' }" >
        <div class="flex flex-col md:flex-row justify-between gap-4 mb-8">
            {{-- Search Bar --}}
            <div class="flex-1">
               <div class="relative">
                    <x-icon name="phosphor-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input type="text" placeholder="Search websites..." class="input pl-10" x-model="searchQuery" />
                </div>
            </div>
        
            {{-- Create New Website Button --}}
            <div class="flex gap-4">
                <a href="{{ route('websites.create') }}" class="btn btn-primary whitespace-nowrap">
                    <x-icon name="phosphor-plus" class="h-5 w-5 mr-2" />
                    Create New Website
                </a>
            </div>
        </div>

         @if($projects->isEmpty())
         <h3 class="text-lg font-semibold">Looks like you dont have any website. Create your first website now</h3>
         @else

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($projects as $web)
<div x-show="
    searchQuery === '' ||
    {{ json_encode($web->project_name) }}.toLowerCase().includes(searchQuery.toLowerCase()) ||
    {{ json_encode($web->description ?? '') }}.toLowerCase().includes(searchQuery.toLowerCase()) ||
    {{ json_encode($web->domain . $this->ourDomain ?? '') }}.toLowerCase().includes(searchQuery.toLowerCase())
">            <x-app.website-card :website="$web" />
            </div>
        @endforeach
    </div>
    @endif
</div>



    </x-app.container>
    @endvolt
</x-layouts.app>