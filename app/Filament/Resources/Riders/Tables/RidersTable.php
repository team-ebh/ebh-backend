<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Tables;

use App\Filament\Resources\Riders\RiderResource;
use App\Models\Company;
use App\Models\Rider;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Rider::COLUMN_FULL_NAME)
                    ->label(trans('riders.admin.fields.full_name'))
                    ->searchable(),
                TextColumn::make(Rider::COLUMN_EMAIL)
                    ->label(trans('riders.admin.fields.email'))
                    ->searchable(),
                TextColumn::make(Rider::COLUMN_PHONE_NUMBER)
                    ->label(trans('riders.admin.fields.phone_number'))
                    ->searchable(query: function ($query, string $search) {
                        // Normalize search: remove +965 prefix if present, also try with + prefix added
                        $cleanSearch = preg_replace('/^\+965/', '', $search);
                        $withPlus = str_starts_with($cleanSearch, '+') ? $cleanSearch : '+' . $cleanSearch;
                        $withoutPlus = str_starts_with($cleanSearch, '+') ? substr($cleanSearch, 1) : $cleanSearch;

                        return $query->where(Rider::COLUMN_PHONE_NUMBER, 'like', "%{$cleanSearch}%")
                            ->orWhere(Rider::COLUMN_PHONE_NUMBER, 'like', "%{$withPlus}%")
                            ->orWhere(Rider::COLUMN_PHONE_NUMBER, 'like', "%{$withoutPlus}%")
                            ->orWhere(Rider::COLUMN_PHONE_NUMBER, 'like', "%{$search}%");
                    }),
                TextColumn::make('company.' . Company::COLUMN_NAME)
                    ->label(trans('riders.admin.fields.company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make(Rider::COLUMN_STATUS)
                    ->label(trans('riders.admin.fields.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make(Rider::COLUMN_CREATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.created_at'))
                    ->description(fn ($record) => $record->created_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Rider::COLUMN_UPDATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.updated_at'))
                    ->description(fn ($record) => $record->updated_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Rider $record): string => RiderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
