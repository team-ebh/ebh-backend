<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers\PaymentLogsRelationManager;
use App\Filament\Resources\TripResource\Tables\TripsTable;
use App\Models\Trip;
use App\Traits\Filament\TranslatableResourceLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TripResource extends Resource
{
    use TranslatableResourceLabels;

    protected static ?string $model = Trip::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMap;

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.order_management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return TripsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PaymentLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'view' => Pages\ViewTrip::route('/{record}'),
        ];
    }
}
