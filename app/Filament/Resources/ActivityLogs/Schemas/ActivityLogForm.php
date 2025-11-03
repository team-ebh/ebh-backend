<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ActivityLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::buildHeaderSection(),
                self::buildSubjectAndPerformerSection(),
                self::buildChangesSection(),
                self::buildBatchSection(),
            ])
            ->extraAttributes([
                'class' => '[&_.fi-section-header-icon]:!w-4 [&_.fi-section-header-icon]:!h-4',
            ]);
    }

    /**
     * Build the header section with event, log type, and date/time
     */
    protected static function buildHeaderSection(): Section
    {
        return Section::make()
            ->schema([
                Grid::make(3)
                    ->schema([
                        Placeholder::make('event_badge')
                            ->label(trans('activity_logs.admin.fields.event_type'))
                            ->content(fn ($record) => self::renderEventBadge($record)),

                        Placeholder::make('log_type')
                            ->label(trans('activity_logs.admin.fields.log_type'))
                            ->content(fn ($record) => self::renderLogType($record)),

                        Placeholder::make('created_at')
                            ->label(trans('activity_logs.admin.fields.date_time'))
                            ->content(fn ($record) => self::renderDateTime($record)),
                    ]),

                Placeholder::make('description')
                    ->label(trans('activity_logs.admin.fields.description'))
                    ->content(fn ($record) => self::renderDescription($record)),
            ])
            ->columnSpanFull();
    }

    /**
     * Build subject and performer information section
     */
    protected static function buildSubjectAndPerformerSection(): Grid
    {
        return Grid::make(2)
            ->columnSpanFull()
            ->schema([
                self::buildSubjectSection(),
                self::buildPerformerSection(),
            ]);
    }

    /**
     * Build subject information section
     */
    protected static function buildSubjectSection(): Section
    {
        return Section::make(trans('activity_logs.admin.sections.subject'))
            ->icon('heroicon-o-document-text')
            ->description(trans('activity_logs.admin.sections.subject_description'))
            ->columns(4)
            ->extraAttributes(['class' => '[&_.fi-section-header-icon]:!w-4 [&_.fi-section-header-icon]:!h-4'])
            ->schema([
                Placeholder::make('subject_model')
                    ->label(trans('activity_logs.admin.fields.subject_model'))
                    ->content(fn ($record) => self::renderSubjectModel($record)),

                Placeholder::make('subject_id')
                    ->label(trans('activity_logs.admin.fields.subject_id'))
                    ->content(fn ($record) => self::renderSubjectId($record)),

                Placeholder::make('subject_link')
                    ->label(trans('activity_logs.admin.fields.subject_link'))
                    ->columnSpan(2)
                    ->content(fn ($record) => self::renderSubjectLink($record)),
            ]);
    }

    /**
     * Build performer information section
     */
    protected static function buildPerformerSection(): Section
    {
        return Section::make(trans('activity_logs.admin.sections.performer'))
            ->icon('heroicon-o-user')
            ->description(trans('activity_logs.admin.sections.performer_description'))
            ->columns(2)
            ->extraAttributes(['class' => '[&_.fi-section-header-icon]:!w-4 [&_.fi-section-header-icon]:!h-4'])
            ->schema([
                Placeholder::make('causer_name')
                    ->label(trans('activity_logs.admin.fields.causer_name'))
                    ->content(fn ($record) => self::renderCauserName($record)),

                Placeholder::make('causer_type')
                    ->label(trans('activity_logs.admin.fields.causer_type'))
                    ->content(fn ($record) => self::renderCauserType($record)),
            ]);
    }

    /**
     * Build changes section
     */
    protected static function buildChangesSection(): Section
    {
        return Section::make(trans('activity_logs.admin.sections.changes'))
            ->columnSpanFull()
            ->icon('heroicon-o-pencil-square')
            ->description(trans('activity_logs.admin.sections.changes_description'))
            ->extraAttributes(['class' => '[&_.fi-section-header-icon]:!w-4 [&_.fi-section-header-icon]:!h-4'])
            ->schema([
                KeyValueEntry::make('changes')
                    ->label('')
                    ->keyLabel(trans('activity_logs.admin.fields.field'))
                    ->valueLabel(trans('activity_logs.admin.fields.new_value'))
                    ->state(function ($record) {
                        if (! $record || ! $record->properties || empty($record->properties)) {
                            return [];
                        }

                        $properties = $record->properties;

                        if (! isset($properties['attributes']) || ! isset($properties['old'])) {
                            return [];
                        }

                        $changes = [];

                        foreach ($properties['attributes'] as $key => $newValue) {
                            $oldValue = $properties['old'][$key] ?? null;

                            if ($oldValue === $newValue) {
                                continue;
                            }

                            $fieldName = ucwords(str_replace('_', ' ', $key));
                            $formattedOld = self::formatValueForDisplay($oldValue);
                            $formattedNew = self::formatValueForDisplay($newValue);

                            $changes[$fieldName] = sprintf(
                                '%s → %s',
                                $formattedOld,
                                $formattedNew
                            );
                        }

                        return $changes;
                    })
                    ->visible(fn ($record) => $record && $record->properties && ! empty($record->properties)),
            ])
            ->collapsible()
            ->collapsed(false)
            ->visible(fn ($record) => $record && $record->properties && ! empty($record->properties));
    }

    /**
     * Build batch information section
     */
    protected static function buildBatchSection(): Section
    {
        return Section::make(trans('activity_logs.admin.sections.batch'))
            ->icon('heroicon-o-queue-list')
            ->description(trans('activity_logs.admin.sections.batch_description'))
            ->extraAttributes(['class' => '[&_.fi-section-header-icon]:!w-4 [&_.fi-section-header-icon]:!h-4'])
            ->schema([
                Placeholder::make('batch_uuid')
                    ->label(trans('activity_logs.admin.fields.batch_uuid'))
                    ->content(fn ($record) => self::renderBatchUuid($record)),
            ])
            ->collapsible()
            ->collapsed()
            ->visible(fn ($record) => $record && $record->batch_uuid);
    }

    /**
     * Render event badge
     */
    protected static function renderEventBadge($record): string | HtmlString
    {
        if (! $record) {
            return '—';
        }

        $eventColor = self::getEventColor($record->event);
        $eventLabel = trans('activity_logs.admin.events.' . $record->event, [], null, $record->event);

        return new HtmlString(sprintf(
            '<span class="inline-flex items-center rounded-md px-3 py-1.5 text-xs font-semibold %s">
                %s
            </span>',
            $eventColor,
            e(ucfirst($eventLabel))
        ));
    }

    /**
     * Get color classes for event type
     */
    protected static function getEventColor(string $event): string
    {
        return match ($event) {
            'created' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
            'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
            'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
            'restored' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * Render log type badge
     */
    protected static function renderLogType($record): string | HtmlString
    {
        if (! $record || ! $record->log_name) {
            return '—';
        }

        return new HtmlString(sprintf(
            '<span class="inline-flex items-center rounded-md px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                %s
            </span>',
            e($record->log_name)
        ));
    }

    /**
     * Render date and time
     */
    protected static function renderDateTime($record): string | HtmlString
    {
        if (! $record) {
            return '—';
        }

        $formattedDate = $record->created_at->format(adminPanelDataFormat() . ' ' . adminPanelTimeFormat());
        $relativeTime = $record->created_at->diffForHumans();

        return new HtmlString(sprintf(
            '<div class="text-sm">
                <div class="font-semibold text-gray-900 dark:text-gray-100">%s</div>
                <div class="text-gray-500 dark:text-gray-400 mt-1">%s</div>
            </div>',
            e($formattedDate),
            e($relativeTime)
        ));
    }

    /**
     * Render description
     */
    protected static function renderDescription($record): string | HtmlString
    {
        if (! $record || ! $record->description) {
            return '—';
        }

        return new HtmlString(sprintf(
            '<div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 p-4 mt-3">
                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">%s</p>
            </div>',
            e($record->description)
        ));
    }

    /**
     * Render subject model badge
     */
    protected static function renderSubjectModel($record): string | HtmlString
    {
        if (! $record || ! $record->subject_type) {
            return '—';
        }

        $modelBasename = class_basename($record->subject_type);

        return new HtmlString(sprintf(
            '<span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                %s
            </span>',
            e($modelBasename)
        ));
    }

    /**
     * Render subject ID
     */
    protected static function renderSubjectId($record): string | HtmlString
    {
        if (! $record || ! $record->subject_id) {
            return '—';
        }

        return new HtmlString(sprintf(
            '<span class="font-mono font-semibold text-gray-900 dark:text-gray-100 text-sm">#%s</span>',
            e((string) $record->subject_id)
        ));
    }

    /**
     * Render subject link
     */
    protected static function renderSubjectLink($record): string | HtmlString
    {
        if (! $record || ! $record->subject_type || ! $record->subject_id) {
            return '—';
        }

        $modelBasename = class_basename($record->subject_type);
        $resourceName = str($modelBasename)->plural()->lower();
        $url = url('/') . '/' . $resourceName . '/' . $record->subject_id;

        return new HtmlString(sprintf(
            '<a href="%s" target="_blank"
               class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                <span>%s</span>
            </a>',
            e($url),
            e(trans('activity_logs.admin.fields.subject_link'))
        ));
    }

    /**
     * Render causer name
     */
    protected static function renderCauserName($record): string | HtmlString
    {
        if (! $record || ! $record->causer) {
            return new HtmlString(sprintf(
                '<span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    %s
                </span>',
                e(trans('activity_logs.admin.messages.system'))
            ));
        }

        $causer = $record->causer;
        $causerName = self::getCauserName($causer);

        return new HtmlString(sprintf(
            '<span class="text-sm font-semibold text-gray-900 dark:text-gray-100">%s</span>',
            e($causerName)
        ));
    }

    /**
     * Get causer name from model
     */
    protected static function getCauserName($causer): string
    {
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

    /**
     * Render causer type
     */
    protected static function renderCauserType($record): string | HtmlString
    {
        if (! $record || ! $record->causer) {
            return '—';
        }

        $causerType = class_basename($record->causer_type);

        return new HtmlString(sprintf(
            '<span class="text-sm text-gray-500 dark:text-gray-400">%s #%s</span>',
            e($causerType),
            e((string) $record->causer_id)
        ));
    }

    /**
     * Format a value for display in KeyValueEntry (simple string, no HTML)
     */
    protected static function formatValueForDisplay($value): string
    {
        if ($value === null) {
            return trans('activity_logs.admin.messages.null');
        }

        if (is_bool($value)) {
            return $value
                ? trans('activity_logs.admin.messages.true')
                : trans('activity_logs.admin.messages.false');
        }

        if (is_array($value)) {
            if (empty($value)) {
                return trans('activity_logs.admin.messages.empty_array');
            }

            return implode(', ', array_map(function ($item) {
                return is_string($item) ? $item : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }, $value));
        }

        return (string) $value;
    }

    /**
     * Render batch UUID
     */
    protected static function renderBatchUuid($record): string | HtmlString
    {
        if (! $record || ! $record->batch_uuid) {
            return '—';
        }

        $copyText = trans('activity_logs.admin.messages.copy');

        return new HtmlString(sprintf(
            '<div class="flex items-center gap-3">
                <code class="text-xs font-mono bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded border border-gray-200 dark:border-gray-700">%s</code>
                <button onclick="navigator.clipboard.writeText(\'%s\')"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                    <span>%s</span>
                </button>
            </div>',
            e($record->batch_uuid),
            e($record->batch_uuid),
            e($copyText)
        ));
    }
}
