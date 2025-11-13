<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Pages;

use App\Filament\Resources\BaseViewRecord;
use App\Filament\Resources\Riders\Infolists\RiderInfolist;
use App\Filament\Resources\Riders\RiderResource;
use Filament\Actions;
use Filament\Schemas\Schema;

class ViewRider extends BaseViewRecord
{
    protected static string $resource = RiderResource::class;

    public function infolist(Schema $schema): Schema
    {
        return RiderInfolist::configure($schema);
    }

    protected function getCustomHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function mutateInfolistDataBeforeFill(array $data): array
    {
        // Eager load documents with their relations and media for better performance
        $this->record->load([
            'documents.document',
            'documents.media',
            'company',
            'media',
            'vehicle.carType',
            'vehicle.carColor',
            'vehicle.carMake',
            'vehicle.carModel',
            'vehicle.vehicleType',
            'vehicle.passengerCapacity',
            'vehicle.accessibilityFeatures',
        ]);

        return $data;
    }
}
