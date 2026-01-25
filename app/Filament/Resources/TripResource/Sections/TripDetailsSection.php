<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Sections;

use App\Models\Trip;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;

class TripDetailsSection
{
    public static function make(): Section
    {
        return Section::make(trans('trips.admin.sections.trip_information'))
            ->description(trans('trips.admin.sections.trip_information_description'))
            ->schema([
                // Trip Details Section
                Section::make(trans('trips.admin.sections.trip_details'))
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                TextEntry::make('id')
                                    ->label(trans('trips.admin.fields.trip_id'))
                                    ->formatStateUsing(fn ($record) => tripNumberFormat($record))
                                    ->badge()
                                    ->color('primary')
                                    ->size('lg')
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-hashtag')
                                    ->copyable(),

                                TextEntry::make('status')
                                    ->label(trans('trips.admin.fields.status'))
                                    ->badge()
                                    ->size('lg')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('created_at')
                                    ->label(trans('trips.admin.fields.created_at'))
                                    ->dateTime('M d, Y H:i')
                                    ->icon('heroicon-o-calendar')
                                    ->color('gray'),

                                TextEntry::make('trip_type_id')
                                    ->label(trans('trips.admin.fields.trip_type'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state->getLabel())
                                    ->color('info')
                                    ->icon('heroicon-o-map'),

                                TextEntry::make('ride_type')
                                    ->label(trans('trips.admin.fields.ride_type'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state->getLabel())
                                    ->color('cyan')
                                    ->icon('heroicon-o-arrow-path-rounded-square'),
                            ]),

                        Grid::make(5)
                            ->schema([
                                TextEntry::make('vehicle_type_id')
                                    ->label(trans('trips.admin.fields.vehicle_type'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state->getLabel())
                                    ->color('warning'),

                                TextEntry::make('passenger_count')
                                    ->label(trans('trips.admin.fields.passengers'))
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-o-users'),

                                TextEntry::make(Trip::COLUMN_WAITING_TIME)
                                    ->label(trans('trips.admin.fields.waiting_time'))
                                    ->formatStateUsing(fn ($state) => $state . ' ' . trans('trips.api.time_units.minutes'))
                                    ->icon('heroicon-o-clock')
                                    ->color('warning')
                                    ->visible(fn ($record) => $record->{Trip::COLUMN_WAITING_TIME} !== null),

                                TextEntry::make('accessibility.accessibility_requirement')
                                    ->label(trans('trips.admin.fields.accessibility'))
                                    ->listWithLineBreaks()
                                    ->icon('heroicon-o-heart')
                                    ->color('purple')
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->compact(),
            ])
            ->collapsed(false)
            ->columnSpanFull()
            ->compact();
    }
}
