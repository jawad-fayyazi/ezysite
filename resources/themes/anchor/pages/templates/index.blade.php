<?php
use function Laravel\Folio\{middleware, name};
use App\Models\Template; // Assuming you have a Template model
use Livewire\Volt\Component;

middleware('auth');  // This ensures the user is authenticated

name('templates');

new class extends Component {
    public $categories;

    public function mount()
    {
        // Fetch distinct categories from the templates table
        // You can replace 'category' with the actual name of the column holding the category in the table
        $this->categories = Template::select('template_category')->distinct()->orderBy('template_category')->get();
    }
}
?>
<x-layouts.app>
    @volt('templates')
    <x-app.container>
        <div class="container mx-auto my-6">
            <div class="bg-white p-6 rounded-lg shadow-lg">

                <div class="flex items-center justify-between mb-5">
                    <x-app.heading title="All Template Categories" description="Browse templates by category"
                        :border="false" />
                    <x-button tag="a" 
                        :href="route('websites.create')">New Website</x-button>
                </div>

                @if($categories->isEmpty())
                <p class="text-gray-600">Looks like there are no Templates available.</p>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    @foreach($categories as $category)
                    <a href="/templates/{{ $category->template_category }}"
                        class="block bg-gray-100 p-4 rounded-lg shadow transition-all duration-300"
                        style="transform: scale(1); transition: transform 0.3s, box-shadow 0.3s;"
                        onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0px 4px 20px rgba(0, 0, 0, 0.2)';"
                        onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0px 4px 10px rgba(0, 0, 0, 0.1)';">
                        <div class="text-center">
                            <!-- Category Name -->
                            <h3 class="text-lg font-bold text-gray-700">{{ $category->template_category }}</h3>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </x-app.container>
    @endvolt
</x-layouts.app>