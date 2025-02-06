<?php
use Livewire\Volt\Component;
use App\Models\PrivateTemplate;
use App\Models\PrivateTempPage;
use App\Models\PrivateTemplateHf;
use Filament\Notifications\Notification;
use function Laravel\Folio\{middleware, name};

middleware('auth'); // Ensure the user is authenticated
name('my'); // Name the route

new class extends Component {
    public $templates; // Store private templates

    public function mount(): void
    {
        // Fetch the private templates for the authenticated user
        $this->templates = PrivateTemplate::where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->get();
    }

    // Delete the template
    public function deleteTemplate($id): void
    {

        $templateDelete = PrivateTemplate::find($id)->first();

        if(!$templateDelete){
            Notification::make()
                ->danger()
                ->title('Template Not Found')
                ->body('The template you are trying to delete does not exist or has already been removed.')
                ->send();
            $this->redirect('/templates/my'); // Redirect to templates page after deletion error
            return;
        }


        $header = PrivateTemplateHf::where("private_template_id", $templateDelete->id)
            ->where("is_header", true)
            ->first();
        $footer = PrivateTemplateHf::where("private_template_id", $templateDelete->id)
            ->where("is_header", false)
            ->first();
        $pages = PrivateTempPage::where('private_template_id', $templateDelete->id)->get();


        // Define the target folder path
        $targetFolder = "/var/www/ezysite/public/storage/private-templates/{$templateDelete->id}";

        // Check if the folder exists
        if (file_exists($targetFolder)) {
            // Recursive function to delete files and directories
            $this->deleteDirectory($targetFolder);
        }
        if ($header) {
            $header->delete();
        }

        if ($footer) {
            $footer->delete();
        }

        if ($pages) {
            foreach ($pages as $page) {
                $page->delete();
            }
        }
        $templateDelete->delete();

        Notification::make()
            ->success()
            ->title('Template deleted successfully')
            ->send();

        $this->redirect('/templates/my'); // Redirect to templates page after deletion
    }


    public function copyDirectory($source, $target)
    {
        // Check if the source is a file or directory
        if (is_file($source)) {
            copy($source, $target);
        } elseif (is_dir($source)) {
            // Create the target directory if it doesn't exist
            if (!is_dir($target)) {
                mkdir($target, 0777, true);
            }

            // Get all files and subdirectories inside the source directory
            $files = array_diff(scandir($source), [".", ".."]);

            // Loop through files and subdirectories and copy them
            foreach ($files as $file) {
                $filePath = $source . DIRECTORY_SEPARATOR . $file;
                $targetPath = $target . DIRECTORY_SEPARATOR . $file;

                if (is_dir($filePath)) {
                    // Recursively copy subdirectories
                    $this->copyDirectory($filePath, $targetPath);
                } else {
                    // Copy the file
                    copy($filePath, $targetPath);
                }
            }
        }
    }

    private function deleteDirectory($dir)
    {
        // Check if the directory exists
        if (!is_dir($dir)) {
            return;
        }

        // Get all files and subdirectories inside the target directory
        $files = array_diff(scandir($dir), ['.', '..']);

        // Loop through files and subdirectories and delete them
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                // Recursively delete subdirectories
                $this->deleteDirectory($filePath);
            } else {
                // Delete the file
                unlink($filePath);
            }
        }

        // Remove the now-empty directory
        rmdir($dir);
    }
};
?>
<x-layouts.app>
    @volt('my') <!-- Use the private templates route -->
    <x-app.container x-data class="lg:space-y-6" x-cloak>
        {{--<div class="container mx-auto my-6">
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
        </div>--}}



         <x-app.heading
         title="My Templates"
         description="Build and customize websites easily using your saved templates."
         :border="false"
         />

<div x-data="{ searchQuery: '' }" >
        <div class="flex flex-col md:flex-row justify-between gap-4 mb-8">
            {{-- Search Bar --}}
            <div class="flex-1">
               <div class="relative">
                    <x-icon name="phosphor-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input type="text" placeholder="Search templates..." class="input pl-10" x-model="searchQuery" />
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

         @if($templates->isEmpty())
         <h3 class="text-lg font-semibold">Looks like you dont have any templates. Create your first template now</h3>
         @else

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($templates as $web)
<div x-show="
    searchQuery === '' ||
    {{ json_encode($web->template_name) }}.toLowerCase().includes(searchQuery.toLowerCase()) ||
    {{ json_encode($web->description ?? '') }}.toLowerCase().includes(searchQuery.toLowerCase())
">            <x-app.my-template-card :website="$web" />
            </div>
        @endforeach
    </div>
    @endif
</div>




    </x-app.container>
    @endvolt
</x-layouts.app>