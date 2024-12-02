<x-layouts.app>
    <x-app.container>
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="container mx-auto px-4 py-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-4">Create New Project</h1>
                <form action="{{ route('projects.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="project_name" class="block text-sm font-medium text-gray-700">Project Name</label>
                        <input type="text" name="project_name" id="project_name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Enter project name" required>
                    </div>
                
                    <div class="mt-6 flex items-center justify-between">
                        <!-- x-button used as a submit button -->
                        <x-button type="submit" class="text-white">
                            Create Project
                        </x-button>
                
                        <!-- Cancel button (as a link) -->
                        <x-button tag="a" :href="route('dashboard')" class="hover:underline">
                            Cancel
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </x-app.container>
</x-layouts.app>