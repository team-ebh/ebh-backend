<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Schemas;

use App\Models\Company;
use App\Models\Rider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RiderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make(Rider::COLUMN_FULL_NAME)
                            ->label(trans('riders.admin.fields.full_name'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255),
                        TextInput::make(Rider::COLUMN_EMAIL)
                            ->label(trans('riders.admin.fields.email'))
                            ->email()
                            ->required()
                            ->unique(table: Rider::class, column: Rider::COLUMN_EMAIL, ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make(Rider::COLUMN_PHONE_NUMBER)
                            ->label(trans('riders.admin.fields.phone_number'))
                            ->tel()
                            ->unique(table: Rider::class, column: Rider::COLUMN_PHONE_NUMBER, ignoreRecord: true)
                            ->prefix(defaultPrefixPhoneNumber())
                            ->telRegex('/^[0-9]{8}$/')
                            ->required()
                            ->maxLength(255),
                        Select::make(Rider::COLUMN_COMPANY_ID)
                            ->label(trans('riders.admin.fields.company'))
                            ->relationship('company', Company::COLUMN_NAME)
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
            ]);
    }
}
