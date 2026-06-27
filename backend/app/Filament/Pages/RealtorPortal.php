<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class RealtorPortal extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.realtor-portal';

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.pages.realtor_portal_nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('crm.pages.realtor_portal');
    }
}
