<?php

namespace App\Filament\Resources\Listings\Tables;

use App\Enums\Condition;
use App\Enums\ListingStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('posted_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('crm.fields.title'))
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->title),
                TextColumn::make('price')
                    ->label(__('crm.fields.price'))
                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((int) $state) : '—')
                    ->sortable(),
                TextColumn::make('rooms')
                    ->label(__('crm.fields.room'))
                    ->sortable(),
                TextColumn::make('area')
                    ->label(__('crm.fields.area'))
                    ->formatStateUsing(fn ($state) => $state ? $state.' m²' : '—')
                    ->sortable(),
                TextColumn::make('region.name_uz')
                    ->label(__('crm.fields.region'))
                    ->sortable(),
                TextColumn::make('district.name_uz')
                    ->label(__('crm.fields.district')),
                TextColumn::make('condition')
                    ->label(__('crm.fields.condition'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('crm.fields.status'))
                    ->badge(),
                TextColumn::make('source.name')
                    ->label(__('crm.fields.source'))
                    ->badge()
                    ->color('info'),
                IconColumn::make('near_metro')
                    ->label(__('crm.fields.metro'))
                    ->boolean(),
                IconColumn::make('ai_analyzed')
                    ->label(__('crm.fields.ai'))
                    ->boolean(),
                TextColumn::make('posted_at')
                    ->label(__('crm.fields.listed_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('region_id')
                    ->label(__('crm.fields.region'))
                    ->relationship('region', 'name_uz'),
                SelectFilter::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(ListingStatus::class),
                SelectFilter::make('condition')
                    ->label(__('crm.fields.condition'))
                    ->options(Condition::class),
                SelectFilter::make('source_id')
                    ->label(__('crm.fields.source'))
                    ->relationship('source', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
