<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('telegram_user_id')
                    ->relationship('user', 'first_name')
                    ->label(__('crm.fields.user'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->first_name ?: $record?->username ?: ('#'.$record?->telegram_id))
                    ->searchable()
                    ->required(),
                Select::make('plan_id')
                    ->relationship('plan', 'name_uz')
                    ->label(__('crm.fields.plan'))
                    ->searchable(),
                TextInput::make('amount')
                    ->label(__('crm.fields.amount'))
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->label(__('crm.fields.currency'))
                    ->required()
                    ->default('UZS'),
                Select::make('provider')
                    ->label(__('crm.fields.payment_system'))
                    ->options(PaymentProvider::class)
                    ->default('manual')
                    ->required(),
                Select::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(PaymentStatus::class)
                    ->default('pending')
                    ->required(),
                TextInput::make('external_id')
                    ->label(__('crm.fields.external_id')),
                TextInput::make('description')
                    ->label(__('crm.fields.description')),
                Textarea::make('meta')
                    ->label(__('crm.fields.meta'))
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at')
                    ->label(__('crm.fields.paid_at')),
            ]);
    }
}
