<?php

namespace App\Filament\Resources\Listings\Schemas;

use App\Enums\Condition;
use App\Enums\Gender;
use App\Enums\ListingStatus;
use App\Enums\MaritalStatus;
use App\Enums\SearchMode;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('source_id')
                    ->relationship('source', 'name')
                    ->label(__('crm.fields.source'))
                    ->searchable()
                    ->required(),
                Select::make('listing_owner_id')
                    ->relationship('owner', 'name')
                    ->label(__('crm.fields.listing_owner'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record?->name ?: $record?->telegram_username ?: $record?->phone ?: ('#'.$record?->id))
                    ->searchable(),
                TextInput::make('external_id')
                    ->label(__('crm.fields.external_id')),
                TextInput::make('url')
                    ->label(__('crm.fields.link'))
                    ->url(),
                TextInput::make('source_ref')
                    ->label(__('crm.fields.source_link'))
                    ->disabled(),
                TextInput::make('title')
                    ->label(__('crm.fields.title')),
                Textarea::make('description')
                    ->label(__('crm.fields.description'))
                    ->columnSpanFull(),
                Textarea::make('images')
                    ->label(__('crm.fields.images'))
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label(__('crm.fields.price_usd'))
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('currency')
                    ->label(__('crm.fields.currency'))
                    ->required()
                    ->default('USD'),
                Select::make('region_id')
                    ->relationship('region', 'name_uz')
                    ->label(__('crm.fields.region'))
                    ->searchable(),
                Select::make('district_id')
                    ->relationship('district', 'name_uz')
                    ->label(__('crm.fields.district'))
                    ->searchable(),
                TextInput::make('address')
                    ->label(__('crm.fields.address')),
                Toggle::make('near_metro')
                    ->label(__('crm.fields.near_metro')),
                TextInput::make('metro_station')
                    ->label(__('crm.fields.metro_station')),
                TextInput::make('lat')
                    ->label(__('crm.fields.latitude'))
                    ->numeric()
                    ->disabled(),
                TextInput::make('lng')
                    ->label(__('crm.fields.longitude'))
                    ->numeric()
                    ->disabled(),
                TextInput::make('rooms')
                    ->label(__('crm.fields.rooms'))
                    ->numeric(),
                TextInput::make('area')
                    ->label(__('crm.fields.area_m2'))
                    ->numeric(),
                TextInput::make('floor')
                    ->label(__('crm.fields.floor'))
                    ->numeric(),
                TextInput::make('total_floors')
                    ->label(__('crm.fields.total_floors'))
                    ->numeric(),
                Select::make('condition')
                    ->label(__('crm.fields.condition'))
                    ->options(Condition::class),
                Toggle::make('has_furniture')
                    ->label(__('crm.fields.furniture')),
                Toggle::make('has_commission')
                    ->label(__('crm.fields.commission')),
                Textarea::make('amenities')
                    ->label(__('crm.fields.amenities'))
                    ->columnSpanFull(),
                Select::make('gender_pref')
                    ->label(__('crm.fields.gender'))
                    ->options(Gender::class),
                Select::make('marital_pref')
                    ->label(__('crm.fields.marital_status'))
                    ->options(MaritalStatus::class),
                Select::make('mode')
                    ->label(__('crm.fields.mode'))
                    ->options(SearchMode::class),
                TextInput::make('partners_needed')
                    ->label(__('crm.fields.partners_count'))
                    ->numeric(),
                Textarea::make('contact')
                    ->label(__('crm.fields.contact'))
                    ->columnSpanFull(),
                DateTimePicker::make('posted_at')
                    ->label(__('crm.fields.posted_at')),
                Select::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(ListingStatus::class)
                    ->default('active')
                    ->required(),
                Toggle::make('ai_analyzed')
                    ->label(__('crm.fields.ai_analyzed'))
                    ->disabled()
                    ->required(),
                Textarea::make('ai_summary')
                    ->label(__('crm.fields.ai_summary'))
                    ->disabled()
                    ->columnSpanFull(),
                Textarea::make('ai_attributes')
                    ->label(__('crm.fields.ai_attributes'))
                    ->disabled()
                    ->columnSpanFull(),
                TextInput::make('ai_confidence')
                    ->label(__('crm.fields.ai_confidence'))
                    ->numeric()
                    ->disabled(),
                DateTimePicker::make('analyzed_at')
                    ->label(__('crm.fields.analyzed_at'))
                    ->disabled(),
            ]);
    }
}
