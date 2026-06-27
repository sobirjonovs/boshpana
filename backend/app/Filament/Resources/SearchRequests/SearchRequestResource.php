<?php

namespace App\Filament\Resources\SearchRequests;

use App\Filament\Resources\SearchRequests\Pages\CreateSearchRequest;
use App\Filament\Resources\SearchRequests\Pages\EditSearchRequest;
use App\Filament\Resources\SearchRequests\Pages\ListSearchRequests;
use App\Filament\Resources\SearchRequests\Schemas\SearchRequestForm;
use App\Filament\Resources\SearchRequests\Tables\SearchRequestsTable;
use App\Models\SearchRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SearchRequestResource extends Resource
{
    protected static ?string $model = SearchRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.qidiruv');
    }

    public static function getNavigationLabel(): string
    {
        return __('crm.models.search_requests');
    }

    public static function getModelLabel(): string
    {
        return __('crm.models.search_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crm.models.search_requests');
    }

    public static function form(Schema $schema): Schema
    {
        return SearchRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SearchRequestsTable::configure($table);
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
            'index' => ListSearchRequests::route('/'),
            'create' => CreateSearchRequest::route('/create'),
            'edit' => EditSearchRequest::route('/{record}/edit'),
        ];
    }
}
