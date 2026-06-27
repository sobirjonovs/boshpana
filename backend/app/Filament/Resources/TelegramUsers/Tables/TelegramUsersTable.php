<?php

namespace App\Filament\Resources\TelegramUsers\Tables;

use App\Enums\Language;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TelegramUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('telegram_id')
                    ->label(__('crm.fields.telegram_id'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label(__('crm.fields.name'))
                    ->searchable(),
                TextColumn::make('username')
                    ->label(__('crm.fields.username'))
                    ->searchable(),
                TextColumn::make('language')
                    ->label(__('crm.fields.language'))
                    ->badge(),
                IconColumn::make('is_premium')
                    ->label(__('crm.fields.premium'))
                    ->boolean(),
                TextColumn::make('free_searches_left')
                    ->label(__('crm.fields.free_search'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.joined_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('language')
                    ->label(__('crm.fields.language'))
                    ->options(Language::class),
                TernaryFilter::make('is_premium')
                    ->label(__('crm.fields.premium')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
