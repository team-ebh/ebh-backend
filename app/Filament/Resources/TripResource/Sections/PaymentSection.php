<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Sections;

use App\Enums\Payment\PaymentMethodEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;

class PaymentSection
{
    public static function make(): Section
    {
        return Section::make(trans('trips.admin.sections.payment_info'))
            ->schema([
                // Payment Summary
                Grid::make(3)
                    ->schema([
                        TextEntry::make('payment_method')
                            ->badge()
                            ->size('lg')
                            ->icon('heroicon-o-credit-card'),

                        TextEntry::make('total_price')
                            ->label(trans('trips.admin.fields.total_price'))
                            ->suffix(fn ($record) => $record->currency->getLabel())
                            ->size('lg')
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->icon('heroicon-o-banknotes'),

                        TextEntry::make('lastPayment.status')
                            ->label(trans('trips.admin.fields.payment_status'))
                            ->badge()
                            ->size('lg')
                            ->weight(FontWeight::Bold),
                    ]),

                Grid::make(3)
                    ->schema([
                        TextEntry::make('base_fare')
                            ->label(trans('trips.admin.fields.base_fare'))
                            ->state(function ($record) {
                                $baseFare = (float) $record->total_price
                                    - (float) ($record->accessibility_price ?? 0)
                                    - (float) ($record->waiting_price ?? 0);

                                return priceFormat($baseFare) . ' ' . $record->currency->value;
                            })
                            ->icon('heroicon-o-calculator')
                            ->color('primary')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('accessibility_price')
                            ->label(trans('trips.admin.fields.accessibility_fee'))
                            ->formatStateUsing(function ($record) {
                                if (is_null($record->accessibility_price)) {
                                    return '-';
                                }

                                return priceFormat($record->accessibility_price) . ' ' . $record->currency->value;
                            })
                            ->icon('heroicon-o-heart')
                            ->color('pink'),

                        TextEntry::make('waiting_price')
                            ->label(trans('trips.admin.fields.waiting_fee'))
                            ->formatStateUsing(function ($record) {
                                if (is_null($record->waiting_price)) {
                                    return '-';
                                }

                                return priceFormat($record->waiting_price) . ' ' . $record->currency->value;
                            })
                            ->icon('heroicon-o-clock')
                            ->color('orange'),
                    ]),

                // Payments Table
                Section::make(trans('trips.admin.sections.payments_list'))
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'sm' => 7,
                                ])
                                    ->schema([
                                        TextEntry::make('payment_number')
                                            ->label(trans('trips.admin.payment_logs.payment_number'))
                                            ->weight(FontWeight::Bold)
                                            ->copyable()
                                            ->icon('heroicon-o-hashtag'),

                                        TextEntry::make('amount')
                                            ->label(trans('trips.admin.payment_logs.amount'))
                                            ->money(fn ($record) => $record->currency->getLabel())
                                            ->weight(FontWeight::Bold)
                                            ->color('success'),

                                        TextEntry::make('status')
                                            ->badge(),

                                        TextEntry::make('gateway')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state->getLabel())
                                            ->color('primary'),

                                        TextEntry::make('gateway_reference_id')
                                            ->label(trans('trips.admin.payment_logs.gateway_reference_id'))
                                            ->default(trans('trips.admin.fields.na'))
                                            ->copyable()
                                            ->limit(20),

                                        TextEntry::make('created_at')
                                            ->label(trans('trips.admin.fields.created_at'))
                                            ->dateTime('M d, Y H:i')
                                            ->icon('heroicon-o-clock'),

                                        Actions::make([
                                            Action::make('viewLogs')
                                                ->label(trans('trips.admin.payment_logs.view_logs'))
                                                ->icon('heroicon-o-document-text')
                                                ->color('info')
                                                ->slideOver()
                                                ->modalWidth('7xl')
                                                ->modalHeading(fn ($record) => trans('trips.admin.payment_logs.title') . ' - #' . $record->payment_number)
                                                ->modalSubmitAction(false)
                                                ->modalCancelAction(false)
                                                ->schema([
                                                    // Payment Summary
                                                    Section::make(trans('trips.admin.payment_logs.payment_summary'))
                                                        ->schema([
                                                            Grid::make(4)
                                                                ->schema([
                                                                    TextEntry::make('payment_number')
                                                                        ->label(trans('trips.admin.payment_logs.payment_number_full'))
                                                                        ->weight(FontWeight::Bold)
                                                                        ->copyable(),

                                                                    TextEntry::make('amount')
                                                                        ->label(trans('trips.admin.payment_logs.amount'))
                                                                        ->money(fn ($record) => $record->currency->value)
                                                                        ->weight(FontWeight::Bold)
                                                                        ->color('success'),

                                                                    TextEntry::make('status')
                                                                        ->badge()
                                                                        ->formatStateUsing(fn ($state) => $state->getLabel())
                                                                        ->color(fn ($state) => match ($state->value) {
                                                                            'pending' => 'warning',
                                                                            'paid' => 'success',
                                                                            'failed' => 'danger',
                                                                            'refunded' => 'info',
                                                                            default => 'gray',
                                                                        }),

                                                                    TextEntry::make('gateway')
                                                                        ->badge()
                                                                        ->formatStateUsing(fn ($state) => $state->getLabel())
                                                                        ->color('primary'),
                                                                ]),

                                                            TextEntry::make('gateway_reference_id')
                                                                ->label(trans('trips.admin.payment_logs.gateway_reference_id'))
                                                                ->default(trans('trips.admin.fields.na'))
                                                                ->copyable()
                                                                ->visible(fn ($record) => filled($record->gateway_reference_id)),
                                                        ])
                                                        ->collapsible()
                                                        ->collapsed(false),

                                                    // HTTP Logs
                                                    Section::make(trans('trips.admin.payment_logs.http_logs'))
                                                        ->schema([
                                                            RepeatableEntry::make('logs')
                                                                ->hiddenLabel()
                                                                ->contained(false)
                                                                ->schema([
                                                                    Grid::make(5)
                                                                        ->schema([
                                                                            TextEntry::make('type')
                                                                                ->label(trans('trips.admin.payment_logs.type'))
                                                                                ->badge()
                                                                                ->formatStateUsing(fn ($state) => $state->getLabel())
                                                                                ->color(fn ($record) => $record->type->value === 'generate_link' ? 'info' : 'warning'),

                                                                            TextEntry::make('method')
                                                                                ->label(trans('trips.admin.payment_logs.method'))
                                                                                ->badge()
                                                                                ->formatStateUsing(fn ($state) => strtoupper($state))
                                                                                ->color('gray'),

                                                                            TextEntry::make('status_code')
                                                                                ->label(trans('trips.admin.payment_logs.status_code'))
                                                                                ->badge()
                                                                                ->default(trans('trips.admin.fields.na'))
                                                                                ->color(fn ($state) => match (true) {
                                                                                    $state >= 200 && $state < 300 => 'success',
                                                                                    $state >= 400 => 'danger',
                                                                                    default => 'warning',
                                                                                }),

                                                                            TextEntry::make('response_time')
                                                                                ->label(trans('trips.admin.payment_logs.response_time'))
                                                                                ->suffix('ms')
                                                                                ->default(trans('trips.admin.fields.na'))
                                                                                ->icon('heroicon-o-bolt'),

                                                                            TextEntry::make('created_at')
                                                                                ->label(trans('trips.admin.payment_logs.timestamp'))
                                                                                ->dateTime('M d, Y H:i:s')
                                                                                ->size('sm'),
                                                                        ]),

                                                                    TextEntry::make('url')
                                                                        ->label(trans('trips.admin.payment_logs.url'))
                                                                        ->copyable()
                                                                        ->limit(100)
                                                                        ->visible(fn ($record) => filled($record->url)),

                                                                    TextEntry::make('request_body')
                                                                        ->label(trans('trips.admin.payment_logs.request_body'))
                                                                        ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                                                        ->copyable()
                                                                        ->extraAttributes(['class' => 'font-mono text-xs'])
                                                                        ->visible(fn ($record) => filled($record->request_body)),

                                                                    TextEntry::make('response_body')
                                                                        ->label(trans('trips.admin.payment_logs.response_body'))
                                                                        ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                                                        ->copyable()
                                                                        ->extraAttributes(['class' => 'font-mono text-xs'])
                                                                        ->visible(fn ($record) => filled($record->response_body)),

                                                                    TextEntry::make('error')
                                                                        ->label(trans('trips.admin.payment_logs.error'))
                                                                        ->color('danger')
                                                                        ->icon('heroicon-o-x-circle')
                                                                        ->visible(fn ($record) => filled($record->error)),
                                                                ])
                                                                ->getStateUsing(fn ($record) => $record->logs()->orderBy('created_at', 'desc')->get())
                                                                ->contained(false)
                                                                ->visible(fn ($record) => $record->logs && $record->logs->isNotEmpty()),

                                                            TextEntry::make('no_logs')
                                                                ->label('')
                                                                ->state(trans('trips.admin.payment_logs.no_logs'))
                                                                ->visible(fn ($record) => ! $record->logs || $record->logs->isEmpty())
                                                                ->extraAttributes(['class' => 'text-center text-gray-500']),
                                                        ])
                                                        ->collapsible()
                                                        ->collapsed(false),
                                                ]),
                                        ]),
                                    ]),
                            ])
                            ->getStateUsing(fn ($record) => $record->payments()->orderBy('created_at', 'desc')->get())
                            ->contained(false)
                            ->visible(fn ($record) => $record->payments->isNotEmpty()),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->visible(fn ($record) => $record->payment_method === PaymentMethodEnum::KNET
                        && $record->payments->isNotEmpty()),
            ])
            ->columnSpanFull()
            ->compact();
    }
}
