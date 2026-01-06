<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Infolists;

use App\Enums\Rider\AccessibilityCertificationEnum;
use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Company;
use App\Models\Document;
use App\Models\Rider;
use App\Models\RiderDocument;
use App\Models\Vehicle;
use App\Models\VehicleSetting;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RiderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(trans('riders.admin.infolist.personal_information'))
                    ->icon('heroicon-o-user')
                    ->description(trans('riders.admin.infolist.personal_information_description'))
                    ->columns(3)
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('profile_photo_image')
                            ->label(trans('riders.admin.fields.profile_photo'))
                            ->formatStateUsing(function ($record) {
                                $media = $record->firstMedia(Rider::PROFILE_PHOTO);

                                return $media?->getUrl();
                            })
                            ->collection(Rider::MEDIA_COLLECTION_NAME)
                            ->circular()
                            ->columnSpan(1)
                            ->height('200px')
                            ->width('200px')
                            ->defaultImageUrl(null),

                        TextEntry::make(Rider::COLUMN_FULL_NAME)
                            ->label(trans('riders.admin.fields.full_name'))
                            ->icon('heroicon-o-user')
                            ->weight('bold')
                            ->size('lg')
                            ->columnSpan(2),

                        TextEntry::make(Rider::COLUMN_EMAIL)
                            ->label(trans('riders.admin.fields.email'))
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->columnSpan(1),

                        TextEntry::make(Rider::COLUMN_PHONE_NUMBER)
                            ->label(trans('riders.admin.fields.phone_number'))
                            ->icon('heroicon-o-phone')
                            ->prefix(defaultPrefixPhoneNumber())
                            ->copyable()
                            ->columnSpan(1),

                        TextEntry::make('company.' . Company::COLUMN_NAME)
                            ->label(trans('riders.admin.fields.company'))
                            ->icon('heroicon-o-building-office-2')
                            ->columnSpan(1),
                    ]),

                Section::make(trans('riders.admin.infolist.vehicle_information'))
                    ->icon('heroicon-o-truck')
                    ->description(trans('riders.admin.infolist.vehicle_information_description'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('vehicle.' . Vehicle::COLUMN_PLATE_NUMBER)
                            ->label(trans('vehicles.admin.fields.plate_number'))
                            ->icon('heroicon-o-identification')
                            ->weight('bold')
                            ->placeholder('-'),

                        TextEntry::make('vehicle.' . Vehicle::COLUMN_YEAR)
                            ->label(trans('vehicles.admin.fields.year'))
                            ->icon('heroicon-o-calendar')
                            ->placeholder('-'),

                        TextEntry::make('vehicle.carMake.' . VehicleSetting::COLUMN_NAME)
                            ->label(trans('vehicles.admin.fields.car_make'))
                            ->icon('heroicon-o-building-office-2')
                            ->placeholder('-'),

                        TextEntry::make('vehicle.carModel.' . VehicleSetting::COLUMN_NAME)
                            ->label(trans('vehicles.admin.fields.car_model'))
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->placeholder('-'),

                        TextEntry::make('vehicle.carType.' . VehicleSetting::COLUMN_NAME)
                            ->label(trans('vehicles.admin.fields.car_type'))
                            ->icon('heroicon-o-truck')
                            ->placeholder('-'),

                        TextEntry::make('vehicle.carColor.' . VehicleSetting::COLUMN_NAME)
                            ->label(trans('vehicles.admin.fields.car_color'))
                            ->icon('heroicon-o-paint-brush')
                            ->badge()
                            ->placeholder('-'),

                        TextEntry::make('vehicle.' . Vehicle::COLUMN_VEHICLE_TYPE_ID)
                            ->label(trans('vehicles.admin.fields.vehicle_type'))
                            ->icon('heroicon-o-squares-2x2')
                            ->formatStateUsing(fn ($state) => $state ? TripVehicleTypeEnum::tryFrom($state)?->getLabel() : null)
                            ->placeholder('-'),

                        TextEntry::make('vehicle.passengerCapacity.' . VehicleSetting::COLUMN_CAPACITY)
                            ->label(trans('vehicles.admin.fields.passenger_capacity'))
                            ->icon('heroicon-o-users')
                            ->suffix(' ' . trans('vehicle_settings.admin.labels.passengers'))
                            ->placeholder('-'),

                        TextEntry::make('vehicle.accessibility_feature_ids')
                            ->label(trans('vehicles.admin.fields.accessibility_features'))
                            ->icon('heroicon-o-heart')
                            ->listWithLineBreaks()
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return null;
                                }

                                return collect($state)
                                    ->map(fn ($value) => AccessibilityRequirementsEnum::tryFrom($value)?->getLabel() ?? $value)
                                    ->join(', ');
                            })
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Section::make(trans('riders.admin.infolist.documents'))
                    ->icon('heroicon-o-document-text')
                    ->description(trans('riders.admin.infolist.documents_description'))
                    ->columns(2)
                    ->schema(function ($record) {
                        $fields = [];

                        // Get all enabled documents
                        $enabledDocuments = Document::query()
                            ->orderBy(Document::COLUMN_NAME)
                            ->get();

                        // Get rider documents
                        $riderDocuments = $record->documents()
                            ->with(['document', 'media'])
                            ->get()
                            ->keyBy('document_id');

                        foreach ($enabledDocuments as $document) {
                            $riderDocument = $riderDocuments->get($document->id);

                            // Only show if RiderDocument exists and has media
                            if ($riderDocument && $riderDocument->hasMedia('rider_documents')) {
                                $media = $riderDocument->getFirstMedia('rider_documents');

                                // Check if media exists and URL is valid
                                if ($media && ! empty($media->getUrl())) {
                                    $url = $media->getUrl();

                                    // Show download link for the file
                                    $fields[] = TextEntry::make("documents.{$document->id}")
                                        ->label($document->{Document::COLUMN_NAME})
                                        ->state('file')
                                        ->formatStateUsing(function ($state) use ($media, $url) {
                                            $fileName = $media->file_name;
                                            $size = number_format($media->size / 1024, 2) . ' KB';
                                            $extension = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION));

                                            return new HtmlString("
                                                <div class='flex items-center gap-2'>
                                                    <a href='{$url}' target='_blank' download class='inline-flex items-center gap-2 text-primary-600 hover:text-primary-800 font-medium'>
                                                        <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'></path>
                                                        </svg>
                                                        <span>{$fileName}</span>
                                                        <span class='text-xs text-gray-500'>({$extension} • {$size})</span>
                                                    </a>
                                                </div>
                                            ");
                                        })
                                        ->html()
                                        ->columnSpan(2);
                                }
                            }
                            // If no file exists, don't show anything (skip)
                        }

                        return $fields;
                    }),

                Section::make(trans('riders.admin.infolist.status_information'))
                    ->icon('heroicon-o-signal')
                    ->description(trans('riders.admin.infolist.status_information_description'))
                    ->schema([
                        TextEntry::make(Rider::COLUMN_STATUS)
                            ->label(trans('riders.admin.fields.status'))
                            ->icon('heroicon-o-signal')
                            ->badge()
                            ->color(fn ($state) => $state?->getColor() ?? 'gray')
                            ->columnSpan(1),

                        TextEntry::make('accessibilityCertifications.certification_type')
                            ->label(trans('riders.admin.fields.accessibility_certifications.label'))
                            ->icon('heroicon-o-check-circle')
                            ->listWithLineBreaks()
                            ->formatStateUsing(function ($state) {
                                if (! $state) {
                                    return '-';
                                }

                                // $state is already cast to enum by the model
                                if ($state instanceof AccessibilityCertificationEnum) {
                                    return $state->getLabel();
                                }

                                // Fallback for string values
                                return AccessibilityCertificationEnum::from($state)->getLabel();
                            })
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
