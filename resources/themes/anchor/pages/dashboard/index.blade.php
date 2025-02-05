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
	public $websitesRecent = [];
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
		$this->websitesRecent = $this->websitesRecent();

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
	public function websitesRecent()
	{
		if (Auth::check()) {
			// Get the 3 most recently modified websites for the authenticated user
			$websitesRecent = auth()->user()->projects()->orderBy('updated_at', 'desc')->take(3)->get();

			return $websitesRecent;
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
      <x-app.alert id="dashboard_alert" class="hidden lg:flex">
         <a href="{{route('settings.subscription')}}" class="text-sm text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">
         🚀 Unlock more features! <span class="font-semibold">Upgrade your plan</span>
         </a>		
      </x-app.alert>
      <x-app.heading
         title="Welcome back, {{ Auth::user()->name }}!"
         description="Here's what's happening with your websites"
         :border="false"
         />
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
         @foreach ($statsCard as $card)
         @admin
         <x-app.stats-card 
            :title="$card['title']" 
            :icon="$card['icon']" 
            :value="$card['value']" 
            />
         @else
         <x-app.stats-card 
            :title="$card['title']"
            :icon="$card['icon']" 
            :value="$card['value']" 
            :max="$card['max']" 
            :progressColor="$card['progressColor']" 
            />
         @endadmin
         @endforeach
      </div>
      <div class='mb-8'>
         <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Recent Websites</h2>
            <div class="flex gap-4">
               <a href="/websites" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 flex items-center">
                  View All
                  <x-icon name="phosphor-caret-right" class="h-5 w-5" />
               </a>
               <a href="/websites/create" class="btn btn-primary">
                  <x-icon name="phosphor-plus" class="h-5 w-5 mr-2" />
                  Create New Website
               </a>
            </div>
         </div>
         @if($this->websitesRecent->isEmpty())
         <h3 class="text-lg font-semibold">Looks like you dont have any website. Create your first Website now</h3>
         @else
         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($this->websitesRecent as $web)
            <x-app.website-card
               :website="$web"
               />
            @endforeach
         </div>
         @endif
      </div>
   </x-app.container>
   <script src="{{ asset('builder/js/app.js') }}"></script>
   @endvolt
</x-layouts.app>