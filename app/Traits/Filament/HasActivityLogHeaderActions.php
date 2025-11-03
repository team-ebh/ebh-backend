<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Models\Activity;

trait HasActivityLogHeaderActions
{
    protected function getActivityLogHeaderActions(): array
    {
        $record = $this->record;

        // Check if model uses LogsActivity trait
        if (! method_exists($record, 'activities')) {
            return [];
        }

        // Get last activity for badge
        $lastActivity = $record->activities()->latest()->first();

        return [
            Action::make('activity')
                ->label(trans('general.admin.activity'))
                ->icon('heroicon-o-clock')
                ->color('info')
                ->badge($lastActivity ? $lastActivity->created_at->diffForHumans() : null)
                ->slideOver()
                ->form([
                    Section::make()
                        ->schema([
                            Placeholder::make('recent_activities')
                                ->label('')
                                ->content(function () use ($record) {
                                    return new HtmlString($this->getRecentActivitiesHtml($record));
                                }),
                        ]),
                ])
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalHeading(trans('general.admin.activity'))
                ->modalWidth('lg'),
        ];
    }

    protected function getRecentActivitiesHtml($record): string
    {
        // Get total count of activities
        $totalCount = $record->activities()->count();

        // Get last 5 activities
        $activities = $record->activities()
            ->with('causer')
            ->latest()
            ->limit(5)
            ->get();

        if ($activities->isEmpty()) {
            return '<p class="text-gray-500">' . trans('general.admin.no_activity_recorded') . '</p>';
        }

        $html = '<div class="space-y-4">';

        foreach ($activities as $activity) {
            $eventColor = match ($activity->event) {
                'created' => 'bg-green-100 text-green-800',
                'updated' => 'bg-blue-100 text-blue-800',
                'deleted' => 'bg-red-100 text-red-800',
                'restored' => 'bg-yellow-100 text-yellow-800',
                default => 'bg-gray-100 text-gray-800',
            };

            $eventIcon = match ($activity->event) {
                'created' => '➕',
                'updated' => '✏️',
                'deleted' => '🗑️',
                'restored' => '♻️',
                default => '📝',
            };

            $html .= '
            <div class="relative flex gap-x-4">
                <div class="relative flex h-6 w-6 flex-none items-center justify-center">
                    <div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300"></div>
                </div>
                <div class="flex-auto rounded-md bg-gray-50 p-3 ring-1 ring-inset ring-gray-200">
                    <div class="flex justify-between gap-x-4">
                        <div class="flex items-start gap-2">
                            <div class="flex-1">
                            <span class="text-lg">' . $eventIcon . '</span>

                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ' . $eventColor . '">'
                . ucfirst($activity->event) .
                '</span>
                                <p class="mt-1 text-sm text-gray-700">' . $activity->description . '</p>';

            // Show causer
            if ($activity->causer) {
                $causerName = $this->getActivityCauserName($activity);
                $html .= '<p class="mt-1 text-xs text-gray-500">' . trans('general.admin.activity_by') . ': ' . $causerName . '</p>';
            }

            $html .= '
                            </div>
                        </div>
                        <time datetime="' . $activity->created_at->toIso8601String() . '" class="flex-none text-xs text-gray-500">'
                . $activity->created_at->diffForHumans() .
                '</time>
                    </div>';

            // Show changes if any
            $properties = $activity->properties;
            if ($properties && isset($properties['attributes']) && isset($properties['old'])) {
                $changes = [];
                foreach ($properties['attributes'] as $key => $newValue) {
                    $oldValue = $properties['old'][$key] ?? null;
                    if ($oldValue !== $newValue) {
                        $changes[] = ucwords(str_replace('_', ' ', $key));
                    }
                }

                if (! empty($changes)) {
                    $html .= '<p class="mt-1 text-xs text-gray-600">' . trans('general.admin.changed_fields') . ': ' . implode(', ', $changes) . '</p>';
                }
            }

            $html .= '
                </div>
            </div>
                    <br>';
        }

        $html .= '</div>';

        // Add View All Activities button only if there are more than 5 activities
        if ($totalCount > 5) {
            // Build URL manually to avoid encoding issues with backslashes in class names
            $baseUrl = route('filament.admin.resources.activity-logs.index');
            $subjectType = urlencode(get_class($record));
            $subjectId = $record->id;
            $viewAllUrl = $baseUrl . '?subject_type=' . $subjectType . '&subject_id=' . $subjectId;

            $remainingCount = $totalCount - 5;

            $html .= '
            <div class="mt-6 pt-4 border-t border-gray-200">
                <a href="' . $viewAllUrl . '" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700 transition-colors">
                    ' . trans('general.admin.view_all_activities') . ' (' . $totalCount . ' ' . trans('general.admin.total') . ')
                </a>
                <p class="mt-1 text-xs text-gray-500">' . $remainingCount . ' ' . trans('general.admin.more_activities') . '</p>
            </div>';
        }

        return $html;
    }

    protected function getActivityCauserName(?Activity $activity): string
    {
        if (! $activity || ! $activity->causer) {
            return trans('general.admin.system');
        }

        $causer = $activity->causer;

        // Check for common name fields
        if (isset($causer->name)) {
            return $causer->name;
        }

        if (isset($causer->full_name)) {
            return $causer->full_name;
        }

        if (isset($causer->email)) {
            return $causer->email;
        }

        return class_basename($causer) . ' #' . $causer->id;
    }
}
