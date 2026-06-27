<?php

namespace App\Filament\Widgets;

use App\Enums\MatchStatus;
use App\Enums\SearchStatus;
use App\Models\Listing;
use App\Models\SearchMatch;
use App\Models\SearchRequest;
use App\Models\TelegramUser;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Boshpana.ai';

    protected function getStats(): array
    {
        $activeSearches = SearchRequest::query()
            ->whereIn('status', [
                SearchStatus::Queued->value,
                SearchStatus::Searching->value,
            ])
            ->count();

        $agreed = SearchMatch::query()
            ->where('status', MatchStatus::Agreed->value)
            ->count();

        return [
            Stat::make(__('crm.widgets.users'), (string) TelegramUser::query()->count())
                ->description(__('crm.widgets.users_desc'))
                ->color('primary'),
            Stat::make(__('crm.widgets.listings'), (string) Listing::query()->count())
                ->description(__('crm.widgets.listings_desc'))
                ->color('info'),
            Stat::make(__('crm.widgets.active_searches'), (string) $activeSearches)
                ->description(__('crm.widgets.active_searches_desc'))
                ->color('warning'),
            Stat::make(__('crm.widgets.agreed'), (string) $agreed)
                ->description(__('crm.widgets.agreed_desc'))
                ->color('success'),
        ];
    }
}
