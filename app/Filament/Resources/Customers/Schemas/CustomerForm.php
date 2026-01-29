<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\Customer\CustomerStatusEnum;
use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(trans('customers.admin.form.personal_information'))
                    ->icon('heroicon-o-user')
                    ->description(trans('customers.admin.form.personal_information_description'))
                    ->columns(2)
                    ->schema([
                        TextInput::make(Customer::COLUMN_FIRST_NAME)
                            ->label(trans('customers.admin.fields.first_name'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make(Customer::COLUMN_LAST_NAME)
                            ->label(trans('customers.admin.fields.last_name'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make(Customer::COLUMN_EMAIL)
                            ->label(trans('customers.admin.fields.email'))
                            ->email()
                            ->nullable()
                            ->unique(table: Customer::class, column: Customer::COLUMN_EMAIL, ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make(Customer::COLUMN_PHONE_NUMBER)
                            ->label(trans('customers.admin.fields.phone_number'))
                            ->tel()
                            ->disabledOn('edit')
                            ->unique(table: Customer::class, column: Customer::COLUMN_PHONE_NUMBER, ignoreRecord: true)
                            ->prefix(defaultPrefixPhoneNumber())
                            ->telRegex('/^[0-9]{8}$/')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Select::make(Customer::COLUMN_STATUS)
                            ->label(trans('customers.admin.fields.status'))
                            ->options(CustomerStatusEnum::class)
                            ->native(false)
                            ->required()
                            ->default(CustomerStatusEnum::ACTIVE)
                            ->columnSpan(1),
                    ]),
            ]);
    }
}
