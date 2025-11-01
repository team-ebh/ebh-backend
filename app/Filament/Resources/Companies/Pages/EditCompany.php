<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Traits\Filament\FilamentRedirectToListPage;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    use FilamentRedirectToListPage;

    protected static string $resource = CompanyResource::class;
}
