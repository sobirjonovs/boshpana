<?php

namespace App\Filament\Resources\SearchRequests\Tables;

use App\Enums\SearchStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SearchRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.first_name')
                    ->label(__('crm.fields.user'))
                    ->searchable(),
                TextColumn::make('user.telegram_id')
                    ->label(__('crm.fields.telegram_id'))
                    ->searchable(),
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
                TextColumn::make('progress')
                    ->label(__('crm.fields.progress'))
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->sortable(),
                TextColumn::make('matched_count')
                    ->label(__('crm.fields.found'))
                    ->sortable(),
                TextColumn::make('agreed_count')
                    ->label(__('crm.fields.agreed'))
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(SearchStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
