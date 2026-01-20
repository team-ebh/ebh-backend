<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Setting\SettingEnum;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.app-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            // Pricing Settings
            'waiting_time_rate' => Setting::get(SettingEnum::WAITING_TIME_RATE),
            'waiting_time_interval_minutes' => Setting::get(SettingEnum::WAITING_TIME_INTERVAL_MINUTES),

            // Scheduling Settings
            'scheduled_trip_search_start_minutes' => Setting::get(SettingEnum::SCHEDULED_TRIP_SEARCH_START_MINUTES),
            'customer_min_return_time_minutes' => Setting::get(SettingEnum::CUSTOMER_MIN_RETURN_TIME_MINUTES),
            'customer_min_schedule_time_minutes' => Setting::get(SettingEnum::CUSTOMER_MIN_SCHEDULE_TIME_MINUTES),
            'customer_max_schedule_time_days' => Setting::getNullable(SettingEnum::CUSTOMER_MAX_SCHEDULE_TIME_DAYS),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('settings_tabs')
                    ->tabs([
                        Tabs\Tab::make('pricing')
                            ->label(trans('app_settings.admin.tabs.pricing'))
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                SchemaSection::make(trans('app_settings.admin.sections.pricing.title'))
                                    ->description(trans('app_settings.admin.sections.pricing.description'))
                                    ->schema([
                                        TextInput::make('waiting_time_rate')
                                            ->label(trans('app_settings.admin.fields.waiting_time_rate'))
                                            ->helperText(trans('app_settings.admin.helpers.waiting_time_rate'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(0.100)
                                            ->maxValue(50.000)
                                            ->step(0.001)
                                            ->suffix('KWD'),

                                        TextInput::make('waiting_time_interval_minutes')
                                            ->label(trans('app_settings.admin.fields.waiting_time_interval_minutes'))
                                            ->helperText(trans('app_settings.admin.helpers.waiting_time_interval_minutes'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(5)
                                            ->maxValue(120)
                                            ->suffix(trans('app_settings.admin.units.minutes')),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('scheduling')
                            ->label(trans('app_settings.admin.tabs.scheduling'))
                            ->icon('heroicon-o-clock')
                            ->schema([
                                SchemaSection::make(trans('app_settings.admin.sections.scheduling.title'))
                                    ->description(trans('app_settings.admin.sections.scheduling.description'))
                                    ->schema([
                                        TextInput::make('scheduled_trip_search_start_minutes')
                                            ->label(trans('app_settings.admin.fields.scheduled_trip_search_start_minutes'))
                                            ->helperText(trans('app_settings.admin.helpers.scheduled_trip_search_start_minutes'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(60)
                                            ->suffix(trans('app_settings.admin.units.minutes')),

                                        TextInput::make('customer_min_return_time_minutes')
                                            ->label(trans('app_settings.admin.fields.customer_min_return_time_minutes'))
                                            ->helperText(trans('app_settings.admin.helpers.customer_min_return_time_minutes'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(15)
                                            ->maxValue(480)
                                            ->suffix(trans('app_settings.admin.units.minutes')),

                                        TextInput::make('customer_min_schedule_time_minutes')
                                            ->label(trans('app_settings.admin.fields.customer_min_schedule_time_minutes'))
                                            ->helperText(trans('app_settings.admin.helpers.customer_min_schedule_time_minutes'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(5)
                                            ->maxValue(1440)
                                            ->suffix(trans('app_settings.admin.units.minutes')),

                                        TextInput::make('customer_max_schedule_time_days')
                                            ->label(trans('app_settings.admin.fields.customer_max_schedule_time_days'))
                                            ->helperText(trans('app_settings.admin.helpers.customer_max_schedule_time_days'))
                                            ->placeholder(trans('app_settings.admin.placeholders.no_limit'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(365)
                                            ->suffix(trans('app_settings.admin.units.days')),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(trans('app_settings.admin.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            // Pricing Settings
            Setting::set(SettingEnum::WAITING_TIME_RATE, (float) $data['waiting_time_rate']);
            Setting::set(SettingEnum::WAITING_TIME_INTERVAL_MINUTES, (int) $data['waiting_time_interval_minutes']);

            // Scheduling Settings
            Setting::set(SettingEnum::SCHEDULED_TRIP_SEARCH_START_MINUTES, (int) $data['scheduled_trip_search_start_minutes']);
            Setting::set(SettingEnum::CUSTOMER_MIN_RETURN_TIME_MINUTES, (int) $data['customer_min_return_time_minutes']);
            Setting::set(SettingEnum::CUSTOMER_MIN_SCHEDULE_TIME_MINUTES, (int) $data['customer_min_schedule_time_minutes']);

            // Nullable settings - remove if empty, otherwise save
            if (empty($data['customer_max_schedule_time_days'])) {
                Setting::remove(SettingEnum::CUSTOMER_MAX_SCHEDULE_TIME_DAYS);
            } else {
                Setting::set(SettingEnum::CUSTOMER_MAX_SCHEDULE_TIME_DAYS, (int) $data['customer_max_schedule_time_days']);
            }

            Notification::make()
                ->success()
                ->title(trans('app_settings.admin.notifications.saved'))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(trans('app_settings.admin.notifications.error'))
                ->body($e->getMessage())
                ->send();
        }
    }

    public static function getNavigationLabel(): string
    {
        return trans('app_settings.admin.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.settings');
    }

    public function getTitle(): string
    {
        return trans('app_settings.admin.page_title');
    }

    public function getHeading(): string
    {
        return trans('app_settings.admin.page_heading');
    }
}
