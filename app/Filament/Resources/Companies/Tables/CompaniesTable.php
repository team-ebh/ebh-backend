<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Tables;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->icon('heroicon-o-building-office-2')
                    ->weight('bold'),

                TextColumn::make(Company::COLUMN_EMAIL)
                    ->label(trans('companies.admin.fields.email'))
                    ->icon('heroicon-o-envelope')
                    ->copyable(),

                TextColumn::make(Company::COLUMN_PHONE_NUMBER)
                    ->label(trans('companies.admin.fields.phone_number'))
                    ->icon('heroicon-o-phone')
                    ->prefix(defaultPrefixPhoneNumber())
                    ->searchable(query: function ($query, string $search) {
                        // Normalize search: remove +965 prefix if present, also try with + prefix added
                        $cleanSearch = preg_replace('/^\+965/', '', $search);
                        $withPlus = str_starts_with($cleanSearch, '+') ? $cleanSearch : '+' . $cleanSearch;
                        $withoutPlus = preg_replace('/^965/', '', $search);

                        return $query->where(Company::COLUMN_PHONE_NUMBER, "%{$cleanSearch}%")
                            ->orWhereLike(Company::COLUMN_PHONE_NUMBER, "%{$withPlus}%")
                            ->orWhereLike(Company::COLUMN_PHONE_NUMBER, "%{$withoutPlus}%")
                            ->orWhereLike(Company::COLUMN_PHONE_NUMBER, "%{$search}%");
                    })
                    ->copyable(),

                TextColumn::make(Company::COLUMN_ADDRESS)
                    ->label(trans('companies.admin.fields.address'))
                    ->icon('heroicon-o-map-pin')
                    ->wrap(),

                TextColumn::make('riders_count')
                    ->label(trans('companies.admin.fields.riders_count'))
                    ->icon('heroicon-o-users')
                    ->counts('riders')
                    ->searchable(false)
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make(Company::COLUMN_COMMISSION_RATE)
                    ->label(trans('companies.admin.fields.commission_rate'))
                    ->icon('heroicon-o-percent-badge')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => $state >= 10 ? 'warning' : 'success'),

                IconColumn::make(Company::COLUMN_ENABLED)
                    ->label(trans('companies.admin.fields.enabled'))
                    ->boolean()
                    ->sortable()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make(Company::COLUMN_CREATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.created_at'))
                    ->description(fn ($record) => $record->created_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make(Company::COLUMN_UPDATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.updated_at'))
                    ->description(fn ($record) => $record->updated_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Company $record): string => CompanyResource::getUrl('view', ['record' => $record])),
                EditAction::make(),
            ]);
    }
}
