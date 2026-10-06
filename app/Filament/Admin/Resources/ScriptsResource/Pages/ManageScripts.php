<?php

namespace App\Filament\Admin\Resources\ScriptsResource\Pages;

use App\Filament\Admin\Resources\ScriptsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

final class ManageScripts extends ManageRecords
{
    protected static string $resource = ScriptsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
