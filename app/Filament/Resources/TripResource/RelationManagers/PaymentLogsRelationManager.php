<?php

declare(strict_types=1);

namespace App\Filament\Resources\TripResource\RelationManagers;

use App\Filament\Resources\TripResource\Tables\PaymentLogsTable;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return trans('trips.admin.payment_logs.title');
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): BackedEnum | string | null
    {
        return 'heroicon-o-document-text';
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return PaymentLogsTable::configure($table)
            ->recordActions([
                ViewAction::make()
                    ->label(trans('trips.admin.payment_logs.view_logs'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn ($record) => trans('trips.admin.payment_logs.payment_logs_heading', ['number' => $record->payment_number]))
                    ->infolist(fn () => $this->getPaymentLogsInfolist())
                    ->modalWidth('7xl')
                    ->slideOver(),
            ]);
    }

    protected function getPaymentLogsInfolist(): array
    {
        return [
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

            Section::make(trans('trips.admin.payment_logs.http_logs'))
                ->schema([
                    RepeatableEntry::make('logs')
                        ->label('')
                        ->schema([
                            Split::make([
                                Grid::make(4)
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
                                    ]),

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
                        ->contained(false),
                ])
                ->collapsible()
                ->collapsed(false),
        ];
    }
}
