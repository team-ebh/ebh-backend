<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use App\Filament\Resources\TripResource\Sections\JourneySection;
use App\Filament\Resources\TripResource\Sections\ParticipantsSection;
use App\Filament\Resources\TripResource\Sections\PaymentSection;
use App\Filament\Resources\TripResource\Sections\TripDetailsSection;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ViewTrip extends ViewRecord
{
    protected static string $resource = TripResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->record->load([
            'customer:id,first_name,last_name,phone_number,email',
            'rider:id,full_name,phone_number,email,latitude,longitude,last_location_update',
            'rider.vehicle:id,rider_id,car_type_id,car_make_id,car_model_id,car_color_id,plate_number',
            'rider.vehicle.carType:id,name',
            'rider.vehicle.carMake:id,name',
            'rider.vehicle.carModel:id,name',
            'rider.vehicle.carColor:id,name',
            'accessibility',
            'statusLogs' => fn ($query) => $query->orderBy('id', 'desc'),
            'locations:id,trip_id,location_title,location_sub_title,latitude,longitude,type,sequence,status',
            'locations.statusLogs' => fn ($query) => $query->orderBy('id', 'desc'),
            'order',
            'order.payments' => fn ($query) => $query->orderBy('id', 'desc'),
            'order.payments.logs' => fn ($query) => $query->orderBy('id', 'asc'),
            'order.lastPayment:id,order_id,status',
            'order.paidPayment:id,order_id,status',
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('TripTabs')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->vertical()
                    ->tabs([
                        // Tab 1: Overview
                        Tabs\Tab::make(trans('trips.admin.tabs.overview'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TripDetailsSection::make(),
                                PaymentSection::make(),
                            ]),

                        // Tab 2: Journey
                        Tabs\Tab::make(trans('trips.admin.tabs.journey'))
                            ->icon('heroicon-o-map')
                            ->schema([
                                JourneySection::make($this->record),
                            ]),

                        // Tab 3: Participants
                        Tabs\Tab::make(trans('trips.admin.tabs.participants'))
                            ->icon('heroicon-o-users')
                            ->schema([
                                ParticipantsSection::make(),
                            ]),
                    ]),
            ]);
    }
}
