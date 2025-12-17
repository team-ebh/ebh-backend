<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use App\Filament\Resources\TripResource\Widgets\TripStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListTrips extends ListRecords
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - trips are created via API
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TripStatsWidget::class,
        ];
    }
}
