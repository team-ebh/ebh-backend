<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\RelationManagers;

use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\Riders\Tables\RidersTable;
use App\Models\Rider;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class RidersRelationManager extends RelationManager
{
    protected static string $relationship = 'riders';

    protected static ?string $recordTitleAttribute = Rider::COLUMN_FULL_NAME;

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $pageClass === ViewCompany::class;
    }

    public function table(Table $table): Table
    {
        return RidersTable::configure($table);
    }
}
