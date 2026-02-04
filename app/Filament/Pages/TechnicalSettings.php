<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Setting\SettingEnum;
use App\Models\Admin;
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

class TechnicalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 98;

    protected string $view = 'filament.pages.technical-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var Admin|null $admin */
        $admin = auth()->user();

        if (! $admin) {
            return false;
        }

        return $admin->{Admin::COLUMN_EMAIL} === config('auth-credentials.admin.email');
    }

    public function mount(): void
    {
        $this->form->fill([
            // Rider Settings
            'rider_trip_request_timeout_seconds' => Setting::get(SettingEnum::RIDER_TRIP_REQUEST_TIMEOUT_SECONDS),
            'rider_location_update_interval_online' => Setting::get(SettingEnum::RIDER_LOCATION_UPDATE_INTERVAL_ONLINE),
            'rider_location_update_interval_busy' => Setting::get(SettingEnum::RIDER_LOCATION_UPDATE_INTERVAL_BUSY),
            'rider_arriving_at_poll_interval' => Setting::get(SettingEnum::RIDER_ARRIVING_AT_POLL_INTERVAL),

            // Customer Settings
            'customer_arriving_at_poll_interval' => Setting::get(SettingEnum::CUSTOMER_ARRIVING_AT_POLL_INTERVAL),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('settings_tabs')
                    ->tabs([
                        Tabs\Tab::make('rider')
                            ->label(trans('technical_settings.admin.tabs.rider'))
                            ->icon('heroicon-o-truck')
                            ->schema([
                                SchemaSection::make(trans('technical_settings.admin.sections.rider.title'))
                                    ->description(trans('technical_settings.admin.sections.rider.description'))
                                    ->schema([
                                        TextInput::make('rider_trip_request_timeout_seconds')
                                            ->label(trans('technical_settings.admin.fields.rider_trip_request_timeout_seconds'))
                                            ->helperText(trans('technical_settings.admin.helpers.rider_trip_request_timeout_seconds'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(10)
                                            ->maxValue(300)
                                            ->suffix(trans('technical_settings.admin.units.seconds')),

                                        TextInput::make('rider_location_update_interval_online')
                                            ->label(trans('technical_settings.admin.fields.rider_location_update_interval_online'))
                                            ->helperText(trans('technical_settings.admin.helpers.rider_location_update_interval_online'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(10)
                                            ->maxValue(300)
                                            ->suffix(trans('technical_settings.admin.units.seconds')),

                                        TextInput::make('rider_location_update_interval_busy')
                                            ->label(trans('technical_settings.admin.fields.rider_location_update_interval_busy'))
                                            ->helperText(trans('technical_settings.admin.helpers.rider_location_update_interval_busy'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(5)
                                            ->maxValue(120)
                                            ->suffix(trans('technical_settings.admin.units.seconds')),

                                        TextInput::make('rider_arriving_at_poll_interval')
                                            ->label(trans('technical_settings.admin.fields.rider_arriving_at_poll_interval'))
                                            ->helperText(trans('technical_settings.admin.helpers.rider_arriving_at_poll_interval'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(10)
                                            ->maxValue(300)
                                            ->suffix(trans('technical_settings.admin.units.seconds')),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('customer')
                            ->label(trans('technical_settings.admin.tabs.customer'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                SchemaSection::make(trans('technical_settings.admin.sections.customer.title'))
                                    ->description(trans('technical_settings.admin.sections.customer.description'))
                                    ->schema([
                                        TextInput::make('customer_arriving_at_poll_interval')
                                            ->label(trans('technical_settings.admin.fields.customer_arriving_at_poll_interval'))
                                            ->helperText(trans('technical_settings.admin.helpers.customer_arriving_at_poll_interval'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(10)
                                            ->maxValue(300)
                                            ->suffix(trans('technical_settings.admin.units.seconds')),
                                    ])
                                    ->columns(1),
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
                ->label(trans('technical_settings.admin.actions.save'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            // Rider Settings
            Setting::set(SettingEnum::RIDER_TRIP_REQUEST_TIMEOUT_SECONDS, (int) $data['rider_trip_request_timeout_seconds']);
            Setting::set(SettingEnum::RIDER_LOCATION_UPDATE_INTERVAL_ONLINE, (int) $data['rider_location_update_interval_online']);
            Setting::set(SettingEnum::RIDER_LOCATION_UPDATE_INTERVAL_BUSY, (int) $data['rider_location_update_interval_busy']);
            Setting::set(SettingEnum::RIDER_ARRIVING_AT_POLL_INTERVAL, (int) $data['rider_arriving_at_poll_interval']);

            // Customer Settings
            Setting::set(SettingEnum::CUSTOMER_ARRIVING_AT_POLL_INTERVAL, (int) $data['customer_arriving_at_poll_interval']);

            Notification::make()
                ->success()
                ->title(trans('technical_settings.admin.notifications.saved'))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(trans('technical_settings.admin.notifications.error'))
                ->body($e->getMessage())
                ->send();
        }
    }

    public static function getNavigationLabel(): string
    {
        return trans('technical_settings.admin.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.technical_management');
    }

    public function getTitle(): string
    {
        return trans('technical_settings.admin.page_title');
    }

    public function getHeading(): string
    {
        return trans('technical_settings.admin.page_heading');
    }
}
