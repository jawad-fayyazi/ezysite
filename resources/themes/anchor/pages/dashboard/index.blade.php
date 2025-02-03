<?php
use Filament\Notifications\Notification;
use Livewire\Volt\Component;
use function Laravel\Folio\{middleware, name};
	middleware('auth');
    name('dashboard');


new class extends Component  {

	public $usedStorage = null;

	// Mount method to set the project_id from the URL and fetch the project
	public function mount(): void
	{
		$user = auth()->user();
		$this->usedStorage = $user->calculateUserStorage($user);
		$this->usedStorage = $user->formatFileSize($this->usedStorage);
	}
};
?>

<x-layouts.app>
	@volt('dashboard')
	<x-app.container x-data class="lg:space-y-6" x-cloak>
        
		<x-app.alert id="dashboard_alert" class="hidden lg:flex">This is the user dashboard where users can manage settings and access features. <a href="https://devdojo.com/wave/docs" target="_blank" class="mx-1 underline">View the docs</a> to learn more.</x-app.alert>





		<div class="mb-8">
          <h1 class="text-3xl font-bold mb-2">Welcome back, User!</h1>
          <p class="text-gray-600 dark:text-gray-400">
            Here's what's happening with your websites
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <DashboardCard/>
			<div>
				<div class="flex items-center space-x-4">
      <div class="p-3 rounded-lg bg-primary-100 dark:bg-primary-900">
        <x-icon name="phosphor-cloud" class="h-6 w-6 text-primary-600 dark:text-primary-400" />
      </div>
      <div>
        <p class="text-sm text-gray-600 dark:text-gray-400">Storage Used</p>
        <p class="text-2xl font-bold">{{$this->usedStorage}}</p>
      </div>
    </div>
			</div>
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
