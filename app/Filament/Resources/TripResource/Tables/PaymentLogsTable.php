<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\Tables;

use App\Enums\Currency\CurrencyEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('payment_number')
                    ->label(trans('trips.admin.payment_logs.payment_number'))
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),

                TextColumn::make('amount')
                    ->label(trans('trips.admin.payment_logs.amount'))
                    ->suffix(fn () => CurrencyEnum::KWD->getLabel())
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color(fn ($state) => match ($state->value) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('gateway')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->color('primary'),

                TextColumn::make('gateway_reference_id')
                    ->label(trans('trips.admin.payment_logs.gateway_reference'))
                    ->copyable()
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->gateway_reference_id)
                    ->default(trans('trips.admin.fields.na')),

                TextColumn::make('created_at')
                    ->label(trans('trips.admin.payment_logs.created'))
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),

                SelectFilter::make('gateway')
                    ->options([
                        'upayments' => 'UPayments',
                        'knet' => 'KNET',
                    ])
                    ->multiple(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}
