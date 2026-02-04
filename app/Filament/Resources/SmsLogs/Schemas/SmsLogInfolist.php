<?php

declare(strict_types=1);

namespace App\Filament\Resources\SmsLogs\Schemas;

use App\Enums\SMS\SmsProvidersEnum;
use App\Enums\SMS\SmsTypesEnum;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Riders\RiderResource;
use App\Models\Customer;
use App\Models\Rider;
use App\Models\SmsLog;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class SmsLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->columns(2)
                            ->schema([
                                TextEntry::make(SmsLog::COLUMN_RECIPIENT_NUMBER)
                                    ->label(__('sms.infolist.phone_number'))
                                    ->formatStateUsing(fn ($state) => '+965 ' . $state)
                                    ->icon('heroicon-o-phone')
                                    ->iconColor('primary')
                                    ->weight(FontWeight::SemiBold)
                                    ->copyable()
                                    ->copyMessage(__('sms.infolist.phone_copied'))
                                    ->size('lg'),

                                IconEntry::make(SmsLog::COLUMN_IS_SUCCESSFUL)
                                    ->label(__('sms.infolist.delivery_status'))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                TextEntry::make(SmsLog::COLUMN_SMS_TYPE)
                                    ->label(__('sms.infolist.type'))
                                    ->badge()
                                    ->formatStateUsing(fn (SmsTypesEnum $state) => __('sms.types.' . strtolower($state->name)))
                                    ->color(fn (SmsTypesEnum $state) => match ($state) {
                                        SmsTypesEnum::SIGNUP => 'success',
                                        SmsTypesEnum::SIGNIN => 'info',
                                        SmsTypesEnum::DELETE_ACCOUNT => 'danger',
                                    }),

                                TextEntry::make('receivable_type')
                                    ->label(__('sms.infolist.receiver'))
                                    ->formatStateUsing(function ($state, $record) {
                                        if (! $record->receivable) {
                                            return __('sms.receiver_types.' . strtolower(class_basename($state))) . ' #' . $record->receivable_id . ' (' . __('sms.infolist.deleted') . ')';
                                        }

                                        $name = $record->receivable instanceof Customer
                                            ? $record->receivable->{Customer::COLUMN_FIRST_NAME} . ' ' . $record->receivable->{Customer::COLUMN_LAST_NAME}
                                            : $record->receivable->{Rider::COLUMN_FULL_NAME};

                                        return __('sms.receiver_types.' . strtolower(class_basename($state))) . ': ' . $name . ' (#' . $record->receivable_id . ')';
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
                                    ->badge()
                                    ->icon('heroicon-o-user')
                                    ->color(fn ($state, $record) => ! $record->receivable ? 'gray' : match (class_basename($state)) {
                                        'Customer' => 'success',
                                        'Rider' => 'info',
                                        default => 'gray',
                                    }),

                                TextEntry::make(SmsLog::COLUMN_SMS_PROVIDER)
                                    ->label(__('sms.infolist.provider'))
                                    ->formatStateUsing(fn (SmsProvidersEnum $state) => __('sms.providers.' . $state->value))
                                    ->badge()
                                    ->icon('heroicon-o-server'),

                                TextEntry::make(SmsLog::COLUMN_SENT_AT)
                                    ->label(__('sms.infolist.sent_at'))
                                    ->dateTime('M j, Y - H:i:s')
                                    ->icon('heroicon-o-clock'),

                                TextEntry::make(SmsLog::COLUMN_STATUS_CODE)
                                    ->label(__('sms.infolist.http_status'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ?? 'N/A')
                                    ->color(fn ($state) => match (true) {
                                        $state >= 200 && $state < 300 => 'success',
                                        $state >= 400 && $state < 500 => 'warning',
                                        $state >= 500 => 'danger',
                                        default => 'gray',
                                    }),
                            ]),

                        Section::make(__('sms.infolist.sms_message'))
                            ->schema([
                                TextEntry::make(SmsLog::COLUMN_MESSAGE)
                                    ->label('')
                                    ->formatStateUsing(function ($state) {
                                        if (! $state) {
                                            return __('sms.infolist.no_message');
                                        }

                                        // Hide OTP codes (4-digit numbers) for security
                                        return preg_replace('/\b\d{4}\b/', '****', $state);
                                    })
                                    ->color('gray')
                                    ->columnSpanFull(),
                            ])
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->iconColor('primary'),

                        Section::make(__('sms.infolist.technical_details'))
                            ->visible(fn () => isTechAdmin())
                            ->schema([
                                TextEntry::make(SmsLog::COLUMN_REQUEST_DATA)
                                    ->label(__('sms.infolist.request_payload'))
                                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                                    ->columnSpanFull()
                                    ->copyable()
                                    ->copyMessage(__('sms.infolist.request_copied'))
                                    ->placeholder(__('sms.infolist.no_request_data')),

                                TextEntry::make(SmsLog::COLUMN_PROVIDER_RESPONSE)
                                    ->label(__('sms.infolist.provider_response'))
                                    ->formatStateUsing(fn ($state) => is_string($state) ? $state : json_encode($state, JSON_PRETTY_PRINT))
                                    ->columnSpanFull()
                                    ->copyable()
                                    ->copyMessage(__('sms.infolist.response_copied'))
                                    ->placeholder(__('sms.infolist.no_response_data')),
                            ])
                            ->icon('heroicon-o-code-bracket'),
                    ]),
            ]);

    }
}
