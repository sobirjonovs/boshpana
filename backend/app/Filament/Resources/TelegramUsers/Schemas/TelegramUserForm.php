<?php

namespace App\Filament\Resources\TelegramUsers\Schemas;

use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\MaritalStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TelegramUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('telegram_id')
                    ->label(__('crm.fields.telegram_id'))
                    ->tel()
                    ->required()
                    ->numeric(),
                TextInput::make('username')
                    ->label(__('crm.fields.username')),
                TextInput::make('first_name')
                    ->label(__('crm.fields.name')),
                TextInput::make('last_name')
                    ->label(__('crm.fields.last_name')),
                Select::make('language')
                    ->label(__('crm.fields.language'))
                    ->options(Language::class)
                    ->default('uz')
                    ->required(),
                TextInput::make('phone')
                    ->label(__('crm.fields.phone'))
                    ->tel(),
                Select::make('gender')
                    ->label(__('crm.fields.gender'))
                    ->options(Gender::class),
                Select::make('marital_status')
                    ->label(__('crm.fields.marital_status'))
                    ->options(MaritalStatus::class),
                Toggle::make('is_premium')
                    ->label(__('crm.fields.premium'))
                    ->required(),
                Toggle::make('is_blocked')
                    ->label(__('crm.fields.blocked'))
                    ->required(),
                TextInput::make('balance')
                    ->label(__('crm.fields.balance'))
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('free_searches_left')
                    ->label(__('crm.fields.free_search'))
                    ->required()
                    ->numeric()
                    ->default(3),
                DateTimePicker::make('premium_until')
                    ->label(__('crm.fields.premium_until')),
                DateTimePicker::make('last_seen_at')
                    ->label(__('crm.fields.last_activity')),
            ]);
    }
}
