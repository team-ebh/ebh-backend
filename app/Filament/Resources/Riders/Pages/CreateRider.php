<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\Riders\RiderResource;
use App\Traits\Filament\HasCustomCreateActions;
use App\Traits\Filament\HasFilamentNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateRider extends CreateRecord
{
    use HasCustomCreateActions;
    use HasFilamentNotifications;

    protected static string $resource = RiderResource::class;
}
