<?php

namespace App\Filament\Resources\Sources\Schemas;

use App\Enums\SourceType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->label(__('crm.fields.slug'))
                    ->required(),
                TextInput::make('name')
                    ->label(__('crm.fields.source_name'))
                    ->required(),
                Select::make('type')
                    ->label(__('crm.fields.type'))
                    ->options(SourceType::class)
                    ->default('marketplace')
                    ->required(),
                TextInput::make('base_url')
                    ->label(__('crm.fields.base_url'))
                    ->url(),
                TextInput::make('logo')
                    ->label(__('crm.fields.logo')),
                Toggle::make('is_active')
                    ->label(__('crm.fields.active'))
                    ->required(),
                Textarea::make('config')
                    ->label(__('crm.fields.settings'))
                    ->columnSpanFull(),
                DateTimePicker::make('last_parsed_at')
                    ->label(__('crm.fields.last_update')),
            ]);
    }
}
