<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Traits\Filament\HasActivityLogHeaderActions;
use Filament\Resources\Pages\ViewRecord;

abstract class BaseViewRecord extends ViewRecord
{
    use HasActivityLogHeaderActions;

    protected function getHeaderActions(): array
    {
        $actions = $this->getCustomHeaderActions();

        return array_merge(
            $actions,
            $this->getActivityLogHeaderActions()
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
