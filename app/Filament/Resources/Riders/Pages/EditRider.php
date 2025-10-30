<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\Riders\RiderResource;
use Filament\Resources\Pages\EditRecord;

class EditRider extends EditRecord
{
    protected static string $resource = RiderResource::class;
}
