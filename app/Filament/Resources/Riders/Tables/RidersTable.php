<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Tables;

use App\Enums\Rider\RiderStatusEnum;
use App\Filament\Resources\Riders\RiderResource;
use App\Models\Company;
use App\Models\Rider;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make(Rider::PROFILE_PHOTO)
                    ->label(trans('riders.admin.fields.profile_photo'))
                    ->circular()
                    ->collection(Rider::MEDIA_COLLECTION_NAME)
                    ->stacked()
                    ->searchable(false)
                    ->limitedRemainingText()
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->defaultImageUrl(getDefaultImageUrl())
                    ->checkFileExistence(false),

                TextColumn::make(Rider::COLUMN_FULL_NAME)
                    ->label(trans('riders.admin.fields.full_name'))
                    ->icon('heroicon-o-user')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make(Rider::COLUMN_EMAIL)
                    ->label(trans('riders.admin.fields.email'))
                    ->icon('heroicon-o-envelope')
                    ->searchable()
                    ->copyable(),

                TextColumn::make(Rider::COLUMN_PHONE_NUMBER)
                    ->label(trans('riders.admin.fields.phone_number'))
                    ->icon('heroicon-o-phone')
                    ->prefix(defaultPrefixPhoneNumber())
                    ->searchable(query: function ($query, string $search) {
                        // Normalize search: remove +965 prefix if present, also try with + prefix added
                        $cleanSearch = preg_replace('/^\+965/', '', $search);
                        $withPlus = str_starts_with($cleanSearch, '+') ? $cleanSearch : '+' . $cleanSearch;
                        $withoutPlus = preg_replace('/^965/', '', $search);

                        return $query->where(Rider::COLUMN_PHONE_NUMBER, 'like', "%{$cleanSearch}%")
                            ->orWhere(Rider::COLUMN_PHONE_NUMBER, 'like', "%{$withPlus}%")
                            ->orWhere(Rider::COLUMN_PHONE_NUMBER, 'like', "%{$withoutPlus}%")
                            ->orWhere(Rider::COLUMN_PHONE_NUMBER, 'like', "%{$search}%");
                    })
                    ->copyable(),

                TextColumn::make('company.' . Company::COLUMN_NAME)
                    ->label(trans('riders.admin.fields.company'))
                    ->icon('heroicon-o-building-office-2')
                    ->searchable()
                    ->sortable(),

                TextColumn::make(Rider::COLUMN_STATUS)
                    ->label(trans('riders.admin.fields.status'))
                    ->icon('heroicon-o-signal')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray'),

                TextColumn::make(Rider::COLUMN_CREATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.created_at'))
                    ->icon('heroicon-o-calendar')
                    ->description(fn ($record) => $record->created_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make(Rider::COLUMN_UPDATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.updated_at'))
                    ->icon('heroicon-o-clock')
                    ->description(fn ($record) => $record->updated_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make(Rider::COLUMN_COMPANY_ID)
                    ->label(trans('riders.admin.fields.company'))
                    ->relationship('company', Company::COLUMN_NAME)
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make(Rider::COLUMN_STATUS)
                    ->label(trans('riders.admin.fields.status'))
                    ->options([
                        RiderStatusEnum::ONLINE->value => RiderStatusEnum::ONLINE->getLabel(),
                        RiderStatusEnum::OFFLINE->value => RiderStatusEnum::OFFLINE->getLabel(),
                        RiderStatusEnum::BUSY->value => RiderStatusEnum::BUSY->getLabel(),
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Rider $record): string => RiderResource::getUrl('view', ['record' => $record])),
                EditAction::make(),
            ]);
    }
}
