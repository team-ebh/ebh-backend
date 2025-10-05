<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use App\Traits\Filament\HasCustomCreateActions;
use App\Traits\Filament\HasFilamentNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmin extends CreateRecord
{
    use HasCustomCreateActions;
    use HasFilamentNotifications;

    protected static string $resource = AdminResource::class;
}
