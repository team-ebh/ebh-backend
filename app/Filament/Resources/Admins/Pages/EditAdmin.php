<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use App\Traits\Filament\HasFilamentNotifications;
use Filament\Resources\Pages\EditRecord;

class EditAdmin extends EditRecord
{
    use HasFilamentNotifications;

    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
