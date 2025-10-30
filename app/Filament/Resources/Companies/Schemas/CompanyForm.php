<?php

declare(strict_types=1);

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\Company;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make(Company::COLUMN_NAME)
                            ->label(trans('companies.admin.fields.name'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255),
                        TextInput::make(Company::COLUMN_EMAIL)
                            ->label(trans('companies.admin.fields.email'))
                            ->email()
                            ->required()
                            ->unique(table: Company::class, column: Company::COLUMN_EMAIL, ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make(Company::COLUMN_PHONE_NUMBER)
                            ->label(trans('companies.admin.fields.phone_number'))
                            ->tel()
                            ->prefix(defaultPrefixPhoneNumber())
                            ->telRegex('/^[0-9]{8}$/')
                            ->required(),
                        TextInput::make(Company::COLUMN_ADDRESS)
                            ->label(trans('companies.admin.fields.address'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make(Company::COLUMN_COMMISSION_RATE)
                            ->label(trans('companies.admin.fields.commission_rate'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->default(0.00),
                        Toggle::make(Company::COLUMN_ENABLED)
                            ->inline(false)
                            ->default(true)
                            ->label(trans('companies.admin.fields.enabled'))
                            ->onColor('success')
                            ->offColor('danger')
                            ->onIcon('heroicon-m-check-circle')
                            ->offIcon('heroicon-m-x-circle'),
                    ]),
            ]);
    }
}
