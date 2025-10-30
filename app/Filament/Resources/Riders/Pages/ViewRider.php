<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\Riders\RiderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRider extends ViewRecord
{
    protected static string $resource = RiderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
