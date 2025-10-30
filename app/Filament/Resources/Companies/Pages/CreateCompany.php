<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Traits\Filament\HasCustomCreateActions;
use App\Traits\Filament\HasFilamentNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    use HasCustomCreateActions;
    use HasFilamentNotifications;

    protected static string $resource = CompanyResource::class;
}
