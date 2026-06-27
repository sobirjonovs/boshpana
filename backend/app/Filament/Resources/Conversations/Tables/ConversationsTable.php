<?php

namespace App\Filament\Resources\Conversations\Tables;

use App\Enums\ConversationStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('listing.title')
                    ->label(__('crm.fields.listing'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('status')
                    ->label(__('crm.fields.status'))
                    ->badge(),
                TextColumn::make('outcome')
                    ->label(__('crm.fields.result'))
                    ->badge()
                    ->placeholder('—'),
                IconColumn::make('is_simulation')
                    ->label(__('crm.fields.simulation'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(ConversationStatus::class),
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
