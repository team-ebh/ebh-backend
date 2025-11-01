<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Infolists;

use App\Models\Company;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(trans('companies.admin.infolist.basic_information'))
                    ->icon('heroicon-o-building-office-2')
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Company::COLUMN_NAME)
                            ->label(trans('companies.admin.fields.name'))
                            ->icon('heroicon-o-building-office-2')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make(Company::COLUMN_EMAIL)
                            ->label(trans('companies.admin.fields.email'))
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        TextEntry::make(Company::COLUMN_PHONE_NUMBER)
                            ->label(trans('companies.admin.fields.phone_number'))
                            ->icon('heroicon-o-phone')
                            ->prefix(defaultPrefixPhoneNumber())
                            ->copyable(),

                        TextEntry::make(Company::COLUMN_ADDRESS)
                            ->label(trans('companies.admin.fields.address'))
                            ->icon('heroicon-o-map-pin')
                            ->wrap(),
                    ]),

                Section::make(trans('companies.admin.infolist.business_information'))
                    ->icon('heroicon-o-chart-bar')
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Company::COLUMN_COMMISSION_RATE)
                            ->label(trans('companies.admin.fields.commission_rate'))
                            ->icon('heroicon-o-percent-badge')
                            ->suffix('%')
                            ->color(fn ($state) => $state >= 10 ? 'warning' : 'success'),

                        TextEntry::make('riders_count')
                            ->label(trans('companies.admin.fields.riders_count'))
                            ->icon('heroicon-o-users')
                            ->formatStateUsing(fn (Company $record): int => $record->riders()->count())
                            ->badge()
                            ->color('info'),

                        IconEntry::make(Company::COLUMN_ENABLED)
                            ->label(trans('companies.admin.fields.enabled'))
                            ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
