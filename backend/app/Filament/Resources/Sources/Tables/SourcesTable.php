<?php

namespace App\Filament\Resources\Sources\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label(__('crm.fields.slug'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('crm.fields.source_name'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('crm.fields.type'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('base_url')
                    ->label(__('crm.fields.base_url'))
                    ->searchable(),
                TextColumn::make('logo')
                    ->label(__('crm.fields.logo'))
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label(__('crm.fields.active'))
                    ->boolean(),
                TextColumn::make('last_parsed_at')
                    ->label(__('crm.fields.last_update'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('crm.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
