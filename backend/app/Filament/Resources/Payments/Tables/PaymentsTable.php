<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.first_name')
                    ->label(__('crm.fields.user'))
                    ->searchable(),
                TextColumn::make('amount')
                    ->label(__('crm.fields.amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('currency')
                    ->label(__('crm.fields.currency')),
                TextColumn::make('provider')
                    ->label(__('crm.fields.payment_system'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('crm.fields.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(PaymentStatus::class),
                SelectFilter::make('provider')
                    ->label(__('crm.fields.payment_system'))
                    ->options(PaymentProvider::class),
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
