<?php

namespace App\Filament\Resources\CrmLeads\Tables;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmLeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('crm.fields.name'))
                    ->searchable(),
                TextColumn::make('company')
                    ->label(__('crm.fields.company'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('crm.fields.type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('crm.fields.status'))
                    ->badge(),
                TextColumn::make('phone')
                    ->label(__('crm.fields.phone'))
                    ->searchable(),
                TextColumn::make('telegram')
                    ->label(__('crm.fields.telegram'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('crm.fields.type'))
                    ->options(LeadType::class),
                SelectFilter::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(LeadStatus::class),
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
