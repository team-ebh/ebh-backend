<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\BaseViewRecord;
use App\Filament\Resources\Riders\RiderResource;
use Filament\Actions;

class ViewRider extends BaseViewRecord
{
    protected static string $resource = RiderResource::class;

    protected function getCustomHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
