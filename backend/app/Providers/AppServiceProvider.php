<?php

namespace App\Providers;

use App\Services\Ai\AiClient;
use App\Services\Ai\AnthropicClient;
use App\Services\Ai\DeepSeekClient;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AnthropicClient::class, fn () => AnthropicClient::fromConfig());
        $this->app->singleton(DeepSeekClient::class, fn () => DeepSeekClient::fromConfig());

        // The AI services depend on the AiClient contract; resolve it to the
        // provider chosen in config (boshpana.ai.provider) — DeepSeek by default.
        $this->app->singleton(AiClient::class, function ($app) {
            return config('boshpana.ai.provider') === 'claude'
                ? $app->make(AnthropicClient::class)
                : $app->make(DeepSeekClient::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch->locales(['uz', 'ru', 'en'])
                ->labels([
                    'uz' => 'O\'zbekcha',
                    'ru' => 'Русский',
                    'en' => 'English',
                ])
                ->flags([
                    'uz' => 'https://flagcdn.com/uz.svg',
                    'ru' => 'https://flagcdn.com/ru.svg',
                    'en' => 'https://flagcdn.com/gb.svg',
                ])
                // circular() applies w-7 h-7 to the trigger flag — otherwise the
                // SVG renders at its huge intrinsic size.
                ->circular()
                // Both default to false — without this the switcher is invisible.
                ->visible(insidePanels: true, outsidePanels: true);
        });

        // Filament v4 purges the `.w-7` utility (nothing in core uses it), so the
        // language-switch flag images get height-only sizing and render oversized.
        // Inject the missing size rules, scoped to the switch flags.
        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => <<<'HTML'
            <style>
                /* Bulletproof: size every flag image by its source, independent of purged utilities. */
                img[src*="flagcdn"] { width: 1.5rem !important; height: 1.5rem !important; object-fit: cover; }
                .fls-flag-only-width img, .fls-dropdown-width img { width: 1.5rem !important; height: 1.5rem !important; }
                .w-7 { width: 1.75rem; }
            </style>
            HTML,
        );
    }
}
