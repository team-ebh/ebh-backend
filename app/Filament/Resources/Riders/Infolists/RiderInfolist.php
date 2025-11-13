<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Infolists;

use App\Enums\Rider\AccessibilityCertificationEnum;
use App\Enums\Trip\AccessibilityRequirementsEnum;
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

                        TextEntry::make('vehicle.vehicleType.' . VehicleSetting::COLUMN_NAME)
                            ->label(trans('vehicles.admin.fields.vehicle_type'))
                            ->icon('heroicon-o-squares-2x2')
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

                            if ($riderDocument && $riderDocument->hasMedia('rider_documents')) {
                                $media = $riderDocument->getFirstMedia('rider_documents');

                                // Check if media and URL are valid
                                if (! $media) {
                                    $fields[] = TextEntry::make("documents.{$document->id}_placeholder")
                                        ->label($document->{Document::COLUMN_NAME})
                                        ->state(trans('documents.admin.status.no_file_uploaded'))
                                        ->color('gray')
                                        ->icon('heroicon-o-document')
                                        ->columnSpan(2);

                                    continue;
                                }

                                $url = $media->getUrl();

                                // If URL is empty or invalid, skip this document
                                if (empty($url)) {
                                    $fields[] = TextEntry::make("documents.{$document->id}_placeholder")
                                        ->label($document->{Document::COLUMN_NAME})
                                        ->state(trans('documents.admin.status.file_not_accessible'))
                                        ->color('warning')
                                        ->icon('heroicon-o-exclamation-triangle')
                                        ->columnSpan(2);

                                    continue;
                                }

                                // Show download link for all files
                                $fields[] = TextEntry::make("documents.{$document->id}")
                                    ->label($document->{Document::COLUMN_NAME})
                                    ->state('file')
                                    ->formatStateUsing(function ($state) use ($media, $riderDocument, $url) {
                                        $fileName = $media->file_name;
                                        $size = number_format($media->size / 1024, 2) . ' KB';
                                        $extension = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION));

                                        // Add expiry info
                                        $expiryInfo = '';
                                        if ($riderDocument->{RiderDocument::COLUMN_EXPIRES_AT}) {
                                            $expiryDate = $riderDocument->{RiderDocument::COLUMN_EXPIRES_AT};
                                            $daysUntilExpiry = now()->diffInDays($expiryDate, false);

                                            if ($daysUntilExpiry < 0) {
                                                $expiryInfo = "<span class='inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-md ml-2'>
                                                    <svg class='w-3 h-3' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'></path>
                                                    </svg>
                                                    " . trans('documents.admin.status.expired') . '
                                                </span>';
                                            } elseif ($daysUntilExpiry <= 30) {
                                                $expiryInfo = "<span class='inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-orange-100 text-orange-700 rounded-md ml-2'>
                                                    <svg class='w-3 h-3' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'></path>
                                                    </svg>
                                                    " . trans('documents.admin.status.expires_soon', ['days' => $daysUntilExpiry]) . '
                                                </span>';
                                            } else {
                                                $expiryInfo = "<span class='inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-md ml-2'>
                                                    <svg class='w-3 h-3' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'></path>
                                                    </svg>
                                                    {$expiryDate->format('Y-m-d')}
                                                </span>";
                                            }
                                        }

                                        return new HtmlString("
                                            <div class='flex items-center gap-2'>
                                                <a href='{$url}' target='_blank' download class='inline-flex items-center gap-2 text-primary-600 hover:text-primary-800 font-medium'>
                                                    <svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'></path>
                                                    </svg>
                                                    <span>{$fileName}</span>
                                                    <span class='text-xs text-gray-500'>({$extension} • {$size})</span>
                                                </a>
                                                {$expiryInfo}
                                            </div>
                                        ");
                                    })
                                    ->html()
                                    ->columnSpan(2);
                            } else {
                                // Document not uploaded
                                $fields[] = TextEntry::make("documents.{$document->id}_placeholder")
                                    ->label($document->{Document::COLUMN_NAME})
                                    ->state(trans('documents.admin.status.no_file_uploaded'))
                                    ->color('gray')
                                    ->icon('heroicon-o-document')
                                    ->columnSpan(2);
                            }
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

                        TextEntry::make(Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS)
                            ->label(trans('riders.admin.fields.accessibility_certifications.label'))
                            ->icon('heroicon-o-check-circle')
                            ->listWithLineBreaks()
                            ->formatStateUsing(function ($state) {
                                return AccessibilityCertificationEnum::from($state)->getLabel();
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
