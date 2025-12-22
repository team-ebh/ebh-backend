<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Pages;

use App\Enums\Payment\PaymentMethodEnum;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Riders\RiderResource;
use App\Filament\Resources\TripResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewTrip extends ViewRecord
{
    protected static string $resource = TripResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->load([
            'customer:id,first_name,last_name,phone_number,email',
            'rider:id,full_name,phone_number,email,latitude,longitude,last_location_update',
            'rider.vehicle:id,rider_id,car_type_id,car_make_id,car_model_id,plate_number',
            'rider.vehicle.carType:id,name',
            'rider.vehicle.carMake:id,name',
            'rider.vehicle.carModel:id,name',
            'accessibility',
            'statusLogs' => fn ($query) => $query->orderBy('created_at', 'desc'),
            'locations' => fn ($query) => $query->with([
                'statusLogs' => fn ($q) => $q->orderBy('created_at', 'desc'),
            ])->orderBy('sequence'),
            'payments' => fn ($query) => $query->with([
                'logs' => fn ($q) => $q->orderBy('created_at', 'asc'),
            ])->orderBy('created_at', 'desc'),
            'lastPayment:id,trip_id,status',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('customerInfo')
                    ->label(trans('trips.admin.actions.customer_info'))
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->slideOver()
                    ->modalHeading(trans('trips.admin.sections.customer_information'))
                    ->schema([
                        Section::make()
                            ->schema([
                                Grid::make(2)
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
                                            ->formatStateUsing(function ($record) {
                                                if (! $record->customer) {
                                                    return trans('trips.admin.fields.na');
                                                }

                                                $phone = $record->customer->phone_number;
                                                if (! str_starts_with($phone, '+')) {
                                                    $phone = defaultPrefixPhoneNumber() . $phone;
                                                }

                                                return $phone;
                                            })
                                            ->icon('heroicon-o-phone')
                                            ->copyable(),

                                        TextEntry::make('customer.email')
                                            ->label(trans('trips.admin.fields.email'))
                                            ->formatStateUsing(fn ($record) => $record->customer?->email ?: trans('trips.admin.fields.na'))
                                            ->icon('heroicon-o-envelope')
                                            ->copyable()
                                            ->columnSpan(2),
                                    ]),
                            ]),
                    ])
                    ->modalFooterActions([
                        Action::make('viewCustomerProfile')
                            ->label(trans('trips.admin.actions.view_customer_profile'))
                            ->url(
                                fn ($record) => $record->customer
                                ? CustomerResource::getUrl('view', ['record' => $record->customer->id])
                                : null,
                                true
                            )
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->color('primary')
                            ->visible(fn ($record) => (bool) $record->customer),
                    ])
                    ->visible(fn ($record) => (bool) $record->customer),

                Action::make('riderInfo')
                    ->label(trans('trips.admin.actions.rider_info'))
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->slideOver()
                    ->modalHeading(trans('trips.admin.sections.rider_information'))
                    ->schema([
                        Section::make(trans('trips.admin.fields.rider'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('rider.full_name')
                                            ->label(trans('trips.admin.fields.full_name'))
                                            ->formatStateUsing(fn ($record) => $record->rider?->full_name ?: trans('trips.admin.fields.not_assigned'))
                                            ->icon('heroicon-o-user')
                                            ->weight(FontWeight::Bold),

                                        TextEntry::make('rider.phone_number')
                                            ->label(trans('trips.admin.fields.phone'))
                                            ->formatStateUsing(function ($record) {
                                                if (! $record->rider) {
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

                                        TextEntry::make('rider.rating')
                                            ->label(trans('trips.admin.fields.rating'))
                                            ->formatStateUsing(fn ($record) => $record->rider
                                                ? '4.5 ★'
                                                : trans('trips.admin.fields.na'))
                                            ->icon('heroicon-o-star')
                                            ->color('warning'),
                                    ]),
                            ])
                            ->compact(),

                        Section::make(trans('trips.admin.fields.vehicle_info'))
                            ->schema([
                                Grid::make(2)
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

                                        TextEntry::make('rider.vehicle.plate_number')
                                            ->label(trans('trips.admin.fields.plate_number'))
                                            ->formatStateUsing(fn ($record) => $record->rider?->vehicle?->plate_number ?: trans('trips.admin.fields.na'))
                                            ->icon('heroicon-o-identification')
                                            ->badge()
                                            ->color('gray'),
                                    ]),
                            ])
                            ->compact()
                            ->visible(fn ($record) => (bool) $record->rider?->vehicle),
                    ])
                    ->modalFooterActions([
                        Action::make('viewRiderProfile')
                            ->label(trans('trips.admin.actions.view_rider_profile'))
                            ->url(
                                fn ($record) => $record->rider
                                ? RiderResource::getUrl('view', ['record' => $record->rider->id])
                                : null,
                                true
                            )
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->color('primary')
                            ->visible(fn ($record) => (bool) $record->rider),
                    ])
                    ->visible(fn ($record) => (bool) $record->rider),
            ])
                ->label(trans('trips.admin.actions.participants_info'))
                ->icon('heroicon-o-users')
                ->color('info')
                ->button(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Trip Information
                Section::make(trans('trips.admin.sections.trip_information'))
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

                                Grid::make(4)
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

                                        TextEntry::make('accessibility')
                                            ->label(trans('trips.admin.fields.accessibility'))
                                            ->formatStateUsing(function ($record) {
                                                if (! $record->accessibility || $record->accessibility->isEmpty()) {
                                                    return trans('general.admin.none');
                                                }

                                                return $record->accessibility
                                                    ->pluck('accessibility_requirement')
                                                    ->unique(fn ($requirement) => $requirement->value)
                                                    ->map(fn ($requirement) => $requirement->getLabel())
                                                    ->join(', ');
                                            })
                                            ->icon('heroicon-o-heart')
                                            ->color('purple')
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->compact(),

                        // Payment Information Section
                        Section::make(trans('trips.admin.sections.payment_info'))
                            ->headerActions([
                                Action::make('viewPaymentLogs')
                                    ->label(trans('trips.admin.payment_logs.view_logs'))
                                    ->icon('heroicon-o-document-text')
                                    ->color('info')
                                    ->slideOver()
                                    ->modalWidth('7xl')
                                    ->modalHeading(trans('trips.admin.payment_logs.title'))
                                    ->infolist([
                                        RepeatableEntry::make('payments')
                                            ->label('')
                                            ->schema([
                                                Section::make(fn ($record) => trans('trips.admin.payment_logs.payment_logs_heading', [
                                                    'number' => $record->payment_number,
                                                ]))
                                                    ->schema([
                                                        // Payment Summary
                                                        Section::make(trans('trips.admin.payment_logs.payment_summary'))
                                                            ->schema([
                                                                Grid::make(4)
                                                                    ->schema([
                                                                        TextEntry::make('payment_number')
                                                                            ->label(trans('trips.admin.payment_logs.payment_number_full'))
                                                                            ->weight(FontWeight::Bold)
                                                                            ->copyable(),

                                                                        TextEntry::make('amount')
                                                                            ->label(trans('trips.admin.payment_logs.amount'))
                                                                            ->money(fn ($record) => $record->currency->value)
                                                                            ->weight(FontWeight::Bold)
                                                                            ->color('success'),

                                                                        TextEntry::make('status')
                                                                            ->badge()
                                                                            ->formatStateUsing(fn ($state) => $state->getLabel())
                                                                            ->color(fn ($state) => match ($state->value) {
                                                                                'pending' => 'warning',
                                                                                'paid' => 'success',
                                                                                'failed' => 'danger',
                                                                                'refunded' => 'info',
                                                                                default => 'gray',
                                                                            }),

                                                                        TextEntry::make('gateway')
                                                                            ->badge()
                                                                            ->formatStateUsing(fn ($state) => $state->getLabel())
                                                                            ->color('primary'),
                                                                    ]),

                                                                TextEntry::make('gateway_reference_id')
                                                                    ->label(trans('trips.admin.payment_logs.gateway_reference_id'))
                                                                    ->default(trans('trips.admin.fields.na'))
                                                                    ->copyable()
                                                                    ->visible(fn ($record) => filled($record->gateway_reference_id)),
                                                            ])
                                                            ->collapsible()
                                                            ->collapsed(false),

                                                        // HTTP Logs
                                                        Section::make(trans('trips.admin.payment_logs.http_logs'))
                                                            ->schema([
                                                                RepeatableEntry::make('logs')
                                                                    ->label('')
                                                                    ->schema([
                                                                        Grid::make(5)
                                                                            ->schema([
                                                                                TextEntry::make('type')
                                                                                    ->label(trans('trips.admin.payment_logs.type'))
                                                                                    ->badge()
                                                                                    ->formatStateUsing(fn ($state) => $state->getLabel())
                                                                                    ->color(fn ($record) => $record->type->value === 'generate_link' ? 'info' : 'warning'),

                                                                                TextEntry::make('method')
                                                                                    ->label(trans('trips.admin.payment_logs.method'))
                                                                                    ->badge()
                                                                                    ->formatStateUsing(fn ($state) => strtoupper($state))
                                                                                    ->color('gray'),

                                                                                TextEntry::make('status_code')
                                                                                    ->label(trans('trips.admin.payment_logs.status_code'))
                                                                                    ->badge()
                                                                                    ->default(trans('trips.admin.fields.na'))
                                                                                    ->color(fn ($state) => match (true) {
                                                                                        $state >= 200 && $state < 300 => 'success',
                                                                                        $state >= 400 => 'danger',
                                                                                        default => 'warning',
                                                                                    }),

                                                                                TextEntry::make('response_time')
                                                                                    ->label(trans('trips.admin.payment_logs.response_time'))
                                                                                    ->suffix('ms')
                                                                                    ->default(trans('trips.admin.fields.na'))
                                                                                    ->icon('heroicon-o-bolt'),

                                                                                TextEntry::make('created_at')
                                                                                    ->label(trans('trips.admin.payment_logs.timestamp'))
                                                                                    ->dateTime('M d, Y H:i:s')
                                                                                    ->size('sm'),
                                                                            ]),

                                                                        TextEntry::make('url')
                                                                            ->label(trans('trips.admin.payment_logs.url'))
                                                                            ->copyable()
                                                                            ->limit(100)
                                                                            ->visible(fn ($record) => filled($record->url)),

                                                                        TextEntry::make('request_body')
                                                                            ->label(trans('trips.admin.payment_logs.request_body'))
                                                                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                                                            ->copyable()
                                                                            ->extraAttributes(['class' => 'font-mono text-xs'])
                                                                            ->visible(fn ($record) => filled($record->request_body)),

                                                                        TextEntry::make('response_body')
                                                                            ->label(trans('trips.admin.payment_logs.response_body'))
                                                                            ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                                                            ->copyable()
                                                                            ->extraAttributes(['class' => 'font-mono text-xs'])
                                                                            ->visible(fn ($record) => filled($record->response_body)),

                                                                        TextEntry::make('error')
                                                                            ->label(trans('trips.admin.payment_logs.error'))
                                                                            ->color('danger')
                                                                            ->icon('heroicon-o-x-circle')
                                                                            ->visible(fn ($record) => filled($record->error)),
                                                                    ])
                                                                    ->getStateUsing(fn ($record) => $record->logs()->orderBy('created_at', 'desc')->get())
                                                                    ->contained(false),
                                                            ])
                                                            ->collapsible()
                                                            ->collapsed(false),
                                                    ])
                                                    ->collapsible()
                                                    ->collapsed(false),
                                            ])
                                            ->contained(false),
                                    ])
                                    ->visible(fn ($record) => $record->payment_method === PaymentMethodEnum::KNET
                                        && $record->hasPaidPayment()
                                        && $record->payments->isNotEmpty()),
                            ])
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('payment_method')
                                            ->badge()
                                            ->size('lg')
                                            ->icon('heroicon-o-credit-card'),

                                        TextEntry::make('total_price')
                                            ->label(trans('trips.admin.fields.total_price'))
                                            ->suffix(fn ($record) => $record->currency->getLabel())
                                            ->size('lg')
                                            ->weight(FontWeight::Bold)
                                            ->color('success')
                                            ->icon('heroicon-o-banknotes'),

                                        TextEntry::make('lastPayment.status')
                                            ->label(trans('trips.admin.fields.payment_status'))
                                            ->formatStateUsing(fn ($state) => $state?->getLabel() ?: trans('trips.admin.fields.no_payment'))
                                            ->badge()
                                            ->size('lg')
                                            ->weight(FontWeight::Bold)
                                            ->color(fn ($state) => match ($state?->value) {
                                                'pending' => 'warning',
                                                'paid' => 'success',
                                                'failed' => 'danger',
                                                'refunded' => 'info',
                                                default => 'gray',
                                            })
                                            ->icon(fn ($state) => match ($state?->value) {
                                                'paid' => 'heroicon-o-check-circle',
                                                'failed' => 'heroicon-o-x-circle',
                                                'pending' => 'heroicon-o-clock',
                                                'refunded' => 'heroicon-o-arrow-uturn-left',
                                                default => 'heroicon-o-question-mark-circle',
                                            })
                                            ->visible(fn ($record) => $record->payment_method !== PaymentMethodEnum::CASH),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('accessibility_price')
                                            ->label(trans('trips.admin.fields.accessibility_fee'))
                                            ->formatStateUsing(function ($record) {
                                                if (! $record->accessibility_price || (float) $record->accessibility_price === 0.0) {
                                                    return '-';
                                                }

                                                return priceFormat($record->accessibility_price) . ' ' . $record->currency->value;
                                            })
                                            ->icon('heroicon-o-heart')
                                            ->color('pink'),

                                        TextEntry::make('waiting_price')
                                            ->label(trans('trips.admin.fields.waiting_fee'))
                                            ->formatStateUsing(function ($record) {
                                                if (! $record->waiting_price || (float) $record->waiting_price === 0.0) {
                                                    return '-';
                                                }

                                                return priceFormat($record->waiting_price) . ' ' . $record->currency->value;
                                            })
                                            ->icon('heroicon-o-clock')
                                            ->color('orange'),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->compact(),
                    ])
                    ->collapsed(false)
                    ->columnSpanFull()
                    ->compact(),

                // Main Content: Journey Timeline + Map (Side by Side)
                Grid::make(2)
                    ->schema([
                        // Left Column: Journey Timeline
                        Section::make(trans('trips.admin.sections.journey_timeline'))
                            ->description(trans('trips.admin.sections.journey_timeline_description'))
                            ->schema([
                                ViewEntry::make('journey_timeline')
                                    ->view('filament.infolists.components.journey-timeline')
                                    ->state([
                                        'trip' => $this->record,
                                    ]),
                            ])
                            ->collapsible()
                            ->collapsed(false),

                        // Right Column: Map
                        Section::make(trans('trips.admin.sections.trip_map'))
                            ->description(trans('trips.admin.sections.trip_map_description'))
                            ->schema([
                                ViewEntry::make('trip_map')
                                    ->view('filament.infolists.components.trip-map')
                                    ->state([
                                        'trip' => $this->record,
                                    ]),
                            ])
                            ->collapsible()
                            ->collapsed(false),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
