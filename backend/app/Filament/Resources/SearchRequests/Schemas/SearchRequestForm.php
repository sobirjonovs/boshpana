<?php

namespace App\Filament\Resources\SearchRequests\Schemas;

use App\Enums\Condition;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\SearchMode;
use App\Enums\SearchStatus;
use App\Enums\TriState;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SearchRequestForm
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
                Select::make('region_id')
                    ->relationship('region', 'name_uz')
                    ->label(__('crm.fields.region'))
                    ->searchable(),
                Select::make('district_id')
                    ->relationship('district', 'name_uz')
                    ->label(__('crm.fields.district'))
                    ->searchable(),
                TextInput::make('price_min')
                    ->label(__('crm.fields.min_price'))
                    ->numeric(),
                TextInput::make('price_max')
                    ->label(__('crm.fields.max_price'))
                    ->numeric(),
                TextInput::make('currency')
                    ->label(__('crm.fields.currency'))
                    ->required()
                    ->default('USD'),
                Textarea::make('rooms')
                    ->label(__('crm.fields.rooms'))
                    ->columnSpanFull(),
                Select::make('condition')
                    ->label(__('crm.fields.condition'))
                    ->options(Condition::class),
                Select::make('has_furniture')
                    ->label(__('crm.fields.furniture'))
                    ->options(TriState::class),
                Select::make('has_commission')
                    ->label(__('crm.fields.commission'))
                    ->options(TriState::class),
                TextInput::make('area_min')
                    ->label(__('crm.fields.min_area'))
                    ->numeric(),
                TextInput::make('area_max')
                    ->label(__('crm.fields.max_area'))
                    ->numeric(),
                Select::make('mode')
                    ->label(__('crm.fields.mode'))
                    ->options(SearchMode::class)
                    ->default('solo')
                    ->required(),
                TextInput::make('partners_count')
                    ->label(__('crm.fields.partners_count'))
                    ->numeric(),
                Select::make('near_metro')
                    ->label(__('crm.fields.near_metro'))
                    ->options(TriState::class),
                Select::make('gender')
                    ->label(__('crm.fields.gender'))
                    ->options(Gender::class),
                Select::make('marital_status')
                    ->label(__('crm.fields.marital_status'))
                    ->options(MaritalStatus::class),
                Textarea::make('free_text')
                    ->label(__('crm.fields.free_text'))
                    ->columnSpanFull(),
                Select::make('status')
                    ->label(__('crm.fields.status'))
                    ->options(SearchStatus::class)
                    ->default('draft')
                    ->required(),
                Toggle::make('is_simulation')
                    ->label(__('crm.fields.simulation'))
                    ->required(),
                TextInput::make('current_step')
                    ->label(__('crm.fields.current_step'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('progress')
                    ->label(__('crm.fields.progress'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('scanned_count')
                    ->label(__('crm.fields.scanner'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('matched_count')
                    ->label(__('crm.fields.matched'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('contacted_count')
                    ->label(__('crm.fields.contacted'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('agreed_count')
                    ->label(__('crm.fields.agreed'))
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('started_at')
                    ->label(__('crm.fields.started_at')),
                DateTimePicker::make('completed_at')
                    ->label(__('crm.fields.finished_at')),
                DateTimePicker::make('last_progress_at')
                    ->label(__('crm.fields.last_update')),
            ]);
    }
}
