<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Tables;

use App\Models\Rider;
use App\Models\Vehicle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Vehicle::COLUMN_RIDER_ID)
                    ->label(trans('vehicles.admin.fields.rider'))
                    ->formatStateUsing(fn (Vehicle $record): string => $record->rider?->{Rider::COLUMN_FULL_NAME} ?? '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make(Vehicle::COLUMN_PLATE_NUMBER)
                    ->label(trans('vehicles.admin.fields.plate_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make(Vehicle::COLUMN_YEAR)
                    ->label(trans('vehicles.admin.fields.year'))
                    ->sortable(),

                TextColumn::make('carMake.name')
                    ->label(trans('vehicles.admin.fields.car_make'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('carModel.name')
                    ->label(trans('vehicles.admin.fields.car_model'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('carType.name')
                    ->label(trans('vehicles.admin.fields.car_type'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('carColor.name')
                    ->label(trans('vehicles.admin.fields.car_color'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vehicleType.name')
                    ->label(trans('vehicles.admin.fields.vehicle_type'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('passengerCapacity.capacity')
                    ->label(trans('vehicles.admin.fields.passenger_capacity'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make(Vehicle::COLUMN_RIDER_ID)
                    ->label(trans('vehicles.admin.fields.rider'))
                    ->relationship('rider', Rider::COLUMN_FULL_NAME)
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
