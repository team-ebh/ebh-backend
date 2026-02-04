<?php

declare(strict_types=1);

namespace App\Filament\Resources\SmsLogs\Tables;

use App\Enums\SMS\SmsProvidersEnum;
use App\Enums\SMS\SmsTypesEnum;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Riders\RiderResource;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\SmsLog;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SmsLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('receivable'))
            ->columns([
                TextColumn::make('receivable_type')
                    ->label(__('sms.table.receiver_type'))
                    ->formatStateUsing(fn ($state) => __('sms.receiver_types.' . strtolower(class_basename($state))))
                    ->badge()
                    ->color(fn ($state) => match (class_basename($state)) {
                        'Customer' => 'success',
                        'Rider' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('receivable.full_name')
                    ->label(__('sms.table.receiver'))
                    ->formatStateUsing(function ($record) {
                        if (! $record->receivable) {
                            return __('sms.table.deleted_user');
                        }

                        $name = $record->receivable instanceof Customer
                            ? $record->receivable->{Customer::COLUMN_FIRST_NAME} . ' ' . $record->receivable->{Customer::COLUMN_LAST_NAME}
                            : $record->receivable->{Rider::COLUMN_FULL_NAME};

                        return $name . ' (#' . $record->receivable_id . ')';
                    })
                    ->url(function ($record) {
                        if (! $record->receivable) {
                            return null;
                        }

                        return match (get_class($record->receivable)) {
                            Customer::class => CustomerResource::getUrl('view', ['record' => $record->receivable_id]),
                            Rider::class => RiderResource::getUrl('view', ['record' => $record->receivable_id]),
                            default => null,
                        };
                    }, true)
                    ->icon('heroicon-o-user')
                    ->iconColor('primary')
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make(SmsLog::COLUMN_RECIPIENT_NUMBER)
                    ->label(__('sms.table.phone_number'))
                    ->formatStateUsing(fn ($state) => '+965 ' . $state)
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make(SmsLog::COLUMN_SMS_TYPE)
                    ->label(__('sms.table.sms_type'))
                    ->badge()
                    ->formatStateUsing(fn (SmsTypesEnum $state) => __('sms.types.' . strtolower($state->name)))
                    ->color(fn (SmsTypesEnum $state) => match ($state) {
                        SmsTypesEnum::SIGNUP => 'success',
                        SmsTypesEnum::SIGNIN => 'info',
                        SmsTypesEnum::DELETE_ACCOUNT => 'danger',
                    })
                    ->sortable(),

                IconColumn::make(SmsLog::COLUMN_IS_SUCCESSFUL)
                    ->label(__('sms.table.status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make(SmsLog::COLUMN_STATUS_CODE)
                    ->label(__('sms.table.http_status'))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 400 && $state < 500 => 'warning',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make(SmsLog::COLUMN_MESSAGE)
                    ->label(__('sms.table.message'))
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make(SmsLog::COLUMN_SENT_AT)
                    ->label(__('sms.table.sent_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(SmsLog::COLUMN_CREATED_AT)
                    ->label(__('sms.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make(SmsLog::COLUMN_IS_SUCCESSFUL)
                    ->label(__('sms.filters.status'))
                    ->placeholder(__('sms.filters.all'))
                    ->trueLabel(__('sms.filters.success'))
                    ->falseLabel(__('sms.filters.failed')),

                SelectFilter::make(SmsLog::COLUMN_SMS_TYPE)
                    ->label(__('sms.filters.sms_type'))
                    ->options([
                        SmsTypesEnum::SIGNUP->value => __('sms.types.signup'),
                        SmsTypesEnum::SIGNIN->value => __('sms.types.signin'),
                        SmsTypesEnum::DELETE_ACCOUNT->value => __('sms.types.delete_account'),
                    ]),

                SelectFilter::make(SmsLog::COLUMN_SMS_PROVIDER)
                    ->label(__('sms.filters.provider'))
                    ->options([
                        SmsProvidersEnum::KWT_SMS->value => __('sms.providers.kwt_sms'),
                        SmsProvidersEnum::ROUTE_MOBILE->value => __('sms.providers.route_mobile'),
                    ]),

                SelectFilter::make('receivable_type')
                    ->label(__('sms.filters.receiver_type'))
                    ->options([
                        'App\\Models\\Customer' => __('sms.receiver_types.customer'),
                        'App\\Models\\Rider' => __('sms.receiver_types.rider'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort(SmsLog::COLUMN_SENT_AT, 'desc');
    }
}
