<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Tables;

use App\Models\Company;
use App\Models\Rider;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->searchable(),
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
                //                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
