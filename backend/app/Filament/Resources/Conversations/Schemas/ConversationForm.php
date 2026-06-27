<?php

namespace App\Filament\Resources\Conversations\Schemas;

use App\Enums\ConversationStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('search_request_id')
                    ->relationship('searchRequest', 'id')
                    ->label(__('crm.fields.search'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => '#'.$record?->id.($record?->user?->first_name ? ' — '.$record->user->first_name : ''))
                    ->searchable()
                    ->required(),
                Select::make('listing_id')
                    ->relationship('listing', 'title')
                    ->label(__('crm.fields.listing'))
                    ->searchable()
                    ->required(),
                Select::make('listing_owner_id')
                    ->relationship('owner', 'name')
                    ->label(__('crm.fields.listing_owner'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->name ?: $record?->telegram_username ?: $record?->phone ?: ('#'.$record?->id))
                    ->searchable(),
                Select::make('telegram_account_id')
                    ->relationship('account', 'label')
                    ->label(__('crm.fields.telegram_account'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->label ?: $record?->username ?: $record?->phone ?: ('#'.$record?->id))
                    ->searchable(),
                TextInput::make('channel')
                    ->label(__('crm.fields.channel'))
                    ->required()
                    ->default('telegram'),
                Select::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(ConversationStatus::class)
                    ->default('pending')
                    ->required(),
                Toggle::make('is_simulation')
                    ->label(__('crm.fields.simulation'))
                    ->required(),
                TextInput::make('outcome')
                    ->label(__('crm.fields.result')),
                Textarea::make('summary')
                    ->label(__('crm.fields.summary'))
                    ->columnSpanFull(),
                DateTimePicker::make('contacted_at')
                    ->label(__('crm.fields.contacted_at')),
                DateTimePicker::make('closed_at')
                    ->label(__('crm.fields.closed_at')),
            ]);
    }
}
