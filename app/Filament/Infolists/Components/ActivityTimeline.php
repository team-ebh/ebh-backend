<?php

declare(strict_types=1);

namespace App\Filament\Infolists\Components;

use Filament\Infolists\Components\Entry;

class ActivityTimeline extends Entry
{
    protected string $view = 'filament.infolists.components.activity-timeline';

    public static function make(?string $name = 'activity_timeline'): static
    {
        $static = app(static::class, ['name' => $name ?? 'activity_timeline']);

        $static->label('');

        return $static;
    }

    public function getState(): mixed
    {
        $record = $this->getRecord();

        if (! $record || ! method_exists($record, 'getActivityTimeline')) {
            return collect();
        }

        return $record->getActivityTimeline();
    }
}
