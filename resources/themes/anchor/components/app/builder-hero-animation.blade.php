{{-- Website Builder Animation --}}
<div x-data
    x-init="$el.classList.add('opacity-0', 'scale-90'); setTimeout(() => $el.classList.remove('opacity-0', 'scale-90'), 200)"
    class="mt-16 relative transition-all duration-700 ease-in-out">
    <div class="relative max-w-5xl mx-auto">
        {{-- Background Glow --}}
        <div class="absolute inset-0 bg-gradient-to-r from-primary-500/30 to-secondary-500/30 blur-3xl"></div>

        {{-- Builder Interface --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden transition-transform duration-[6000ms] ease-in-out"
            x-data="{ floating: true }" x-init="setInterval(() => floating = !floating, 6000)"
            x-bind:class="floating ? 'translate-y-0' : '-translate-y-2'">
            {{-- Top Bar --}}
            <div
                class="bg-gray-100 dark:bg-gray-700 p-4 flex items-center justify-between border-b border-gray-200 dark:border-gray-600">
                <div class="flex items-center space-x-2">
                    <x-icon name="phosphor-code" class="h-6 w-6 text-primary-600 animate-spin"
                        x-init="setInterval(() => $el.classList.toggle('rotate-360'), 2000)"></x-icon>
                    <span class="font-semibold">Website Builder</span>
                </div>
                <div class="flex space-x-4">
                    <button class="btn btn-primary py-1 px-3 hover:scale-105 transition-transform">Preview</button>
                    <button class="btn btn-primary py-1 px-3 hover:scale-105 transition-transform">Publish</button>
                </div>
            </div>

            {{-- Builder Content --}}
            <div class="grid grid-cols-12 gap-0 h-[400px]">
                {{-- Left Sidebar - Components --}}
                <div class="col-span-2 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 p-4 opacity-0 translate-x-[-50px] transition-all duration-700 delay-200"
                    x-init="$el.classList.remove('opacity-0', 'translate-x-[-50px]')">
                    <div class="space-y-4">
                        <div
                            class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm cursor-move hover:scale-105 transition-transform">
                            <x-icon name="phosphor-pencil" class="h-5 w-5 text-primary-600"></x-icon>
                        </div>
                        <div
                            class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm cursor-move hover:scale-105 transition-transform">
                            <x-icon name="phosphor-image" class="h-5 w-5 text-primary-600"></x-icon>
                        </div>
                        <div
                            class="p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm cursor-move hover:scale-105 transition-transform">
                            <x-icon name="phosphor-stack" class="h-5 w-5 text-primary-600"></x-icon>
                        </div>
                    </div>
                </div>

                {{-- Main Content Area --}}
                <div class="col-span-8 bg-gray-100 dark:bg-gray-800 p-8 opacity-0 translate-y-[50px] transition-all duration-700 delay-400"
                    x-init="$el.classList.remove('opacity-0', 'translate-y-[50px]')">
                    {{-- Draggable Elements --}}
                    <div
                        class="bg-white dark:bg-gray-700 p-6 rounded-lg shadow-lg mb-4 hover:scale-105 transition-transform cursor-move">
                        <h2 class="text-2xl font-bold mb-4">Welcome to My Website</h2>
                        <p class="text-gray-600 dark:text-gray-400">Drag and drop to build your perfect website.</p>
                    </div>

                    <div
                        class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-lg hover:scale-105 transition-transform cursor-move">
                        <div
                            class="h-32 bg-gradient-to-r from-primary-500/20 to-secondary-500/20 rounded-lg flex items-center justify-center">
                            <x-icon name="phosphor-image" class="h-8 w-8 text-primary-600"></x-icon>
                        </div>
                    </div>
                </div>

                {{-- Right Sidebar - Settings --}}
                <div class="col-span-2 bg-gray-50 dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 p-4 opacity-0 translate-x-[50px] transition-all duration-700 delay-600"
                    x-init="$el.classList.remove('opacity-0', 'translate-x-[50px]')">
                    <div class="space-y-4">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>