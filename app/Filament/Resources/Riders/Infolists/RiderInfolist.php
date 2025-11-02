<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Infolists;

use App\Enums\Rider\AccessibilityCertificationEnum;
use App\Models\Company;
use App\Models\Document;
use App\Models\Rider;
use App\Models\RiderDocument;
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

                Section::make(trans('riders.admin.infolist.documents'))
                    ->hidden()
                    ->icon('heroicon-o-document-text')
                    ->description(trans('riders.admin.infolist.documents_description'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('documents_list')
                            ->label('')
                            ->formatStateUsing(function ($record) {
                                // Load documents with their document relation and media
                                $riderDocuments = $record->documents()
                                    ->with(['document'])
                                    ->get();

                                if ($riderDocuments->isEmpty()) {
                                    return new HtmlString('<span class="text-gray-400 italic">' . trans('general.admin.none') . '</span>');
                                }

                                $items = [];
                                foreach ($riderDocuments as $riderDocument) {
                                    $document = $riderDocument->document;
                                    if (! $document) {
                                        continue;
                                    }

                                    // Load media for this riderDocument
                                    // Use getMedia from Spatie Media Library which queries by collection name
                                    $riderDocument->loadMissing('media');
                                    $media = $riderDocument->getMedia('rider_documents')->first();

                                    if ($media) {
                                        try {
                                            $url = $media->getUrl();
                                            $name = $document->{Document::COLUMN_NAME};
                                            $size = number_format($media->size / 1024, 2) . ' KB';
                                            $type = strtoupper(pathinfo($media->file_name, PATHINFO_EXTENSION));

                                            // Check expiry date
                                            $expiryInfo = '';
                                            if ($riderDocument->{RiderDocument::COLUMN_EXPIRES_AT}) {
                                                $expiryDate = $riderDocument->{RiderDocument::COLUMN_EXPIRES_AT};
                                                $daysUntilExpiry = now()->diffInDays($expiryDate, false);

                                                if ($daysUntilExpiry < 0) {
                                                    $expiryInfo = '<span class="text-xs text-red-600 ml-2">(' . trans('documents.admin.status.expired') . ')</span>';
                                                } elseif ($daysUntilExpiry <= 30) {
                                                    $expiryInfo = '<span class="text-xs text-orange-600 ml-2">(' . trans('documents.admin.status.expires_soon', ['days' => $daysUntilExpiry]) . ')</span>';
                                                } else {
                                                    $expiryInfo = '<span class="text-xs text-gray-500 ml-2">(' . trans('documents.admin.fields.expires_at') . ': ' . $expiryDate->format('Y-m-d') . ')</span>';
                                                }
                                            }

                                            $icon = '<svg class="w-5 h-5 inline mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                            $downloadIcon = '<svg class="w-4 h-4 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>';

                                            $items[] = "<div class='mb-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors'>
                                                <a href='{$url}' target='_blank' download class='inline-flex items-center justify-between w-full'>
                                                    <span class='inline-flex items-center text-primary-600 hover:text-primary-800 font-medium'>
                                                        {$icon}
                                                        <span>{$name}</span>
                                                        <span class='text-xs text-gray-500 ml-2'>({$type} • {$size})</span>
                                                        {$expiryInfo}
                                                    </span>
                                                    <span class='text-primary-600 hover:text-primary-800'>{$downloadIcon}</span>
                                                </a>
                                            </div>";
                                        } catch (\Exception $e) {
                                            // If URL generation fails, show document name without link
                                            $name = $document->{Document::COLUMN_NAME};
                                            $icon = '<svg class="w-5 h-5 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                            $items[] = "<div class='mb-3 p-3 bg-gray-50 rounded-lg'><span class='inline-flex items-center text-gray-500 font-medium'>{$icon}<span>{$name} <span class=\"text-xs text-gray-400\">(" . trans('documents.admin.status.file_error') . ')</span></span></span></div>';
                                        }
                                    } else {
                                        // Show document name even if no file uploaded
                                        $name = $document->{Document::COLUMN_NAME};
                                        $isRequired = $document->{Document::COLUMN_IS_REQUIRED};
                                        $requiredBadge = $isRequired ? '<span class="text-xs text-red-600 ml-2">(' . trans('documents.admin.status.required') . ')</span>' : '';
                                        $icon = '<svg class="w-5 h-5 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                        $items[] = "<div class='mb-3 p-3 bg-gray-50 rounded-lg'><span class='inline-flex items-center text-gray-500 font-medium'>{$icon}<span>{$name} <span class=\"text-xs text-gray-400\">(" . trans('documents.admin.status.no_file_uploaded') . ")</span>{$requiredBadge}</span></span></div>";
                                    }
                                }

                                return ! empty($items) ? new HtmlString(implode('', $items)) : new HtmlString('<span class="text-gray-400 italic">' . trans('general.admin.none') . '</span>');
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
