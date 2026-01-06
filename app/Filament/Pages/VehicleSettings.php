<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\VehicleSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class VehicleSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.vehicle-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'car_types' => $this->getItemsForType(VehicleSetting::TYPE_CAR_TYPES),
            'car_colors' => $this->getItemsForType(VehicleSetting::TYPE_CAR_COLORS),
            'passenger_capacity' => $this->getItemsForType(VehicleSetting::TYPE_PASSENGER_CAPACITY),
            'car_makes' => $this->getItemsForType(VehicleSetting::TYPE_CAR_MAKES),
            'car_models' => $this->getItemsForType(VehicleSetting::TYPE_CAR_MODELS),
        ]);
    }

    private function getItemsForType(string $type): array
    {
        return VehicleSetting::getByType($type)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->{VehicleSetting::COLUMN_NAME},
                    'name_ar' => $item->{VehicleSetting::COLUMN_NAME_AR},
                    'capacity' => $item->{VehicleSetting::COLUMN_CAPACITY},
                ];
            })
            ->toArray();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make(trans('vehicle_settings.admin.sections.car_types.title'))
                    ->description(trans('vehicle_settings.admin.sections.car_types.description'))
                    ->icon('heroicon-o-truck')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('car_types')
                            ->label('')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->grid(2)
                            ->defaultItems(0)
                            ->addActionLabel(trans('vehicle_settings.admin.actions.add_car_type'))
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ])
                    ->columnSpanFull(),

                SchemaSection::make(trans('vehicle_settings.admin.sections.car_colors.title'))
                    ->description(trans('vehicle_settings.admin.sections.car_colors.description'))
                    ->icon('heroicon-o-paint-brush')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('car_colors')
                            ->label('')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->grid(2)
                            ->defaultItems(0)
                            ->addActionLabel(trans('vehicle_settings.admin.actions.add_car_color'))
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ])
                    ->columnSpanFull(),

                SchemaSection::make(trans('vehicle_settings.admin.sections.passenger_capacity.title'))
                    ->description(trans('vehicle_settings.admin.sections.passenger_capacity.description'))
                    ->icon('heroicon-o-users')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('passenger_capacity')
                            ->label('')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('capacity')
                                    ->label(trans('vehicle_settings.admin.fields.capacity'))
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(20),
                            ])
                            ->columns(2)
                            ->grid(2)
                            ->defaultItems(0)
                            ->addActionLabel(trans('vehicle_settings.admin.actions.add_capacity'))
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => isset($state['capacity']) ? $state['capacity'] . ' ' . trans('vehicle_settings.admin.labels.passengers') : null),
                    ])
                    ->columnSpanFull(),

                SchemaSection::make(trans('vehicle_settings.admin.sections.car_makes.title'))
                    ->description(trans('vehicle_settings.admin.sections.car_makes.description'))
                    ->icon('heroicon-o-building-office-2')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('car_makes')
                            ->label('')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->grid(2)
                            ->defaultItems(0)
                            ->addActionLabel(trans('vehicle_settings.admin.actions.add_car_make'))
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                    ])
                    ->columnSpanFull(),

                SchemaSection::make(trans('vehicle_settings.admin.sections.car_models.title'))
                    ->description(trans('vehicle_settings.admin.sections.car_models.description'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Repeater::make('car_models')
                            ->label('')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->grid(2)
                            ->defaultItems(0)
                            ->addActionLabel(trans('vehicle_settings.admin.actions.add_car_model'))
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
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
                ->label(trans('vehicle_settings.admin.actions.save_all_changes'))
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_CAR_TYPES, $data['car_types'] ?? []);
            VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_CAR_COLORS, $data['car_colors'] ?? []);
            VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_PASSENGER_CAPACITY, $data['passenger_capacity'] ?? []);
            VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_CAR_MAKES, $data['car_makes'] ?? []);
            VehicleSetting::updateOrCreateItems(VehicleSetting::TYPE_CAR_MODELS, $data['car_models'] ?? []);

            Notification::make()
                ->success()
                ->title(trans('vehicle_settings.admin.notifications.saved'))
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title(trans('vehicle_settings.admin.notifications.error'))
                ->body($e->getMessage())
                ->send();
        }
    }

    public static function getNavigationLabel(): string
    {
        return trans('vehicle_settings.admin.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.settings');
    }

    public function getTitle(): string
    {
        return trans('vehicle_settings.admin.page_title');
    }

    public function getHeading(): string
    {
        return trans('vehicle_settings.admin.page_heading');
    }
}
