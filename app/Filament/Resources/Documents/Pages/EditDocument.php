<?php

declare(strict_types=1);

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Traits\Filament\FilamentRedirectToListPage;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    use FilamentRedirectToListPage;

    protected static string $resource = DocumentResource::class;
}
