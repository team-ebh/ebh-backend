<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Sections;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Riders\RiderResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;

class ParticipantsSection
{
    public static function make(): Section
    {
        return Section::make(trans('trips.admin.sections.participants'))
            ->description(trans('trips.admin.sections.participants_description'))
            ->schema([
                Grid::make(1)
                    ->schema([
                        // Customer Information
                        Section::make(trans('trips.admin.sections.customer_information'))
                            ->icon('heroicon-o-user-circle')
                            ->headerActions([
                                Action::make('viewCustomer')
                                    ->label(trans('trips.admin.actions.view'))
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->color('primary')
                                    ->url(fn ($record) => $record->customer
                                        ? CustomerResource::getUrl('view', ['record' => $record->customer->id])
                                        : null, shouldOpenInNewTab: true)
                                    ->visible(fn ($record) => (bool) $record->customer),
                            ])
                            ->schema([
                                TextEntry::make('customer.full_name')
                                    ->label(trans('trips.admin.fields.full_name'))
                                    ->formatStateUsing(fn ($record) => $record->customer
                                        ? "{$record->customer->first_name} {$record->customer->last_name}"
                                        : trans('trips.admin.fields.na'))
                                    ->icon('heroicon-o-user')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('customer.phone_number')
                                    ->label(trans('trips.admin.fields.phone'))
                                    ->prefix(defaultPrefixPhoneNumber())
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),

                                TextEntry::make('customer.email')
                                    ->label(trans('trips.admin.fields.email'))
                                    ->formatStateUsing(fn ($record) => $record->customer?->email ?: trans('trips.admin.fields.na'))
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                            ])
                            ->columns(3)
                            ->compact()
                            ->visible(fn ($record) => (bool) $record->customer),

                        // Rider Information
                        Section::make(trans('trips.admin.sections.rider_information'))
                            ->icon('heroicon-o-truck')
                            ->headerActions([
                                Action::make('viewRider')
                                    ->label(trans('trips.admin.actions.view'))
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->color('success')
                                    ->url(fn ($record) => $record->rider
                                        ? RiderResource::getUrl('view', ['record' => $record->rider->id])
                                        : null, shouldOpenInNewTab: true)
                                    ->visible(fn ($record) => (bool) $record->rider),
                            ])
                            ->schema([
                                TextEntry::make('rider.full_name')
                                    ->label(trans('trips.admin.fields.full_name'))
                                    ->formatStateUsing(fn ($record) => $record->rider?->full_name ?: trans('trips.admin.fields.not_assigned'))
                                    ->icon('heroicon-o-user')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('rider.phone_number')
                                    ->label(trans('trips.admin.fields.phone'))
                                    ->formatStateUsing(function ($record) {
                                        if (! $record->rider || ! $record->rider->phone_number) {
                                            return trans('trips.admin.fields.na');
                                        }

                                        $phone = $record->rider->phone_number;
                                        if (! str_starts_with($phone, '+')) {
                                            $phone = defaultPrefixPhoneNumber() . $phone;
                                        }

                                        return $phone;
                                    })
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),

                                TextEntry::make('rider.email')
                                    ->label(trans('trips.admin.fields.email'))
                                    ->formatStateUsing(fn ($record) => $record->rider?->email ?: trans('trips.admin.fields.na'))
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                            ])
                            ->columns(3)
                            ->compact()
                            ->visible(fn ($record) => (bool) $record->rider),

                        // Rider Vehicle Information
                        Section::make(trans('trips.admin.sections.rider_vehicle_information'))
                            ->icon('heroicon-o-truck')
                            ->schema([
                                // Vehicle Information
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('rider.vehicle.carType.name')
                                            ->label(trans('trips.admin.fields.car_type'))
                                            ->formatStateUsing(fn ($record) => $record->rider?->vehicle?->carType?->name ?: trans('trips.admin.fields.na'))
                                            ->icon('heroicon-o-truck'),

                                        TextEntry::make('rider.vehicle.carMake.name')
                                            ->label(trans('trips.admin.fields.car_make'))
                                            ->formatStateUsing(fn ($record) => $record->rider?->vehicle?->carMake?->name ?: trans('trips.admin.fields.na'))
                                            ->icon('heroicon-o-wrench'),

                                        TextEntry::make('rider.vehicle.carModel.name')
                                            ->label(trans('trips.admin.fields.car_model'))
                                            ->formatStateUsing(fn ($record) => $record->rider?->vehicle?->carModel?->name ?: trans('trips.admin.fields.na'))
                                            ->icon('heroicon-o-cog'),

                                        TextEntry::make('rider.vehicle.carColor.name')
                                            ->label(trans('trips.admin.fields.color'))
                                            ->formatStateUsing(fn ($record) => $record->rider?->vehicle?->carColor?->name ?: trans('trips.admin.fields.na'))
                                            ->icon('heroicon-o-swatch'),

                                        TextEntry::make('rider.vehicle.plate_number')
                                            ->label(trans('trips.admin.fields.plate_number'))
                                            ->formatStateUsing(fn ($record) => $record->rider?->vehicle?->plate_number ?: trans('trips.admin.fields.na'))
                                            ->icon('heroicon-o-identification')
                                            ->badge()
                                            ->color('gray'),
                                    ])
                                    ->visible(fn ($record) => (bool) $record->rider?->vehicle),
                            ])
                            ->compact()
                            ->visible(fn ($record) => (bool) $record->rider),
                    ]),
            ])
            ->collapsed(false)
            ->columnSpanFull()
            ->compact();
    }
}
