<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Tables;

use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Filament\Resources\TripResource;
use App\Models\Trip;
use App\Models\TripLocation;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class TripsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn ($query) => $query
                    ->where(Trip::COLUMN_STATUS, '!=', TripStatusEnum::DRAFT)
                    ->with(['customer', 'rider', 'firstOriginLocation', 'firstDestinationLocation'])
            )
            ->columns([
                TextColumn::make('id')
                    ->label(trans('trips.admin.fields.trip_id'))
                    ->formatStateUsing(fn ($record) => tripNumberFormat($record))
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('customer.first_name')
                    ->label(trans('trips.admin.fields.customer'))
                    ->searchable(['first_name', 'last_name'])
                    ->formatStateUsing(fn ($record) => $record->customer
                        ? "{$record->customer->first_name} {$record->customer->last_name}"
                        : '-')
                    ->description(fn ($record) => defaultPrefixPhoneNumber() . $record->customer?->phone_number),

                TextColumn::make('rider.full_name')
                    ->label(trans('trips.admin.fields.rider'))
                    ->searchable(['full_name'])
                    ->formatStateUsing(fn ($record) => $record->rider
                        ? $record->rider->full_name
                        : trans('trips.admin.fields.not_assigned'))
                    ->description(fn ($record) => $record->rider ? defaultPrefixPhoneNumber() . $record->rider->phone_number : '')
                    ->default(trans('trips.admin.fields.not_assigned'))
                    ->color(fn ($record) => $record->rider ? 'success' : 'gray'),

                TextColumn::make('firstOriginLocation.location_title')
                    ->label(trans('trips.admin.fields.origin_location'))
                    ->description(function ($record) {
                        return Str::words($record->firstOriginLocation?->{TripLocation::COLUMN_LOCATION_SUB_TITLE}, 8) ?? '-';
                    })
                    ->wrap(),

                TextColumn::make('firstDestinationLocation.location_title')
                    ->label(trans('trips.admin.fields.destination_location'))
                    ->description(function ($record) {
                        return Str::words($record->firstDestinationLocation?->{TripLocation::COLUMN_LOCATION_SUB_TITLE}, 8) ?? '-';
                    })
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color(fn ($state) => match ($state) {
                        TripStatusEnum::DRAFT => 'gray',
                        TripStatusEnum::PENDING_RIDER => 'warning',
                        TripStatusEnum::ACCEPTED_RIDER => 'info',
                        TripStatusEnum::IN_PROGRESS => 'primary',
                        TripStatusEnum::COMPLETED => 'success',
                        TripStatusEnum::CANCELED_BY_CUSTOMER, TripStatusEnum::CANCELLED_BY_RIDER => 'danger',
                    }),

                TextColumn::make('trip_type_id')
                    ->label(trans('trips.admin.fields.trip_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color('info'),

                TextColumn::make('vehicle_type_id')
                    ->label(trans('trips.admin.fields.vehicle_type'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color('primary'),

                TextColumn::make('total_price')
                    ->label(trans('trips.admin.fields.total_price'))
                    ->money(fn ($record) => $record->currency->value)
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),

                TextColumn::make('created_at')
                    ->label(trans('trips.admin.fields.created'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        TripStatusEnum::PENDING_RIDER->value => TripStatusEnum::PENDING_RIDER->getLabel(),
                        TripStatusEnum::ACCEPTED_RIDER->value => TripStatusEnum::ACCEPTED_RIDER->getLabel(),
                        TripStatusEnum::IN_PROGRESS->value => TripStatusEnum::IN_PROGRESS->getLabel(),
                        TripStatusEnum::COMPLETED->value => TripStatusEnum::COMPLETED->getLabel(),
                        TripStatusEnum::CANCELED_BY_CUSTOMER->value => TripStatusEnum::CANCELED_BY_CUSTOMER->getLabel(),
                        TripStatusEnum::CANCELLED_BY_RIDER->value => TripStatusEnum::CANCELLED_BY_RIDER->getLabel(),
                    ])
                    ->multiple(),

                SelectFilter::make('trip_type_id')
                    ->label(trans('trips.admin.filters.trip_type'))
                    ->options(TripTypeEnum::class),

                SelectFilter::make('vehicle_type_id')
                    ->label(trans('trips.admin.filters.vehicle_type'))
                    ->options(TripVehicleTypeEnum::class),

                SelectFilter::make('payment_method')
                    ->label(trans('trips.admin.filters.payment_method'))
                    ->options(PaymentMethodEnum::class),

                SelectFilter::make('customer_id')
                    ->label(trans('trips.admin.filters.customer'))
                    ->relationship('customer', 'first_name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10)
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} ({$record->getPhoneNumberWithPrefix()})"),

                SelectFilter::make('rider_id')
                    ->label(trans('trips.admin.filters.rider'))
                    ->relationship('rider', 'full_name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10)
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->full_name} ({$record->getPhoneNumberWithPrefix()})"),

                Filter::make('created_at')
                    ->schema([
                        Select::make('period')
                            ->label(trans('trips.admin.filters.date_period'))
                            ->options([
                                'today' => trans('trips.admin.filters.today'),
                                'yesterday' => trans('trips.admin.filters.yesterday'),
                                'this_week' => trans('trips.admin.filters.this_week'),
                                'this_month' => trans('trips.admin.filters.this_month'),
                                'custom' => trans('trips.admin.filters.custom_range'),
                            ])
                            ->live(),
                        DatePicker::make('date_from')
                            ->label(trans('trips.admin.filters.date_from'))
                            ->visible(fn ($get) => $get('period') === 'custom'),
                        DatePicker::make('date_to')
                            ->label(trans('trips.admin.filters.date_to'))
                            ->visible(fn ($get) => $get('period') === 'custom'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['period'] ?? null,
                            function (Builder $query, string $period) use ($data) {
                                return match ($period) {
                                    'today' => $query->whereDate(Trip::COLUMN_CREATED_AT, today()),
                                    'yesterday' => $query->whereDate(Trip::COLUMN_CREATED_AT, today()->subDay()),
                                    'this_week' => $query->whereBetween(Trip::COLUMN_CREATED_AT, [
                                        now()->startOfWeek(),
                                        now()->endOfWeek(),
                                    ]),
                                    'this_month' => $query->whereBetween(Trip::COLUMN_CREATED_AT, [
                                        now()->startOfMonth(),
                                        now()->endOfMonth(),
                                    ]),
                                    'custom' => $query->when(
                                        $data['date_from'] ?? null,
                                        fn (Builder $query, $date) => $query->whereDate(Trip::COLUMN_CREATED_AT, '>=', $date)
                                    )->when(
                                        $data['date_to'] ?? null,
                                        fn (Builder $query, $date) => $query->whereDate(Trip::COLUMN_CREATED_AT, '<=', $date)
                                    ),
                                    default => $query,
                                };
                            }
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['period'] ?? null)) {
                            return null;
                        }

                        $period = match ($data['period']) {
                            'today' => trans('trips.admin.filters.today'),
                            'yesterday' => trans('trips.admin.filters.yesterday'),
                            'this_week' => trans('trips.admin.filters.this_week'),
                            'this_month' => trans('trips.admin.filters.this_month'),
                            'custom' => trans('trips.admin.filters.custom_range'),
                            default => null,
                        };

                        if ($data['period'] === 'custom' && isset($data['date_from'], $data['date_to'])) {
                            return trans('trips.admin.filters.date_range_indicator', [
                                'from' => $data['date_from'],
                                'to' => $data['date_to'],
                            ]);
                        }

                        return $period;
                    }),

                TernaryFilter::make('has_rider')
                    ->label(trans('trips.admin.fields.has_rider_assigned'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull(Trip::COLUMN_RIDER_ID),
                        false: fn (Builder $query) => $query->whereNull(Trip::COLUMN_RIDER_ID),
                        blank: fn (Builder $query) => $query,
                    ),

                TernaryFilter::make('is_paid')
                    ->label(trans('trips.admin.fields.is_paid'))
                    ->queries(
                        true: fn (Builder $query) => $query->has('paidPayment'),
                        false: fn (Builder $query) => $query->doesntHave('paidPayment'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (Trip $record): string => TripResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('id', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds for live updates
    }
}
