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
