<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Tables;

use App\Models\Company;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Company::COLUMN_NAME)
                    ->label(trans('companies.admin.fields.name'))
                    ->searchable(),
                TextColumn::make(Company::COLUMN_EMAIL)
                    ->label(trans('companies.admin.fields.email'))
                    ->searchable(),
                TextColumn::make(Company::COLUMN_PHONE_NUMBER)
                    ->label(trans('companies.admin.fields.phone_number'))
                    ->searchable(),
                TextColumn::make(Company::COLUMN_ADDRESS)
                    ->label(trans('companies.admin.fields.address'))
                    ->searchable(),
                TextColumn::make(Company::COLUMN_COMMISSION_RATE)
                    ->label(trans('companies.admin.fields.commission_rate'))
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make(Company::COLUMN_ENABLED)
                    ->label(trans('companies.admin.fields.enabled'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make(Company::COLUMN_CREATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.created_at'))
                    ->description(fn ($record) => $record->created_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Company::COLUMN_UPDATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.updated_at'))
                    ->description(fn ($record) => $record->updated_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
