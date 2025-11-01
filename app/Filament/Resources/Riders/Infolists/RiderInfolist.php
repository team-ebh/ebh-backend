<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Infolists;

use App\Enums\Rider\AccessibilityCertificationEnum;
use App\Models\Company;
use App\Models\Document;
use App\Models\Rider;
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
                    ->columns(2)
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
                            ->formatStateUsing(function ($record) {
                                $state = $record->{Rider::COLUMN_ACCESSIBILITY_CERTIFICATIONS};

                                // Handle null, empty string, or empty array
                                if (empty($state) || (is_array($state) && count($state) === 0)) {
                                    return new HtmlString('<span class="text-gray-400 italic">' . trans('general.admin.none') . '</span>');
                                }

                                // Ensure it's an array
                                if (! is_array($state)) {
                                    $state = json_decode($state, true) ?? [];
                                }

                                if (empty($state) || ! is_array($state)) {
                                    return new HtmlString('<span class="text-gray-400 italic">' . trans('general.admin.none') . '</span>');
                                }

                                $badges = [];
                                foreach ($state as $certification) {
                                    $enumCase = AccessibilityCertificationEnum::tryFrom($certification);
                                    if ($enumCase) {
                                        $badges[] = '<span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-info-50 text-info-700 ring-info-600/20">' . htmlspecialchars($enumCase->getLabel()) . '</span>';
                                    }
                                }

                                return ! empty($badges) ? new HtmlString(implode(' ', $badges)) : new HtmlString('<span class="text-gray-400 italic">' . trans('general.admin.none') . '</span>');
                            })
                            ->html()
                            ->columnSpan(1),
                    ]),

                Section::make(trans('riders.admin.infolist.documents'))
                    ->icon('heroicon-o-document-text')
                    ->description(trans('riders.admin.infolist.documents_description'))
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
                                    $media = $riderDocument->getMedia('documents')->first();

                                    if ($media) {
                                        try {
                                            $url = $media->getUrl();
                                            $name = $document->{Document::COLUMN_NAME};
                                            $icon = '<svg class="w-5 h-5 inline mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                            $items[] = "<div class='mb-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors'><a href='{$url}' target='_blank' class='inline-flex items-center text-primary-600 hover:text-primary-800 hover:underline font-medium'>{$icon}<span>{$name}</span></a></div>";
                                        } catch (\Exception $e) {
                                            // If URL generation fails, show document name without link
                                            $name = $document->{Document::COLUMN_NAME};
                                            $icon = '<svg class="w-5 h-5 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                            $items[] = "<div class='mb-3 p-3 bg-gray-50 rounded-lg'><span class='inline-flex items-center text-gray-500 font-medium'>{$icon}<span>{$name} <span class=\"text-xs text-gray-400\">(File error)</span></span></span></div>";
                                        }
                                    } else {
                                        // Show document name even if no file uploaded
                                        $name = $document->{Document::COLUMN_NAME};
                                        $icon = '<svg class="w-5 h-5 inline mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                                        $items[] = "<div class='mb-3 p-3 bg-gray-50 rounded-lg'><span class='inline-flex items-center text-gray-500 font-medium'>{$icon}<span>{$name} <span class=\"text-xs text-gray-400\">(No file uploaded)</span></span></span></div>";
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
