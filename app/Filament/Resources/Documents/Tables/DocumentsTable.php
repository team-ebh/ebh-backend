<?php

declare(strict_types=1);

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Document;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Document::COLUMN_NAME)
                    ->label(trans('documents.admin.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make(Document::COLUMN_DESCRIPTION)
                    ->label(trans('documents.admin.fields.description'))
                    ->wrap()
                    ->limit(50)
                    ->searchable(),

                TextColumn::make(Document::COLUMN_VALIDITY_PERIOD)
                    ->label(trans('documents.admin.fields.validity_period'))
                    ->searchable(),

                TextColumn::make(Document::COLUMN_ACCEPTED_FORMATS)
                    ->label(trans('documents.admin.fields.accepted_formats'))
                    ->searchable(false)
                    ->formatStateUsing(function ($state) {
                        // Handle null or empty
                        if (empty($state)) {
                            return new \Illuminate\Support\HtmlString('<span class="text-gray-400">-</span>');
                        }

                        // Ensure $state is an array
                        if (! is_array($state)) {
                            if (is_string($state)) {
                                $decoded = json_decode($state, true);
                                $state = json_last_error() === JSON_ERROR_NONE ? $decoded : [$state];
                            } else {
                                $state = (array) $state;
                            }
                        }

                        if (! is_array($state) || empty($state)) {
                            return new \Illuminate\Support\HtmlString('<span class="text-gray-400">-</span>');
                        }

                        // Create badges for each format
                        $badges = [];
                        foreach ($state as $format) {
                            $format = trim((string) $format);
                            if (! empty($format)) {
                                $badges[] = '<span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-info-50 text-info-700 ring-info-600/20">' . htmlspecialchars($format) . '</span>';
                            }
                        }

                        if (empty($badges)) {
                            return new \Illuminate\Support\HtmlString('<span class="text-gray-400">-</span>');
                        }

                        return new \Illuminate\Support\HtmlString(implode(' ', $badges));
                    })
                    ->html()
                    ->wrap(),

                IconColumn::make(Document::COLUMN_IS_REQUIRED)
                    ->label(trans('documents.admin.fields.is_required'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                IconColumn::make(Document::COLUMN_ENABLED)
                    ->label(trans('documents.admin.fields.enabled'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make(Document::COLUMN_CREATED_AT)
                    ->label(trans('general.admin.created_at'))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
