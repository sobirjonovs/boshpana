<?php

namespace App\Filament\Resources\TelegramUsers;

use App\Filament\Resources\TelegramUsers\Pages\CreateTelegramUser;
use App\Filament\Resources\TelegramUsers\Pages\EditTelegramUser;
use App\Filament\Resources\TelegramUsers\Pages\ListTelegramUsers;
use App\Filament\Resources\TelegramUsers\Schemas\TelegramUserForm;
use App\Filament\Resources\TelegramUsers\Tables\TelegramUsersTable;
use App\Models\TelegramUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TelegramUserResource extends Resource
{
    protected static ?string $model = TelegramUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.foydalanuvchilar');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.models.telegram_users');
    }

    public static function getModelLabel(): string
    {
        return __('crm.models.telegram_user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.models.telegram_users');
    }

    public static function form(Schema $schema): Schema
    {
        return TelegramUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TelegramUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTelegramUsers::route('/'),
            'create' => CreateTelegramUser::route('/create'),
            'edit' => EditTelegramUser::route('/{record}/edit'),
        ];
    }
}
