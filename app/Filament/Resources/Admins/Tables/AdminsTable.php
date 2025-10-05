<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Tables;

use App\Filament\Actions\ResetPasswordAction;
use App\Models\Admin;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(Admin::COLUMN_NAME)
                    ->label(trans('admins.admin.fields.name'))
                    ->searchable(),
                TextColumn::make(Admin::COLUMN_EMAIL)
                    ->label(trans('admins.admin.fields.email'))
                    ->searchable(),
                TextColumn::make(Admin::COLUMN_PHONE_NUMBER)
                    ->label(trans('admins.admin.fields.phone_number'))
                    ->prefix(defaultPrefixPhoneNumber())
                    ->searchable(),
                IconColumn::make(Admin::COLUMN_ENABLED)
                    ->label(trans('admins.admin.fields.enabled'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make(Admin::COLUMN_CREATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.created_at'))
                    ->description(fn ($record) => $record->created_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make(Admin::COLUMN_UPDATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.updated_at'))
                    ->description(fn ($record) => $record->updated_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Admin $record): bool => $record->{Admin::COLUMN_EMAIL} !== config('auth-credentials.admin.email')),
                ResetPasswordAction::make(),
            ]);
    }
}
