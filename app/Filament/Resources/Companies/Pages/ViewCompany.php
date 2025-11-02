<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\BaseViewRecord;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Infolists\CompanyInfolist;
use Filament\Actions;
use Filament\Schemas\Schema;

class ViewCompany extends BaseViewRecord
{
    protected static string $resource = CompanyResource::class;

    public function infolist(Schema $schema): Schema
    {
        return CompanyInfolist::configure($schema);
    }

    protected function getCustomHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
