<?php

namespace App\Filament\Widgets;

use App\Models\SearchRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSearches extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return __('crm.widgets.latest_searches');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SearchRequest::query()->latest()
            )
            ->columns([
                TextColumn::make('user.first_name')
                    ->label(__('crm.fields.user')),
                TextColumn::make('status')
                    ->label(__('crm.fields.status'))
                    ->badge(),
                TextColumn::make('region.name_uz')
                    ->label(__('crm.fields.region')),
                TextColumn::make('price')
                    ->label(__('crm.fields.price'))
                    ->state(fn ($record) => $record->priceLabel()),
                TextColumn::make('mode')
                    ->label(__('crm.fields.mode'))
                    ->badge(),
                TextColumn::make('agreed_count')
                    ->label(__('crm.fields.agreed'))
                    ->badge()
                    ->color('success'),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->since(),
            ]);
    }
}
