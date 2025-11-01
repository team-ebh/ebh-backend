<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Infolists;

use App\Models\Company;
use App\Models\Rider;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RiderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(trans('riders.admin.infolist.personal_information'))
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Rider::COLUMN_FULL_NAME)
                            ->label(trans('riders.admin.fields.full_name'))
                            ->icon('heroicon-o-user')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make(Rider::COLUMN_EMAIL)
                            ->label(trans('riders.admin.fields.email'))
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        TextEntry::make(Rider::COLUMN_PHONE_NUMBER)
                            ->label(trans('riders.admin.fields.phone_number'))
                            ->icon('heroicon-o-phone')
                            ->prefix(defaultPrefixPhoneNumber())
                            ->copyable(),

                        TextEntry::make('company.' . Company::COLUMN_NAME)
                            ->label(trans('riders.admin.fields.company'))
                            ->icon('heroicon-o-building-office-2'),
                    ]),

                Section::make(trans('riders.admin.infolist.status_information'))
                    ->icon('heroicon-o-signal')
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Rider::COLUMN_STATUS)
                            ->label(trans('riders.admin.fields.status'))
                            ->icon('heroicon-o-signal')
                            ->badge()
                            ->color(fn ($state) => $state?->getColor() ?? 'gray')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
