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
    :bodyClass="''"
>
        
        <x-marketing.sections.hero />
        
        <div class=" py-12 border-t sm:py-24 border-zinc-200">
            <x-container>
                {{--<x-marketing.sections.features />--}}
                <x-marketing.sections.new-features />
            </x-container>
        </div>

        <div class=" py-12 border-t sm:py-24 border-zinc-200">
            <x-container>
                <x-marketing.sections.testimonials />
            </x-container>
        </div> 
             
        <div class=" py-12 border-t sm:py-24 border-zinc-200">
            <x-container>
                {{--<x-marketing.sections.pricing />--}}
                <x-marketing.sections.new-pricing />
            </x-container>
        </div>   
        <div class=" py-12 border-t sm:py-24 border-zinc-200">
            <x-container>
                <x-marketing.sections.cta-home />
            </x-container>
        </div>   


</x-layouts.marketing>
