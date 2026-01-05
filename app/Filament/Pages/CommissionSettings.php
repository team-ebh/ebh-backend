<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Filament\Pages\Widgets\CommissionStatsWidget;
use App\Models\Company;
use App\Models\Setting;
use App\Models\Trip;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class CommissionSettings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.commission-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'default_commission_rate' => Setting::getDefaultCommissionRate(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make(trans('commission_settings.admin.sections.default_rate.title'))
                    ->description(trans('commission_settings.admin.sections.default_rate.description'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        TextInput::make('default_commission_rate')
                            ->label(trans('commission_settings.admin.fields.default_rate'))
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText(trans('commission_settings.admin.fields.default_rate_helper')),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(trans('commission_settings.admin.sections.company_rates.title'))
            ->description(trans('commission_settings.admin.sections.company_rates.description'))
            ->query(Company::query())
            ->defaultSort(Company::COLUMN_NAME)
            ->columns([
                TextColumn::make(Company::COLUMN_NAME)
                    ->label(trans('commission_settings.admin.table.company_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make(Company::COLUMN_COMMISSION_RATE)
                    ->label(trans('commission_settings.admin.table.rate'))
                    ->suffix('%')
                    ->sortable(),

                IconColumn::make(Company::COLUMN_IS_CUSTOM_RATE)
                    ->label(trans('commission_settings.admin.table.rate_type'))
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->falseIcon('heroicon-o-cog-6-tooth')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (Company $record): string => $record->hasCustomRate()
                        ? trans('commission_settings.admin.table.custom')
                        : trans('commission_settings.admin.table.default')),

                TextColumn::make('trips_count')
                    ->label(trans('commission_settings.admin.table.trips'))
                    ->state(fn (Company $record): string => Number::format($this->getCompanyTripsCount($record)) . ' ' . trans('commission_settings.admin.table.trips_label'))
                    ->color('gray'),

                TextColumn::make('revenue')
                    ->label(trans('commission_settings.admin.table.revenue'))
                    ->state(fn (Company $record): string => CurrencyEnum::KWD->getLabel() . ' ' . priceFormat($this->getCompanyRevenue($record)))
                    ->color('success'),

                TextColumn::make('commission')
                    ->label(trans('commission_settings.admin.table.commission'))
                    ->state(fn (Company $record): string => CurrencyEnum::KWD->getLabel() . ' ' . priceFormat($this->getCompanyCommission($record)))
                    ->color('primary')
                    ->weight('bold'),
            ])
            ->recordActions([
                Action::make('edit_rate')
                    ->label(trans('commission_settings.admin.actions.edit_rate'))
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Radio::make('rate_type')
                            ->label(trans('commission_settings.admin.fields.rate_type'))
                            ->options([
                                'default' => trans('commission_settings.admin.fields.rate_type_default', ['rate' => Setting::getDefaultCommissionRate() . '%']),
                                'custom' => trans('commission_settings.admin.fields.rate_type_custom'),
                            ])
                            ->default(fn (Company $record): string => $record->hasCustomRate() ? 'custom' : 'default')
                            ->required()
                            ->live(),

                        TextInput::make('commission_rate')
                            ->label(trans('commission_settings.admin.fields.commission_rate'))
                            ->numeric()
                            ->required(fn (Get $get): bool => $get('rate_type') === 'custom')
                            ->visible(fn (Get $get): bool => $get('rate_type') === 'custom')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->default(fn (Company $record): float => (float) $record->{Company::COLUMN_COMMISSION_RATE})
                            ->rules(['numeric', 'min:0', 'max:100', 'regex:/^\d+(\.\d{1,2})?$/']),
                    ])
                    ->action(function (Company $record, array $data): void {
                        if ($data['rate_type'] === 'default') {
                            $record->update([
                                Company::COLUMN_COMMISSION_RATE => Setting::getDefaultCommissionRate(),
                                Company::COLUMN_IS_CUSTOM_RATE => false,
                            ]);
                        } else {
                            $record->update([
                                Company::COLUMN_COMMISSION_RATE => $data['commission_rate'],
                                Company::COLUMN_IS_CUSTOM_RATE => true,
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title(trans('commission_settings.admin.notifications.rate_updated'))
                            ->send();
                    }),
            ])
            ->emptyStateHeading(trans('commission_settings.admin.table.empty_heading'))
            ->emptyStateDescription(trans('commission_settings.admin.table.empty_description'))
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save_default_rate')
                ->label(trans('commission_settings.admin.actions.save_default_rate'))
                ->icon('heroicon-o-check')
                ->action('saveDefaultRate'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CommissionStatsWidget::class,
        ];
    }

    public function saveDefaultRate(): void
    {
        $data = $this->form->getState();

        try {
            Setting::setDefaultCommissionRate((float) $data['default_commission_rate']);

            // Update all companies using default rate (using each to trigger activity log)
            Company::query()
                ->where(Company::COLUMN_IS_CUSTOM_RATE, false)
                ->each(function (Company $company) use ($data): void {
                    $company->update([
                        Company::COLUMN_COMMISSION_RATE => $data['default_commission_rate'],
                    ]);
                });

            Notification::make()
                ->success()
                ->title(trans('commission_settings.admin.notifications.default_rate_saved'))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(trans('commission_settings.admin.notifications.error'))
                ->body($e->getMessage())
                ->send();
        }
    }

    private function getCompanyTripsCount(Company $company): int
    {
        return Trip::query()
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::COMPLETED)
            ->whereHas('rider', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->count();
    }

    private function getCompanyRevenue(Company $company): float
    {
        return (float) (Trip::query()
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::COMPLETED)
            ->whereHas('rider', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->sum(Trip::COLUMN_TOTAL_PRICE) ?? 0);
    }

    private function getCompanyCommission(Company $company): float
    {
        return (float) (Trip::query()
            ->where(Trip::COLUMN_STATUS, TripStatusEnum::COMPLETED)
            ->whereHas('rider', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->sum(Trip::COLUMN_COMMISSION_AMOUNT) ?? 0);
    }

    public static function getNavigationLabel(): string
    {
        return trans('commission_settings.admin.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.settings');
    }

    public function getTitle(): string
    {
        return trans('commission_settings.admin.page_title');
    }

    public function getHeading(): string
    {
        return trans('commission_settings.admin.page_heading');
    }
}
