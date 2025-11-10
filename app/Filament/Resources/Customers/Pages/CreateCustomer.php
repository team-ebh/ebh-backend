<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Traits\Filament\FilamentRedirectToListPage;
use App\Traits\Filament\HasCustomCreateActions;
use App\Traits\Filament\HasFilamentNotifications;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    use FilamentRedirectToListPage;
    use HasCustomCreateActions;
    use HasFilamentNotifications;

    protected static string $resource = CustomerResource::class;
}
