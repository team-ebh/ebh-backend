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
                    ->searchable(query: function ($query, string $search) {
                        // Normalize search: remove +965 prefix if present, also try with + prefix added
                        $cleanSearch = preg_replace('/^\+965/', '', $search);
                        $withPlus = str_starts_with($cleanSearch, '+') ? $cleanSearch : '+' . $cleanSearch;
                        $withoutPlus = str_starts_with($cleanSearch, '+') ? substr($cleanSearch, 1) : $cleanSearch;

                        return $query->where(Admin::COLUMN_PHONE_NUMBER, 'like', "%{$cleanSearch}%")
                            ->orWhere(Admin::COLUMN_PHONE_NUMBER, 'like', "%{$withPlus}%")
                            ->orWhere(Admin::COLUMN_PHONE_NUMBER, 'like', "%{$withoutPlus}%")
                            ->orWhere(Admin::COLUMN_PHONE_NUMBER, 'like', "%{$search}%");
                    }),
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
