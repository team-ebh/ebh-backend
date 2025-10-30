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
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Rider::COLUMN_FULL_NAME)
                            ->label(trans('riders.admin.fields.full_name')),
                        TextEntry::make(Rider::COLUMN_EMAIL)
                            ->label(trans('riders.admin.fields.email')),
                        TextEntry::make(Rider::COLUMN_PHONE_NUMBER)
                            ->label(trans('riders.admin.fields.phone_number')),
                        TextEntry::make('company.' . Company::COLUMN_NAME)
                            ->label(trans('riders.admin.fields.company')),
                        TextEntry::make(Rider::COLUMN_STATUS)
                            ->label(trans('riders.admin.fields.status'))
                            ->badge(),
                    ]),
            ]);
    }
}
