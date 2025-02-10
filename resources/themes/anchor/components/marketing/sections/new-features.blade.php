<!-- Features Section -->
<section class="py-16 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="max-w-7xl mx-auto px-4 mb-12 sm:px-6 lg:px-8 text-center">
    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
        Everything You Need to <span
            class="bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">Succeed</span>
    </h2>
    
    <p class="mt-4 text-base sm:text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
        Powerful features to help you build the perfect website.
    </p>
</div>


        <!-- Features Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Feature 1 -->
            <div 
                x-data="{ hover: false }" 
                @mouseenter="hover = true" 
                @mouseleave="hover = false" 
                class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow-md transform transition duration-300 ease-out"
                :class="{'-translate-y-2': hover}"
            >
                <x-icon name="phosphor-lightning" class="h-10 w-10 text-primary-600 mb-3" />
                <h3 class="text-lg font-semibold mb-1">Lightning Fast</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Build and deploy your website in minutes, not hours or days.
                </p>
            </div>

            <!-- Feature 2 -->
            <div 
                x-data="{ hover: false }" 
                @mouseenter="hover = true" 
                @mouseleave="hover = false" 
                class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow-md transform transition duration-300 ease-out"
                :class="{'-translate-y-2': hover}"
            >
                <x-icon name="phosphor-shield" class="h-10 w-10 text-primary-600 mb-3" />
                <h3 class="text-lg font-semibold mb-1">Secure by Default</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Enterprise-grade security to keep your website safe.
                </p>
            </div>

            <!-- Feature 3 -->
            <div 
                x-data="{ hover: false }" 
                @mouseenter="hover = true" 
                @mouseleave="hover = false" 
                class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow-md transform transition duration-300 ease-out"
                :class="{'-translate-y-2': hover}"
            >
                <x-icon name="phosphor-globe" class="h-10 w-10 text-primary-600 mb-3" />
                <h3 class="text-lg font-semibold mb-1">Global CDN</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Lightning-fast loading times anywhere in the world.
                </p>
            </div>

            <!-- Feature 4 -->
            <div 
                x-data="{ hover: false }" 
                @mouseenter="hover = true" 
                @mouseleave="hover = false" 
                class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow-md transform transition duration-300 ease-out"
                :class="{'-translate-y-2': hover}"
            >
                <x-icon name="phosphor-gear" class="h-10 w-10 text-primary-600 mb-3" />
                <h3 class="text-lg font-semibold mb-1">Easy Customization</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Personalize every aspect of your website with simple tools.
                </p>
            </div>

            <!-- Feature 5 -->
            <div 
                x-data="{ hover: false }" 
                @mouseenter="hover = true" 
                @mouseleave="hover = false" 
                class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow-md transform transition duration-300 ease-out"
                :class="{'-translate-y-2': hover}"
            >
                <x-icon name="phosphor-chart-bar" class="h-10 w-10 text-primary-600 mb-3" />
                <h3 class="text-lg font-semibold mb-1">Analytics & Insights</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Track your website's performance and user engagement.
                </p>
            </div>

            <!-- Feature 6 -->
            <div 
                x-data="{ hover: false }" 
                @mouseenter="hover = true" 
                @mouseleave="hover = false" 
                class="p-6 bg-white dark:bg-gray-900 rounded-lg shadow-md transform transition duration-300 ease-out"
                :class="{'-translate-y-2': hover}"
            >
                <x-icon name="phosphor-headset" class="h-10 w-10 text-primary-600 mb-3" />
                <h3 class="text-lg font-semibold mb-1">24/7 Support</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    Our team is always ready to assist you, anytime, anywhere.
                </p>
            </div>
        </div>
    </div>
</section>
