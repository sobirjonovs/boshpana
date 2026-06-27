<?php

namespace App\Filament\Resources\CrmLeads\Schemas;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CrmLeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('crm.fields.name'))
                    ->required(),
                TextInput::make('company')
                    ->label(__('crm.fields.company')),
                TextInput::make('phone')
                    ->label(__('crm.fields.phone'))
                    ->tel(),
                TextInput::make('telegram')
                    ->label(__('crm.fields.telegram'))
                    ->tel(),
                TextInput::make('email')
                    ->label(__('crm.fields.email'))
                    ->email(),
                Select::make('type')
                    ->label(__('crm.fields.type'))
                    ->options(LeadType::class)
                    ->default('owner')
                    ->required(),
                Select::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(LeadStatus::class)
                    ->default('new')
                    ->required(),
                TextInput::make('source')
                    ->label(__('crm.fields.source')),
                TextInput::make('potential_value')
                    ->label(__('crm.fields.potential_value'))
                    ->numeric(),
                TextInput::make('assigned_to')
                    ->label(__('crm.fields.responsible'))
                    ->numeric(),
                Select::make('listing_owner_id')
                    ->relationship('listingOwner', 'name')
                    ->label(__('crm.fields.listing_owner'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->name ?: $record?->telegram_username ?: $record?->phone ?: ('#'.$record?->id))
                    ->searchable(),
                Textarea::make('notes')
                    ->label(__('crm.fields.notes'))
                    ->columnSpanFull(),
                DateTimePicker::make('last_contacted_at')
                    ->label(__('crm.fields.last_contact')),
            ]);
    }
}
