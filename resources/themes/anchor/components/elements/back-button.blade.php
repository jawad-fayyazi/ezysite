<a href="{{ $href ?? '/websites' }}"
    class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 mb-4">
    <x-icon :name="$iconName ?? 'phosphor-arrow-left'" class="h-5 w-5 mr-2" />
    {{ $text ?? 'Back to Websites' }}
</a>