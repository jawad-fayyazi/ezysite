<section class="relative top-0 flex flex-col items-center justify-center w-full min-h-screen -mt-24 gradient-bg lg:min-h-screen">
    
        <div class="flex flex-col items-center justify-between flex-1 w-full max-w-2xl gap-6 px-8 pt-32 mx-auto text-left md:px-12 xl:px-20 lg:pt-32 lg:pb-16 lg:max-w-7xl lg:flex-row">
            <div class="w-full lg:w-1/2">
                <h1 class="text-6xl font-bold tracking-tighter text-left sm:text-7xl md:text-8xl sm:text-center lg:text-left">
    <span class="block origin-left lg:scale-90 gradient-txt text-nowrap">Create in </br> Days</span>
    <span class="block mt-4 text-black">Not Weeks!</span>
</h1>

                <p class="mx-auto mt-5 text-2xl font-normal text-left sm:max-w-md lg:ml-0 lg:max-w-md sm:text-center lg:text-left text-zinc-500">
                    Bring your vision to life with a website that works for you<span class="hidden sm:inline"> with Ezysite</span>.
                </p>
                <div class="flex flex-col items-center justify-center gap-3 mx-auto mt-8 md:gap-2 lg:justify-start md:ml-0 md:flex-row">
                    <button class="gradient-btn-primary text-xs">Get satrted</button>
                    <button class="gradient-btn-secondary text-xs">See example websites</button>
                </div>
            </div>
            <div class="flex items-center justify-center w-full mt-12 lg:w-1/2 lg:mt-0">
                <img alt="Website Preview" class="relative w-full lg:scale-125 xl:translate-x-6" src="/wave/img/web-preview.png" style="max-width:450px;">
            </div>
        </div>
        <div class="flex-shrink-0 lg:h-[150px] flex border-t border-zinc-200 items-center w-full gradient-bg">
    <div class="grid h-auto grid-cols-1 px-8 py-10 mx-auto space-y-5 divide-y max-w-7xl lg:space-y-0 lg:divide-y-0 divide-zinc-200 lg:py-0 lg:divide-x md:px-12 lg:px-20 lg:divide-zinc-200 lg:grid-cols-3">
        <div>
            <h3 class="flex items-center font-medium gradient-txt">
                Why Choose Ezysite?
            </h3>
            <p class="mt-2 text-sm text-zinc-500">
                Discover how Ezysite makes website creation simple and fast.<span class="hidden lg:inline"> Get started now!</span>
            </p>
        </div>
        <div class="pt-5 lg:pt-0 lg:px-10">
            <h3 class="font-medium gradient-txt">Your Challenges, Solved</h3>
            <p class="mt-2 text-sm text-zinc-500">
                We eliminate the hassle of complex tools and slow processes. <span class="hidden lg:inline">Your dream website is just a few clicks away.</span>
            </p>
        </div>
        <div class="pt-5 lg:pt-0 lg:px-10">
            <h3 class="font-medium gradient-txt">What Sets Us Apart</h3>
            <p class="mt-2 text-sm text-zinc-500">
                Unlike others, we combine speed, simplicity, and customization to give you full control of your site.
            </p>
        </div>
    </div>
</div>




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


</section>