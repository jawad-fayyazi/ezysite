<x-layouts.app>
    <x-app.container>
        <div class="container mx-auto my-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-3xl font-semibold text-gray-800 mb-6">Your Projects</h2>

                @if($projects->isEmpty())
                    <p class="text-gray-600">You have no projects yet.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        @foreach($projects as $project)
                            <a href="{{ route('project.details', $project->project_id) }}"
                                class="block bg-gray-100 p-4 rounded-lg shadow transition-all duration-300"
                                style="transform: scale(1); transition: transform 0.3s, box-shadow 0.3s;"
                                onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0px 4px 20px rgba(0, 0, 0, 0.2)';"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0px 4px 10px rgba(0, 0, 0, 0.1)';">
                                <div class="text-center">
                                    <h3 class="text-lg font-bold text-gray-700">{{ $project->project_name }}</h3>
                                </div>
                                <div class="mt-4">
                                    <img src="https://via.placeholder.com/300x200" alt="Project Image"
                                        class="w-full rounded-md shadow">
                                </div>
                                <p class="mt-4 text-sm text-gray-500">This is a placeholder project description. Customize it as
                                    needed.</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-app.container>
</x-layouts.app>