<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\Customer\CustomerStatusEnum;
use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where(Customer::COLUMN_STATUS, '<>', CustomerStatusEnum::PENDING_VERIFICATION))
            ->columns([
                TextColumn::make('full_name')
                    ->label(trans('customers.admin.fields.full_name'))
                    ->icon('heroicon-o-user')
                    ->weight('bold')
                    ->searchable([Customer::COLUMN_FIRST_NAME, Customer::COLUMN_LAST_NAME]),

                TextColumn::make(Customer::COLUMN_EMAIL)
                    ->label(trans('customers.admin.fields.email'))
                    ->icon('heroicon-o-envelope')
                    ->searchable()
                    ->copyable()
                    ->placeholder(trans('general.admin.not_provided')),

                TextColumn::make(Customer::COLUMN_PHONE_NUMBER)
                    ->label(trans('customers.admin.fields.phone_number'))
                    ->icon('heroicon-o-phone')
                    ->prefix(defaultPrefixPhoneNumber())
                    ->searchable(query: function ($query, string $search) {
                        // Normalize search: remove +965 prefix if present, also try with + prefix added
                        $cleanSearch = preg_replace('/^\+965/', '', $search);
                        $withPlus = str_starts_with($cleanSearch, '+') ? $cleanSearch : '+' . $cleanSearch;
                        $withoutPlus = preg_replace('/^965/', '', $search);

                        return $query->where(Customer::COLUMN_PHONE_NUMBER, 'like', "%{$cleanSearch}%")
                            ->orWhere(Customer::COLUMN_PHONE_NUMBER, 'like', "%{$withPlus}%")
                            ->orWhere(Customer::COLUMN_PHONE_NUMBER, 'like', "%{$withoutPlus}%")
                            ->orWhere(Customer::COLUMN_PHONE_NUMBER, 'like', "%{$search}%");
                    })
                    ->copyable(),

                TextColumn::make(Customer::COLUMN_STATUS)
                    ->label(trans('customers.admin.fields.status'))
                    ->icon('heroicon-o-signal')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state) => $state?->getColor() ?? 'gray'),

                TextColumn::make(Customer::COLUMN_CREATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.created_at'))
                    ->icon('heroicon-o-calendar')
                    ->description(fn ($record) => $record->created_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make(Customer::COLUMN_UPDATED_AT)
                    ->searchable(false)
                    ->label(trans('general.admin.updated_at'))
                    ->icon('heroicon-o-clock')
                    ->description(fn ($record) => $record->updated_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make(Customer::COLUMN_STATUS)
                    ->label(trans('customers.admin.fields.status'))
                    ->options([
                        CustomerStatusEnum::ACTIVE->value => CustomerStatusEnum::ACTIVE->getLabel(),
                        CustomerStatusEnum::INACTIVE->value => CustomerStatusEnum::INACTIVE->getLabel(),
                        CustomerStatusEnum::SUSPENDED->value => CustomerStatusEnum::SUSPENDED->getLabel(),
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Customer $record): string => CustomerResource::getUrl('view', ['record' => $record])),
                EditAction::make(),
            ]);
    }
}
