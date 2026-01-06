<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Sections;

use App\Enums\Trip\RideTypeEnum;
use App\Filament\Resources\TripResource;
use App\Models\Trip;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;

class RelatedTripSection
{
    public static function make(Trip $trip): ?Section
    {
        // Check if this is a scheduled return trip with a demand trip (show regardless of ride_type)
        if (self::hasDemandTrip($trip)) {
            return self::makeDemandTripSection($trip);
        }

        // Check if this is a demand trip with a scheduled return trip (only for round trips)
        if (self::isRoundTrip($trip) && self::hasScheduledReturnTrip($trip)) {
            return self::makeScheduledReturnTripSection($trip);
        }

        return null;
    }

    private static function isRoundTrip(Trip $trip): bool
    {
        return in_array($trip->{Trip::COLUMN_RIDE_TYPE}, [
            RideTypeEnum::ROUND_TRIP,
            RideTypeEnum::ROUND_TRIP_WAIT,
        ], true);
    }

    private static function hasScheduledReturnTrip(Trip $trip): bool
    {
        return $trip->scheduledReturnTrip !== null;
    }

    private static function hasDemandTrip(Trip $trip): bool
    {
        return $trip->{Trip::COLUMN_DEMAND_TRIP_ID} !== null;
    }

    private static function makeScheduledReturnTripSection(Trip $trip): Section
    {
        $returnTrip = $trip->scheduledReturnTrip;

        return Section::make(trans('trips.admin.sections.scheduled_return_trip'))
            ->description(trans('trips.admin.sections.scheduled_return_trip_description'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->headerActions([
                Action::make('viewScheduledReturnTrip')
                    ->label(trans('trips.admin.actions.view'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('warning')
                    ->url(TripResource::getUrl('view', ['record' => $returnTrip->id]), shouldOpenInNewTab: true),
            ])
            ->schema([
                Grid::make(4)
                    ->schema([
                        TextEntry::make('scheduledReturnTrip.id')
                            ->label(trans('trips.admin.fields.trip_id'))
                            ->formatStateUsing(fn () => tripNumberFormat($returnTrip))
                            ->badge()
                            ->color('primary')
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-o-hashtag'),

                        TextEntry::make('scheduledReturnTrip.status')
                            ->label(trans('trips.admin.fields.status'))
                            ->badge()
                            ->weight(FontWeight::Bold),

                        TextEntry::make('scheduledReturnTrip.trip_type_id')
                            ->label(trans('trips.admin.fields.trip_type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->getLabel())
                            ->color('info'),

                        TextEntry::make('scheduledReturnTrip.scheduled_time')
                            ->label(trans('trips.admin.fields.scheduled_time'))
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-clock')
                            ->color('warning'),
                    ]),
            ])
            ->collapsed(false)
            ->columnSpanFull()
            ->compact();
    }

    private static function makeDemandTripSection(Trip $trip): Section
    {
        $demandTrip = $trip->demandTrip;

        return Section::make(trans('trips.admin.sections.demand_trip'))
            ->description(trans('trips.admin.sections.demand_trip_description'))
            ->icon('heroicon-o-arrow-right-circle')
            ->headerActions([
                Action::make('viewDemandTrip')
                    ->label(trans('trips.admin.actions.view'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('success')
                    ->url(TripResource::getUrl('view', ['record' => $demandTrip->id]), shouldOpenInNewTab: true),
            ])
            ->schema([
                Grid::make(4)
                    ->schema([
                        TextEntry::make('demandTrip.id')
                            ->label(trans('trips.admin.fields.trip_id'))
                            ->formatStateUsing(fn () => tripNumberFormat($demandTrip))
                            ->badge()
                            ->color('primary')
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-o-hashtag'),

                        TextEntry::make('demandTrip.status')
                            ->label(trans('trips.admin.fields.status'))
                            ->badge()
                            ->weight(FontWeight::Bold),

                        TextEntry::make('demandTrip.trip_type_id')
                            ->label(trans('trips.admin.fields.trip_type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->getLabel())
                            ->color('info'),

                        TextEntry::make('demandTrip.created_at')
                            ->label(trans('trips.admin.fields.created_at'))
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar')
                            ->color('gray'),
                    ]),
            ])
            ->collapsed(false)
            ->columnSpanFull()
            ->compact();
    }
}
