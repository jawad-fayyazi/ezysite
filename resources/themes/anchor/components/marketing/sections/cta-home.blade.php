{{-- CTA Section --}}
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div 
            x-data="{ show: false }" 
            x-init="setTimeout(() => show = true, 300)" 
            x-transition.opacity.scale.duration.500ms
            class="bg-gradient-to-r from-primary-600 to-secondary-600 rounded-2xl p-12 text-center text-white shadow-xl"
        >
            <h2 
                x-data="{ animate: false }"
                x-init="setTimeout(() => animate = true, 500)"
                x-transition:enter="transition-transform duration-700 ease-out"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="text-3xl font-bold mb-4 tracking-tight"
            >
                Ready to Build Your Website?
            </h2>
            <p 
                x-data="{ animate: false }"
                x-init="setTimeout(() => animate = true, 700)"
                x-transition:enter="transition-opacity duration-700 ease-out"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="text-xl mb-8"
            >
                Join thousands of creators who trust Ezysite for their web presence.
            </p>
            <a href="{{ route('dashboard') }}" 
                class="inline-flex items-center justify-center px-6 py-3 bg-white text-primary-600 font-semibold rounded-lg hover:bg-gray-100 transition duration-300 ease-in-out shadow-md"
                x-data="{ hover: false }"
                @mouseenter="hover = true" 
                @mouseleave="hover = false"
                x-transition
            >
                Get Started Now
                <x-icon name='phosphor-arrow-right' class="ml-2 h-5 w-5" />
            </a>
        </div>
    </div>
</section>
