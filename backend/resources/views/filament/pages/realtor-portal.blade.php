<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center gap-4 py-16 text-center">
        <div class="text-6xl">🏠</div>

        <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
            {{ __('crm.pages.realtor_portal_heading') }}
        </h2>

        <p class="max-w-xl text-gray-500 dark:text-gray-400">
            {{ __('crm.pages.realtor_portal_body') }}
        </p>

        <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-600 dark:bg-primary-400/10 dark:text-primary-400">
            ⏳ {{ __('crm.pages.realtor_portal_badge') }}
        </span>
    </div>
</x-filament-panels::page>
