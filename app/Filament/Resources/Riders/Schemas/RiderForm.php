<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Schemas;

use App\Enums\Document\FileFormatEnum;
use App\Enums\Rider\AccessibilityCertificationEnum;
use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Models\Company;
use App\Models\Document;
use App\Models\Rider;
use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class RiderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(trans('riders.admin.form.personal_information'))
                    ->icon('heroicon-o-user')
                    ->description(trans('riders.admin.form.personal_information_description'))
                    ->columns(3)
                    ->schema([
                        // Profile Photo - Left Column
                        SpatieMediaLibraryFileUpload::make(Rider::PROFILE_PHOTO)
                            ->label(trans('riders.admin.fields.profile_photo'))
                            ->collection(Rider::MEDIA_COLLECTION_NAME)
                            ->image()
                            ->imageEditor()
                            ->required()
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->maxSize(2048) // 2MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                            ->columnSpan(1)
                            ->avatar()
                            ->helperText(trans('riders.admin.form.profile_photo_helper')),

                        // Basic Information - Right Columns
                        TextInput::make(Rider::COLUMN_FULL_NAME)
                            ->label(trans('riders.admin.fields.full_name'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make(Rider::COLUMN_EMAIL)
                            ->label(trans('riders.admin.fields.email'))
                            ->email()
                            ->unique(table: Rider::class, column: Rider::COLUMN_EMAIL, ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make(Rider::COLUMN_PHONE_NUMBER)
                            ->label(trans('riders.admin.fields.phone_number'))
                            ->tel()
                            ->disabledOn('edit')
                            ->unique(table: Rider::class, column: Rider::COLUMN_PHONE_NUMBER, ignoreRecord: true)
                            ->prefix(defaultPrefixPhoneNumber())
                            ->telRegex('/^[0-9]{8}$/')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Select::make(Rider::COLUMN_COMPANY_ID)
                            ->label(trans('riders.admin.fields.company'))
                            ->relationship('company', Company::COLUMN_NAME)
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                    ]),

                Section::make(trans('riders.admin.form.accessibility_certifications'))
                    ->icon('heroicon-o-check-circle')
                    ->description(trans('riders.admin.form.accessibility_certifications_description'))
                    ->schema([
                        CheckboxList::make(Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS)
                            ->options(AccessibilityCertificationEnum::getOptions())
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(trans('vehicles.admin.sections.vehicle_information.title'))
                    ->icon('heroicon-o-truck')
                    ->description(trans('vehicles.admin.sections.vehicle_information.description'))
                    ->relationship('vehicle')
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire) {
                        // Store accessibility features for later
                        if (isset($data['accessibility_feature_ids'])) {
                            $livewire->accessibilityFeatureIds = $data['accessibility_feature_ids'];
                            unset($data['accessibility_feature_ids']);
                        }

                        return $data;
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $livewire) {
                        // Store accessibility features for later
                        if (isset($data['accessibility_feature_ids'])) {
                            $livewire->accessibilityFeatureIds = $data['accessibility_feature_ids'];
                            unset($data['accessibility_feature_ids']);
                        }

                        return $data;
                    })
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        Select::make(Vehicle::COLUMN_CAR_MAKE_ID)
                            ->required()
                            ->label(trans('vehicles.admin.fields.car_make'))
                            ->options(fn () => VehicleSetting::getOptionsForSelect(VehicleSetting::TYPE_CAR_MAKES))
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): int => VehicleSetting::addItem(
                                VehicleSetting::TYPE_CAR_MAKES,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->fillEditOptionActionFormUsing(fn ($state): array => VehicleSetting::findItemById($state) ?? ['name' => '', 'name_ar' => ''])
                            ->updateOptionUsing(fn (array $data, $state): int => VehicleSetting::updateItemById(
                                $state,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->columnSpan(1),

                        Select::make(Vehicle::COLUMN_CAR_MODEL_ID)
                            ->required()
                            ->label(trans('vehicles.admin.fields.car_model'))
                            ->options(fn () => VehicleSetting::getOptionsForSelect(VehicleSetting::TYPE_CAR_MODELS))
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): int => VehicleSetting::addItem(
                                VehicleSetting::TYPE_CAR_MODELS,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->fillEditOptionActionFormUsing(fn ($state): array => VehicleSetting::findItemById($state) ?? ['name' => '', 'name_ar' => ''])
                            ->updateOptionUsing(fn (array $data, $state): int => VehicleSetting::updateItemById(
                                $state,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->columnSpan(1),

                        Select::make(Vehicle::COLUMN_CAR_TYPE_ID)
                            ->required()
                            ->label(trans('vehicles.admin.fields.car_type'))
                            ->options(fn () => VehicleSetting::getOptionsForSelect(VehicleSetting::TYPE_CAR_TYPES))
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): string => VehicleSetting::addItem(
                                VehicleSetting::TYPE_CAR_TYPES,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->fillEditOptionActionFormUsing(fn ($state): array => VehicleSetting::findItemById($state) ?? ['name' => '', 'name_ar' => ''])
                            ->updateOptionUsing(fn (array $data, $state): int => VehicleSetting::updateItemById(
                                $state,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->columnSpan(1),

                        TextInput::make(Vehicle::COLUMN_PLATE_NUMBER)
                            ->required()
                            ->label(trans('vehicles.admin.fields.plate_number'))
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make(Vehicle::COLUMN_YEAR)
                            ->label(trans('vehicles.admin.fields.year'))
                            ->required()
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y') + 1)
                            ->columnSpan(1),

                        Select::make(Vehicle::COLUMN_VEHICLE_TYPE_ID)
                            ->label(trans('vehicles.admin.fields.vehicle_type'))
                            ->options(fn () => VehicleSetting::getOptionsForSelect(VehicleSetting::TYPE_VEHICLE_TYPES))
                            ->required()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): string => VehicleSetting::addItem(
                                VehicleSetting::TYPE_VEHICLE_TYPES,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->fillEditOptionActionFormUsing(fn ($state): array => VehicleSetting::findItemById($state) ?? ['name' => '', 'name_ar' => ''])
                            ->updateOptionUsing(fn (array $data, $state): int => VehicleSetting::updateItemById(
                                $state,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->columnSpan(1),

                        Select::make(Vehicle::COLUMN_CAR_COLOR_ID)
                            ->label(trans('vehicles.admin.fields.car_color'))
                            ->options(fn () => VehicleSetting::getOptionsForSelect(VehicleSetting::TYPE_CAR_COLORS))
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): string => VehicleSetting::addItem(
                                VehicleSetting::TYPE_CAR_COLORS,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->label(trans('vehicle_settings.admin.fields.name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name_ar')
                                    ->label(trans('vehicle_settings.admin.fields.name_ar'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->fillEditOptionActionFormUsing(fn ($state): array => VehicleSetting::findItemById($state) ?? ['name' => '', 'name_ar' => ''])
                            ->updateOptionUsing(fn (array $data, $state): int => VehicleSetting::updateItemById(
                                $state,
                                $data['name'],
                                $data['name_ar']
                            ))
                            ->columnSpan(1),

                        Select::make(Vehicle::COLUMN_PASSENGER_CAPACITY_ID)
                            ->label(trans('vehicles.admin.fields.passenger_capacity'))
                            ->options(fn () => VehicleSetting::getOptionsForSelect(VehicleSetting::TYPE_PASSENGER_CAPACITY, VehicleSetting::COLUMN_CAPACITY))
                            ->searchable()
                            ->required()
                            ->columnSpan(1),

                        CheckboxList::make('accessibility_feature_ids')
                            ->label(trans('vehicles.admin.fields.accessibility_features'))
                            ->options(AccessibilityRequirementsEnum::class)
                            ->columns(3)
                            ->nullable()
                            ->columnSpan(3)
                            ->afterStateHydrated(function (CheckboxList $component, $state, $livewire) {
                                $record = $livewire->getRecord();
                                if ($record && $record->vehicle) {
                                    $component->state($record->vehicle->accessibility_feature_ids);
                                }
                            }),
                    ]),

                Section::make(trans('riders.admin.form.documents'))
                    ->icon('heroicon-o-document-text')
                    ->description(trans('riders.admin.form.documents_description'))
                    ->columns(2)
                    ->schema(function (?Model $record) {
                        $fields = [];

                        // Get all enabled documents
                        $enabledDocuments = Document::query()
                            ->where(Document::COLUMN_ENABLED, true)
                            ->orderBy(Document::COLUMN_NAME)
                            ->get();

                        foreach ($enabledDocuments as $document) {
                            $acceptedFormats = $document->{Document::COLUMN_ACCEPTED_FORMATS} ?? [];
                            $mimeTypes = FileFormatEnum::toMimeTypes($acceptedFormats);

                            // Build helper text
                            $helperParts = [];
                            if ($document->{Document::COLUMN_DESCRIPTION}) {
                                $helperParts[] = $document->{Document::COLUMN_DESCRIPTION};
                            }
                            if ($document->{Document::COLUMN_VALIDITY_PERIOD}) {
                                $helperParts[] = trans('documents.admin.fields.validity_period') . ': ' . $document->{Document::COLUMN_VALIDITY_PERIOD};
                            }
                            if (! empty($acceptedFormats)) {
                                $helperParts[] = trans('documents.admin.fields.accepted_formats') . ': ' . implode(', ', array_map(fn ($format) => strtoupper($format), $acceptedFormats));
                            }
                            $helperText = ! empty($helperParts) ? implode(' • ', $helperParts) : null;

                            $isRequired = $document->{Document::COLUMN_IS_REQUIRED};

                            // Create a grid for each document
                            $fields[] = SpatieMediaLibraryFileUpload::make("documents.{$document->id}.file")
                                ->label($document->{Document::COLUMN_NAME})
                                ->statePath("documents.{$document->id}.file")
                                ->collection("document_{$document->id}")
                                ->acceptedFileTypes($mimeTypes)
                                ->maxFiles(1)
                                ->maxSize(5120) // 5MB
                                ->required($isRequired)
                                ->helperText($helperText)
                                ->downloadable()
                                ->deletable()
                                ->previewable()
                                ->openable()
                                ->columnSpan(1);
                        }

                        return $fields;
                    }),
            ]);
    }
}
