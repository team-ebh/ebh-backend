<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        // Check if we're filtering for a specific record
        // Try from session first (set by mount), then from query params
        $subjectType = session('activity_log_filter_subject_type') ?? request()->query('subject_type');
        $subjectId = session('activity_log_filter_subject_id') ?? request()->query('subject_id');

        // Decode if needed
        if ($subjectType && str_contains($subjectType, '%')) {
            $subjectType = urldecode($subjectType);
        }

        $filterDescription = null;

        if ($subjectType && $subjectId) {
            $modelName = class_basename($subjectType);
            $filterDescription = trans('activity_logs.admin.table.description', [
                'model' => $modelName,
                'id' => $subjectId,
            ]);
        }

        return $table
            ->description($filterDescription)
            ->modifyQueryUsing(function ($query) use ($subjectType, $subjectId) {
                // Apply subject filter from URL parameters
                if ($subjectType && $subjectId) {
                    $query->where('subject_type', $subjectType)
                        ->where('subject_id', $subjectId);
                }
            })
            ->columns([
                TextColumn::make('log_name')
                    ->label(trans('activity_logs.admin.fields.log_type'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(trans('activity_logs.admin.fields.description'))
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                TextColumn::make('event')
                    ->label(trans('activity_logs.admin.fields.event'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => trans('activity_logs.admin.events.' . $state, [], null, $state))
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'created' => 'heroicon-o-plus-circle',
                        'updated' => 'heroicon-o-pencil',
                        'deleted' => 'heroicon-o-trash',
                        'restored' => 'heroicon-o-arrow-path',
                        default => 'heroicon-o-document-text',
                    }),

                TextColumn::make('subject_type')
                    ->label(trans('activity_logs.admin.fields.subject_model'))
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('subject_id')
                    ->label(trans('activity_logs.admin.fields.record_id'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('causer.name')
                    ->label(trans('activity_logs.admin.fields.performed_by'))
                    ->default(trans('activity_logs.admin.messages.system'))
                    ->searchable()
                    ->formatStateUsing(function ($state, Model $record) {
                        if (! $record->causer) {
                            return trans('activity_logs.admin.messages.system');
                        }

                        $causer = $record->causer;

                        // Try common name fields
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
                    }),

                TextColumn::make('created_at')
                    ->label(trans('activity_logs.admin.fields.date_time'))
                    ->dateTime(adminPanelDataFormat() . ' ' . adminPanelTimeFormat())
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label(trans('activity_logs.admin.table.filters.log_type'))
                    ->options(fn () => Activity::distinct()
                        ->pluck('log_name', 'log_name')
                        ->toArray()),

                SelectFilter::make('event')
                    ->label(trans('activity_logs.admin.table.filters.event'))
                    ->options([
                        'created' => trans('activity_logs.admin.events.created'),
                        'updated' => trans('activity_logs.admin.events.updated'),
                        'deleted' => trans('activity_logs.admin.events.deleted'),
                        'restored' => trans('activity_logs.admin.events.restored'),
                    ]),

                SelectFilter::make('subject_type')
                    ->label(trans('activity_logs.admin.table.filters.model'))
                    ->options(fn () => Activity::distinct()
                        ->pluck('subject_type')
                        ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                        ->toArray()),

            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for activity logs
            ])
            ->searchable()
            ->deferLoading()
            ->poll('60s');
    }
}
