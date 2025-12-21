<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Pages;

use App\Enums\Trip\TripLocationStatusEnum;
use App\Enums\Trip\TripLocationTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Filament\Resources\TripResource;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\TripStatusLog;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewTripTracking extends ViewRecord
{
    protected static string $resource = TripResource::class;

    public function getTitle(): string
    {
        return trans('trips.admin.tracking.title', ['id' => tripNumberFormat($this->record)]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager load all required relationships to optimize queries
        $this->record->load([
            'customer',
            'rider',
            'locations' => function ($query) {
                $query->orderBy(TripLocation::COLUMN_SEQUENCE);
            },
            'statusLogs' => function ($query) {
                $query->orderBy(TripStatusLog::COLUMN_CREATED_AT);
            },
        ]);

        return $data;
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Section 1: Trip Status Timeline
                Section::make(trans('trips.admin.tracking.sections.status_timeline'))
                    ->description(trans('trips.admin.tracking.sections.status_timeline_description'))
                    ->schema([
                        RepeatableEntry::make('statusLogs')
                            ->label('')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make(TripStatusLog::COLUMN_STATUS)
                                            ->label(trans('trips.admin.tracking.fields.status'))
                                            ->badge()
                                            ->size('lg')
                                            ->formatStateUsing(fn (TripStatusEnum $state): string => $state->getLabel())
                                            ->color(fn (TripStatusLog $record): string | array | null => $record->{TripStatusLog::COLUMN_STATUS}->getColor())
                                            ->icon(fn (TripStatusLog $record): ?string => $record->{TripStatusLog::COLUMN_STATUS}->getIcon()),

                                        TextEntry::make(TripStatusLog::COLUMN_CREATED_AT)
                                            ->label(trans('trips.admin.tracking.fields.timestamp'))
                                            ->dateTime()
                                            ->icon('heroicon-o-clock')
                                            ->color('gray'),

                                        TextEntry::make(TripStatusLog::COLUMN_CHANGED_BY_TYPE)
                                            ->label(trans('trips.admin.tracking.fields.changed_by'))
                                            ->formatStateUsing(function (TripStatusLog $record): string {
                                                if (! $record->{TripStatusLog::COLUMN_CHANGED_BY_TYPE}) {
                                                    return trans('trips.admin.tracking.placeholders.system');
                                                }

                                                return $record->{TripStatusLog::COLUMN_CHANGED_BY_TYPE};
                                            })
                                            ->color('gray')
                                            ->icon('heroicon-o-user'),
                                    ]),
                            ])
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
                    ->id('timeline-section'),

                // Section 2: Trip Location Cards
                Section::make(trans('trips.admin.tracking.sections.locations'))
                    ->description(trans('trips.admin.tracking.sections.locations_description'))
                    ->schema([
                        RepeatableEntry::make('locations')
                            ->label('')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make(TripLocation::COLUMN_TYPE)
                                            ->label(trans('trips.admin.tracking.fields.location_type'))
                                            ->badge()
                                            ->size('lg')
                                            ->formatStateUsing(fn (TripLocationTypeEnum $state): string => $state->getLabel())
                                            ->color(fn (TripLocationTypeEnum $state): string | array | null => match ($state) {
                                                TripLocationTypeEnum::ORIGIN => 'success',
                                                TripLocationTypeEnum::DESTINATION => 'danger',
                                            })
                                            ->icon(fn (TripLocationTypeEnum $state): string => match ($state) {
                                                TripLocationTypeEnum::ORIGIN => 'heroicon-o-map-pin',
                                                TripLocationTypeEnum::DESTINATION => 'heroicon-o-flag',
                                            }),

                                        TextEntry::make(TripLocation::COLUMN_STATUS)
                                            ->label(trans('trips.admin.tracking.fields.location_status'))
                                            ->badge()
                                            ->size('lg')
                                            ->formatStateUsing(fn (TripLocationStatusEnum $state): string => $state->getLabel())
                                            ->color(fn (TripLocation $record): string | array | null => $record->{TripLocation::COLUMN_STATUS}->getColor())
                                            ->icon(fn (TripLocation $record): ?string => $record->{TripLocation::COLUMN_STATUS}->getIcon()),
                                    ]),

                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make(TripLocation::COLUMN_LOCATION_TITLE)
                                            ->label(trans('trips.admin.tracking.fields.address'))
                                            ->icon('heroicon-o-map-pin')
                                            ->weight(FontWeight::Bold)
                                            ->color('primary'),

                                        TextEntry::make(TripLocation::COLUMN_LOCATION_SUB_TITLE)
                                            ->label(trans('trips.admin.tracking.fields.address_details'))
                                            ->icon('heroicon-o-information-circle')
                                            ->color('gray')
                                            ->placeholder(trans('trips.admin.tracking.placeholders.no_details')),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make(TripLocation::COLUMN_LATITUDE)
                                            ->label(trans('trips.admin.tracking.fields.latitude'))
                                            ->icon('heroicon-o-globe-alt')
                                            ->color('gray')
                                            ->numeric(decimalPlaces: 6),

                                        TextEntry::make(TripLocation::COLUMN_LONGITUDE)
                                            ->label(trans('trips.admin.tracking.fields.longitude'))
                                            ->icon('heroicon-o-globe-alt')
                                            ->color('gray')
                                            ->numeric(decimalPlaces: 6),

                                        TextEntry::make(TripLocation::COLUMN_SEQUENCE)
                                            ->label(trans('trips.admin.tracking.fields.sequence'))
                                            ->icon('heroicon-o-numbered-list')
                                            ->badge()
                                            ->color('gray'),
                                    ]),
                            ])
                            ->contained(true),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
                    ->id('locations-section'),

                // Section 3: Real-time Map
                Section::make(trans('trips.admin.tracking.sections.map'))
                    ->description(trans('trips.admin.tracking.sections.map_description'))
                    ->schema([
                        ViewEntry::make('trip_map')
                            ->view('filament.infolists.components.trip-map')
                            ->state([
                                'trip' => $this->record,
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed()
                    ->id('map-section'),
            ]);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
