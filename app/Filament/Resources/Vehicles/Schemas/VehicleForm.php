<?php

declare(strict_types=1);

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Models\Rider;
use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(trans('vehicles.admin.sections.basic_info.title'))
                    ->description(trans('vehicles.admin.sections.basic_info.description'))
                    ->schema([
                        Select::make(Vehicle::COLUMN_RIDER_ID)
                            ->label(trans('vehicles.admin.fields.rider'))
                            ->relationship('rider', Rider::COLUMN_FULL_NAME)
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make(Vehicle::COLUMN_PLATE_NUMBER)
                            ->label(trans('vehicles.admin.fields.plate_number'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make(Vehicle::COLUMN_YEAR)
                            ->label(trans('vehicles.admin.fields.year'))
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y') + 1)
                            ->required(),
                    ])
                    ->columns(3),

                Section::make(trans('vehicles.admin.sections.vehicle_details.title'))
                    ->description(trans('vehicles.admin.sections.vehicle_details.description'))
                    ->schema([
                        Select::make(Vehicle::COLUMN_CAR_TYPE_ID)
                            ->label(trans('vehicles.admin.fields.car_type'))
                            ->options(function () {
                                return VehicleSetting::getByType(VehicleSetting::TYPE_CAR_TYPES)
                                    ->pluck(VehicleSetting::COLUMN_NAME, 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        Select::make(Vehicle::COLUMN_CAR_COLOR_ID)
                            ->label(trans('vehicles.admin.fields.car_color'))
                            ->options(function () {
                                return VehicleSetting::getByType(VehicleSetting::TYPE_CAR_COLORS)
                                    ->pluck(VehicleSetting::COLUMN_NAME, 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        Select::make(Vehicle::COLUMN_CAR_MAKE_ID)
                            ->label(trans('vehicles.admin.fields.car_make'))
                            ->options(function () {
                                return VehicleSetting::getByType(VehicleSetting::TYPE_CAR_MAKES)
                                    ->pluck(VehicleSetting::COLUMN_NAME, 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        Select::make(Vehicle::COLUMN_CAR_MODEL_ID)
                            ->label(trans('vehicles.admin.fields.car_model'))
                            ->options(function () {
                                return VehicleSetting::getByType(VehicleSetting::TYPE_CAR_MODELS)
                                    ->pluck(VehicleSetting::COLUMN_NAME, 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        Select::make(Vehicle::COLUMN_PASSENGER_CAPACITY_ID)
                            ->label(trans('vehicles.admin.fields.passenger_capacity'))
                            ->options(function () {
                                return VehicleSetting::getByType(VehicleSetting::TYPE_PASSENGER_CAPACITY)
                                    ->pluck(VehicleSetting::COLUMN_CAPACITY, 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        Select::make(Vehicle::COLUMN_VEHICLE_TYPE_ID)
                            ->label(trans('vehicles.admin.fields.vehicle_type'))
                            ->options(function () {
                                return VehicleSetting::getByType(VehicleSetting::TYPE_VEHICLE_TYPES)
                                    ->pluck(VehicleSetting::COLUMN_NAME, 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make(trans('vehicles.admin.sections.accessibility.title'))
                    ->description(trans('vehicles.admin.sections.accessibility.description'))
                    ->schema([
                        CheckboxList::make('accessibility_feature_ids')
                            ->label(trans('vehicles.admin.fields.accessibility_features'))
                            ->options(AccessibilityRequirementsEnum::class)
                            ->columns(2)
                            ->nullable()
                            ->afterStateHydrated(function (CheckboxList $component, $state, ?Vehicle $record) {
                                if ($record) {
                                    $component->state($record->accessibility_feature_ids);
                                }
                            })
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
