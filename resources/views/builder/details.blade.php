<x-layouts.app>
    <x-app.container>
        {{-- Success and Error Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="container mx-auto my-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">

                {{-- Project Name and Edit in Builder Button --}}
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-semibold text-gray-800">Project: {{ $project->project_name }}</h2>
                    <x-button tag="a"
                        href="{{ route('builder', ['project_id' => $project->project_id, 'project_name' => $project->project_name]) }}"
                        class="bg-blue-600 hover:bg-blue-700 text-white">
                        Edit in Builder
                    </x-button>
                </div>

                {{-- Rename Project Section --}}
                <div class="mb-6">
                    <h3 class="text-xl font-medium text-gray-600">Rename Project</h3>
                    <form action="{{ route('projects.rename', $project->project_id) }}" method="POST"
                        class="flex items-center space-x-4 mt-2">
                        @csrf
                        <x-input type="text" name="project_name"
                            value="{{ old('project_name', $project->project_name) }}" class="w-1/2" required />
                        @error('project_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <x-button type="submit" class="bg-green-600 hover:bg-green-700 text-white">Rename</x-button>
                    </form>
                </div>

                {{-- Duplicate Project Section --}}
                <div class="mb-6">
                    <h3 class="text-xl font-medium text-gray-600">Duplicate Project</h3>
                    <form action="{{ route('projects.duplicate', $project->project_id) }}" method="POST" class="mt-2">
                        @csrf
                        <x-button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white">Duplicate</x-button>
                    </form>
                </div>

                {{-- Domain Update Section --}}
                <div class="mb-6">
                    <h3 class="text-xl font-medium text-gray-600">Change Domain</h3>
                    <form action="#" method="POST" class="mt-2">
                        @csrf
                        <x-input type="text" name="domain" placeholder="Enter new domain" class="w-1/2" />
                        <x-button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white">Update
                            Domain</x-button>
                    </form>
                </div>

                {{-- Delete Project Section --}}
                <div class="mb-6">
                    <h3 class="text-xl font-medium text-gray-600">Delete Project</h3>
                    <form id="delete-project-{{ $project->project_id }}"
                        action="{{ route('projects.delete', $project->project_id) }}" method="POST" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" class="bg-red-600 hover:bg-red-700 text-white">Delete Project</x-button>
                    </form>
                </div>

                {{-- Save and Cancel Buttons --}}
                <div class="flex items-center justify-between mt-6">
                    <x-button class="bg-gray-500 hover:bg-gray-600 text-white">Cancel</x-button>
                    <x-button class="bg-green-600 hover:bg-green-700 text-white">Save</x-button>
                </div>
            </div>
        </div>
    </x-app.container>
</x-layouts.app>