{{-- Pricing Section --}}
<section class="py-20 bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold mb-4">Simple, Transparent <span
                class="bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">Pricing</span></h2>
            <p class="text-xl text-gray-600 dark:text-gray-400">
                Choose the perfect plan for your needs
            </p>
        </div>

<div x-data="{ on: false, billing: '{{ get_default_billing_cycle() }}',
            toggleRepositionMarker(toggleButton){
                this.$refs.marker.style.width=toggleButton.offsetWidth + 'px';
                this.$refs.marker.style.height=toggleButton.offsetHeight + 'px';
                this.$refs.marker.style.left=toggleButton.offsetLeft + 'px';
            }
         }" 
        x-init="
                setTimeout(function(){ 
                    toggleRepositionMarker($refs.monthly); 
                    $refs.marker.classList.remove('opacity-0');
                    setTimeout(function(){ 
                        $refs.marker.classList.add('duration-300', 'ease-out');
                    }, 10); 
                }, 1);
        "
        class="w-full max-w-6xl mx-auto mt-12 mb-2 md:my-12" x-cloak>

@if(has_monthly_yearly_toggle())
    <div class="relative flex items-center justify-start pb-5 -translate-y-2 md:justify-center">
        <div class="relative inline-flex items-center justify-center w-auto p-1 text-center -translate-y-3 border-2 rounded-full md:mx-auto border-primary-500">
            <div 
                x-ref="monthly" 
                x-on:click="billing='Monthly'; toggleRepositionMarker($el)" 
                :class="{ 'bg-primary-500 text-white': billing == 'Monthly', 'text-primary-500' : billing != 'Monthly' }" 
                class="relative z-20 px-4 py-1 text-sm font-medium leading-6 rounded-full duration-300 ease-out cursor-pointer">
                Monthly
            </div>
            <div 
                x-ref="yearly" 
                x-on:click="billing='Yearly'; toggleRepositionMarker($el)" 
                :class="{ 'bg-primary-500 text-white': billing == 'Yearly', 'text-primary-500' : billing != 'Yearly' }" 
                class="relative z-20 px-4 py-1 text-sm font-medium leading-6 rounded-full duration-300 ease-out cursor-pointer">
                Yearly
            </div>
        </div>  
    </div>
@endif




        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach(Wave\Plan::where('active', 1)->get() as $plan)
                @php $features = explode(',', $plan->features); @endphp
                <div 
                x-show="(billing == 'Monthly' && '{{ $plan->monthly_price_id }}' != '') || (billing == 'Yearly' && '{{ $plan->yearly_price_id }}' != '')"
                x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
                    class="relative p-6 bg-white dark:bg-gray-900 rounded-lg shadow-lg transition-transform duration-300
                    {{ $plan->default ? 'border-2 border-primary-500' : ''}}" 
                    :class="hover ? 'transform -translate-y-2' : ''">
                    @if($plan->default)
                        <span
                            class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-primary-500 text-white text-sm rounded-full">
                            Recommended
                        </span>
                    @endif

                    <h3 class="text-xl font-bold mb-2">{{ $plan->name }}</h3>
                    <div class="mb-6">
                        <span class="text-4xl font-bold">$<span x-text="billing == 'Monthly' ? '{{ $plan->monthly_price }}' : '{{ $plan->yearly_price }}'"></span></span>
                            <span class="text-gray-600 dark:text-gray-400"><span x-text="billing == 'Monthly' ? '/mo' : '/yr'"></span></span>
                    </div>

                    <ul class="space-y-3 mb-6">
                        @foreach($features as $feature)
                            <li class="flex items-center text-gray-600 dark:text-gray-400">
                                <svg class="h-5 w-5 text-primary-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    <a href="/settings/subscription"
                        class="block text-center w-full py-2 rounded-lg font-semibold transition 
                            {{ $plan->default ? 'bg-primary-500 text-white hover:bg-primary-600' : 'border-2 border-primary-500 text-primary-500 hover:bg-primary-500 hover:text-white' }}">
                        Get Started
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
</section>