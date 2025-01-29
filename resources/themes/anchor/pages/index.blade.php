<?php
    use function Laravel\Folio\{name};
    name('home');
?>

<x-layouts.marketing
    :seo="[
        'title'         => setting('site.title', 'Laravel Wave'),
        'description'   => setting('site.description', 'Software as a Service Starter Kit'),
        'image'         => url('/og_image.png'),
        'type'          => 'website'
    ]"
    :bodyClass="'gradient-bg'"
>
        
        <x-marketing.sections.hero />
        
        <div class="gradient-bg py-12 border-t sm:py-24 border-zinc-200">
            <x-container>
                <x-marketing.sections.features />
            </x-container>
        </div>

        <div class="gradient-bg py-12 border-t sm:py-24 border-zinc-200">
            <x-container>
                <x-marketing.sections.testimonials />
            </x-container>
        </div> 
             
        <div class="gradient-bg py-12 border-t sm:py-24 border-zinc-200">
            <x-container>
                <x-marketing.sections.pricing />
            </x-container>
        </div>   


</x-layouts.marketing>
