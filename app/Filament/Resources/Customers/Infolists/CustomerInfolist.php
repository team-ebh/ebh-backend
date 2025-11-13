<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Infolists;

use App\Enums\Customer\CustomerStatusEnum;
use App\Models\Customer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(trans('customers.admin.infolist.personal_information'))
                    ->icon('heroicon-o-user')
                    ->description(trans('customers.admin.infolist.personal_information_description'))
                    ->columns(2)
                    ->schema([
                        TextEntry::make(Customer::COLUMN_FIRST_NAME)
                            ->label(trans('customers.admin.fields.first_name'))
                            ->icon('heroicon-o-user')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->columnSpan(1),

                        TextEntry::make(Customer::COLUMN_LAST_NAME)
                            ->label(trans('customers.admin.fields.last_name'))
                            ->icon('heroicon-o-user')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->columnSpan(1),

                        TextEntry::make(Customer::COLUMN_EMAIL)
                            ->label(trans('customers.admin.fields.email'))
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->placeholder(trans('general.admin.not_provided'))
                            ->columnSpan(1),

                        TextEntry::make(Customer::COLUMN_PHONE_NUMBER)
                            ->label(trans('customers.admin.fields.phone_number'))
                            ->icon('heroicon-o-phone')
                            ->prefix(defaultPrefixPhoneNumber())
                            ->copyable()
                            ->columnSpan(1),
                    ]),

                Section::make(trans('customers.admin.infolist.status_information'))
                    ->icon('heroicon-o-signal')
                    ->description(trans('customers.admin.infolist.status_information_description'))
                    ->schema([
                        TextEntry::make(Customer::COLUMN_STATUS)
                            ->label(trans('customers.admin.fields.status'))
                            ->badge()
                            ->color(fn (CustomerStatusEnum $state): string => $state->getColor()),
                    ]),
            ]);
    }
}
