<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders;

use App\Filament\Resources\Riders\Infolists\RiderInfolist;
use App\Filament\Resources\Riders\Pages\CreateRider;
use App\Filament\Resources\Riders\Pages\EditRider;
use App\Filament\Resources\Riders\Pages\ListRiders;
use App\Filament\Resources\Riders\Pages\ViewRider;
use App\Filament\Resources\Riders\Schemas\RiderForm;
use App\Filament\Resources\Riders\Tables\RidersTable;
use App\Models\Rider;
use App\Traits\Filament\TranslatableResourceLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RiderResource extends Resource
{
    use TranslatableResourceLabels;

    protected static ?string $model = Rider::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUser;

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.fleet_management');
    }

    public static function form(Schema $schema): Schema
    {
        return RiderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RidersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RiderInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiders::route('/'),
            'create' => CreateRider::route('/create'),
            //            'view' => ViewRider::route('/{record}'),
            'edit' => EditRider::route('/{record}/edit'),
        ];
    }
}
