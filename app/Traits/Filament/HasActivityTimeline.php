<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use App\Filament\Infolists\Components\ActivityTimeline;
use Filament\Schemas\Components\Section;

trait HasActivityTimeline
{
    protected function addActivityTimelineSection(array $components): array
    {
        // Add activity timeline section at the end
        $components[] = Section::make(trans('general.admin.activity_timeline'))
            ->icon('heroicon-o-clock')
            ->description(trans('general.admin.activity_timeline_description'))
            ->collapsible()
            ->collapsed()
            ->schema([
                ActivityTimeline::make(),
            ]);

        return $components;
    }
}
