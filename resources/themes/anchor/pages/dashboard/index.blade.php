<?php
use Filament\Notifications\Notification;
use App\Models\WebPage;
use App\Models\Project;
use Livewire\Volt\Component;
use function Laravel\Folio\{middleware, name};
	middleware('auth');
    name('dashboard');


new class extends Component  {

	public $usedStorage = null;
	public $liveWebsites = null;
	public $totalPages = null;
	public $customDomains = null;
	public $statsCard = [];

	public $totalStorage = null;
	public $totalWebsites = null;

	public $maxPages = null;
	
	public $allowedCustomDomains = null;


	// Mount method to set the project_id from the URL and fetch the project
	public function mount(): void
	{
		$user = auth()->user();
		$this->usedStorage = $user->calculateUserStorage($user);
		$this->usedStorage = $user->formatFileSize($this->usedStorage);
		$this->totalPages = $this->totalPages();
		$this->liveWebsites = $this->liveWebsites();
		$this->customDomains = 0;
		$this->maxStorage = $this->maxStorage();
		$this->maxPages = $this->maxPages();
		$this->totalWebsites = $this->totalWebsites();

		$this->statsCard();
	}

	public function statsCard()  {
		$this->statsCard = [
			[
				'title' => 'Storage Used',
				'value' => $this->usedStorage,
				'max' => $this->maxStorage,
				'icon' => 'phosphor-cloud',
				'progressColor' => 'bg-blue-600 dark:bg-blue-500'
			],
			[
				'title' => 'Live Websites',
				'value' => $this->liveWebsites,
				'max' => $this->totalWebsites,
				'icon' => 'phosphor-globe',
				'progressColor' => 'bg-green-600 dark:bg-green-500'
			],
			[
				'title' => 'Total Pages',
				'value' => $this->totalPages,
				'max' => $this->maxPages,
				'icon' => 'phosphor-browser',
				'progressColor' => 'bg-purple-600 dark:bg-purple-500'
			],
			[
				'title' => 'Custom Domains',
				'value' => $this->customDomains,
				'max' => '2',
				'icon' => 'phosphor-link-simple-horizontal',
				'progressColor' => 'bg-pink-600 dark:bg-pink-500'
			]
		];

	}


	public function maxPages()
	{
		if (Auth::check()) {
			$user = auth()->user();
			$roles = $user->getRoleNames(); // Get user roles
			// Get the role (assuming first role in collection)
			$role = $roles->first();
			// Check if the role exists in the limits
			if (!isset($user->maxWebsites[$role])) {
				return 0;
			}
			if (!isset($user->maxWebsites[$role])) {
				return 0;
			}
			

			$websitesLimit = $user->maxWebsites[$role];
			$pageLimit = $user->maxPages[$role];
			$totalPageLimit = $websitesLimit * $pageLimit;
			return $totalPageLimit;
		}
		return 0;

	}
	public function totalPages()
	{

		if (Auth::check()) {
			return Auth::user()->projects()->withCount('pages')->get()->sum('pages_count');
		}
		return 0;

	}
	public function totalWebsites()
	{
		if (Auth::check()) {

			$totalWebsites = auth()->user()->projects()->count();
			
			return $totalWebsites;
		}
		return 0;
	}
	public function maxStorage()
	{

		if (Auth::check()) {

			$user = auth()->user();
			

			$roles = $user->getRoleNames(); // Get user roles

        // Get the role (assuming first role in collection)
        $role = $roles->first();

        // Check if the role exists in the limits
        if (!isset($user->storageLimits[$role])) {
            return 0;
        }

			$storageLimit = $user->storageLimits[$role];
			$storageLimit = $user->formatFileSize($storageLimit);
			return $storageLimit;
		}
		return 0;

	}

	public function liveWebsites()
	{

		if (Auth::check()) {
			return Auth::user()->projects()->where('live', true)->count();
		}
		return 0;

	}
};
?>

<x-layouts.app>
	@volt('dashboard')
	<x-app.container x-data class="lg:space-y-6" x-cloak>
        
		<x-app.alert id="dashboard_alert" class="hidden lg:flex">This is the user dashboard where users can manage settings and access features. <a href="https://devdojo.com/wave/docs" target="_blank" class="mx-1 underline">View the docs</a> to learn more.</x-app.alert>





		<div class="mb-8">
          <h1 class="text-3xl font-bold mb-2 dark:text-white">Welcome back, {{ Auth::user()->name }}!</h1>
          <p class="text-gray-600 dark:text-gray-400">
            Here's what's happening with your websites
          </p>
        </div>



        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">	
			@foreach ($statsCard as $card)
				<x-app.stats-card 
					:title="$card['title']" 
					:icon="$card['icon']" 
					:value="$card['value']" 
					:max="$card['max']" 
					:progressColor="$card['progressColor']" 
				/>
			@endforeach
        </div>













	{{--
	<x-app.heading
	title="Dashboard"
                description="Welcome to an example application dashboard. Find more resources below."
                :border="false"
            />

        <div class="flex flex-col w-full mt-6 space-y-5 md:flex-row lg:mt-0 md:space-y-0 md:space-x-5">
            <x-app.dashboard-card
				href="https://devdojo.com/wave/docs"
				target="_blank"
				title="Documentation"
				description="Learn how to customize your app and make it shine!"
				link_text="View The Docs"
				image="/wave/img/docs.png"
			/>
			<x-app.dashboard-card
				href="https://devdojo.com/questions"
				target="_blank"
				title="Ask The Community"
				description="Share your progress and get help from other builders."
				link_text="Ask a Question"
				image="/wave/img/community.png"
			/>
        </div>

		<div class="flex flex-col w-full mt-5 space-y-5 md:flex-row md:space-y-0 md:mb-0 md:space-x-5">
			<x-app.dashboard-card
				href="https://github.com/thedevdojo/wave"
				target="_blank"
				title="Github Repo"
				description="View the source code and submit a Pull Request"
				link_text="View on Github"
				image="/wave/img/laptop.png"
			/>
			<x-app.dashboard-card
			href="https://devdojo.com"
				target="_blank"
				title="Resources"
				description="View resources that will help you build your SaaS"
				link_text="View Resources"
				image="/wave/img/globe.png"
			/>
		<v>

		<div class="mt-5 space-y-5">
			@subscriber
			<p>You are a subscribed user with the <strong>{{ auth()->user()->roles()->first()->name }}</strong> role. Learn <a href="https://devdojo.com/wave/docs/features/roles-permissions" target="_blank" class="underline">more about roles</a> here.</p>
				<x-app.message-for-subscriber />
			@else
				<p>This current logged in user has a <strong>{{ auth()->user()->roles()->first()->name }}</strong> role. To upgrade, <a href="{{ route('settings.subscription') }}" class="underline">subscribe to a plan</a>. Learn <a href="https://devdojo.com/wave/docs/features/roles-permissions" target="_blank" class="underline">more about roles</a> here.</p>
			@endsubscriber
			
			@admin
				<x-app.message-for-admin />
			@endadmin
		</div>
		--}}	
    </x-app.container>
	<script src="{{ asset('builder/js/app.js') }}"></script>
	@endvolt
</x-layouts.app>
