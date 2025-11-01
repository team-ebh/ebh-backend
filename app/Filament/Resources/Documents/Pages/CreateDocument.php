<?php

declare(strict_types=1);

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Traits\Filament\FilamentRedirectToListPage;
use App\Traits\Filament\HasCustomCreateActions;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    use FilamentRedirectToListPage;
    use HasCustomCreateActions;

    protected static string $resource = DocumentResource::class;
}
