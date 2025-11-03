<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Check for subject filter in query params
        $subjectType = request()->query('subject_type');
        $subjectId = request()->query('subject_id');

        if ($subjectType && $subjectId) {
            // Store in session for table to use
            session([
                'activity_log_filter_subject_type' => urldecode($subjectType),
                'activity_log_filter_subject_id' => $subjectId,
            ]);
        } else {
            // No query params - clear the filter session
            session()->forget([
                'activity_log_filter_subject_type',
                'activity_log_filter_subject_id',
            ]);
        }
    }
}
