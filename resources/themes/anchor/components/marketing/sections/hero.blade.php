<section class="relative top-0 flex flex-col items-center justify-center w-full min-h-screen -mt-24  lg:min-h-screen">
    
        <div class="flex flex-col items-center justify-between flex-1 w-full max-w-2xl gap-6 px-8 pt-32 mx-auto text-left md:px-12 xl:px-20 lg:pt-32 lg:pb-16 lg:max-w-7xl lg:flex-row">
            <div 
    x-data="{ show: false, scale: false }" 
    x-init="setTimeout(() => { show = true; scale = true }, 200)"
    class="space-y-6"
>
    <!-- Animated Badge -->
    <div 
        x-show="scale"
        x-transition.scale.90
        class="inline-flex items-center px-4 py-2 rounded-full bg-primary-50 dark:bg-primary-900/50 mb-8"
    >
        <x-icon name='phosphor-sparkle' class="h-5 w-5 text-primary-500 mr-2" />
        <span class="text-primary-700 dark:text-primary-300 font-medium">
            Launching Your Digital Presence
        </span>
    </div>

    <!-- Animated Heading & Text -->
    <div 
        x-show="show"
        x-transition.opacity
        x-transition.duration.800ms
        class="space-y-4"
    >


        <h1 class="text-6xl font-bold tracking-tighter text-left sm:text-7xl md:text-8xl sm:text-center lg:text-left">
            <span class="block origin-left lg:scale-90 text-nowrap bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">Create in </br> Days</span>
            <span class="block mt-4">Not Weeks!</span>
        </h1>
        
        <p
            class="mx-auto mt-5 text-2xl font-normal text-left sm:max-w-md lg:ml-0 lg:max-w-md sm:text-center lg:text-left text-gray-600 dark:text-gray-400">
            Bring your vision to life with a website that works for you<span class="hidden sm:inline"> with <span class="font-semibold text-primary-600">Ezysite</span></span>.
        </p>

        <!-- Buttons -->
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                Start Creating
                <x-icon name='phosphor-arrow-right' class="ml-2 h-5 w-5" />
            </a>
            <a href="{{ route('templates') }}" class="btn btn-outline">
                Explore Templates
            </a>
        </div>
    </div>
</div>

            <div class="flex items-center justify-center w-full mt-12 lg:w-1/2 lg:mt-0">
                {{--<img alt="Website Preview" class="relative w-full lg:scale-125 xl:translate-x-6" src="/wave/img/web-preview.png" style="max-width:450px;">--}}
                <x-app.builder-hero-animation/>
            </div>
        </div>
        <div class="flex-shrink-0 lg:h-[150px] flex border-t border-zinc-200 items-center w-full ">
    <div class="grid h-auto grid-cols-1 px-8 py-10 mx-auto space-y-5 divide-y max-w-7xl lg:space-y-0 lg:divide-y-0 divide-zinc-200 lg:py-0 lg:divide-x md:px-12 lg:px-20 lg:divide-zinc-200 lg:grid-cols-3">
        <div class="pt-5 lg:pt-0 lg:px-10">
            <h3 class="font-medium">
                Why Choose <span class="font-semibold text-primary-600">Ezysite</span>?
            </h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Discover how <span class="font-semibold text-primary-600">Ezysite</span> makes website creation simple and fast.<span class="hidden lg:inline"> Get started now!</span>
            </p>
        </div>
        <div class="pt-5 lg:pt-0 lg:px-10">
            <h3 class="font-medium">Your Challenges, Solved</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                We eliminate the hassle of complex tools and slow processes. <span class="hidden lg:inline">Your dream website is just a few clicks away.</span>
            </p>
        </div>
        <div class="pt-5 lg:pt-0 lg:px-10">
            <h3 class="font-medium">What Sets Us Apart</h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Unlike others, we combine speed, simplicity, and customization to give you full control of your site.
            </p>
        </div>
    </div>
</div>
</section>