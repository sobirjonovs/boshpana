<?php

namespace App\Filament\Resources\SearchRequests\Pages;

use App\Filament\Resources\SearchRequests\SearchRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSearchRequest extends EditRecord
{
    protected static string $resource = SearchRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
