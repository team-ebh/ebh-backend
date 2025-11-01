<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Traits\Filament\HasTimestampsHeaderActions;
use Filament\Resources\Pages\ViewRecord;

abstract class BaseViewRecord extends ViewRecord
{
    use HasTimestampsHeaderActions;

    protected function getHeaderActions(): array
    {
        $actions = $this->getCustomHeaderActions();

        return array_merge(
            $actions,
            $this->getTimestampsHeaderActions()
        );
    }

    /**
     * Override this method to add custom header actions
     */
    protected function getCustomHeaderActions(): array
    {
        return [
            //
        ];
    }
}
