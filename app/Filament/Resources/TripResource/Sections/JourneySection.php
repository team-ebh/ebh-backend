<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Sections;

use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

class JourneySection
{
    public static function make(Model $record): Grid
    {
        return Grid::make(1)
            ->schema([
                Section::make(trans('trips.admin.sections.journey_timeline'))
                    ->description(trans('trips.admin.sections.journey_timeline_description'))
                    ->schema([
                        ViewEntry::make('journey_timeline')
                            ->view('filament.infolists.components.journey-timeline')
                            ->state([
                                'trip' => $record,
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make(trans('trips.admin.sections.trip_map'))
                    ->description(trans('trips.admin.sections.trip_map_description'))
                    ->schema([
                        ViewEntry::make('trip_map')
                            ->view('filament.infolists.components.trip-map')
                            ->state([
                                'trip' => $record,
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ])
            ->columnSpanFull();
    }
}
