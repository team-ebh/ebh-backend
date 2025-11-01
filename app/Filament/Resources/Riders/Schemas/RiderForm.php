<?php

declare(strict_types=1);

namespace App\Filament\Resources\Riders\Schemas;

use App\Enums\Document\FileFormatEnum;
use App\Enums\Rider\AccessibilityCertificationEnum;
use App\Models\Company;
use App\Models\Document;
use App\Models\Rider;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RiderForm
{
    public static function configure(Schema $schema): Schema
    {
        $enabledDocuments = Document::query()
            ->where(Document::COLUMN_ENABLED, true)
            ->orderBy(Document::COLUMN_NAME)
            ->get();

        // Create document fields for each enabled document
        $documentFields = [];
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
            $helperText = ! empty($helperParts) ? implode(' • ', $helperParts) : null;

            $documentFields[] = SpatieMediaLibraryFileUpload::make("document_{$document->id}")
                ->label($document->{Document::COLUMN_NAME})
                ->collection("document_{$document->id}")
                ->acceptedFileTypes($mimeTypes)
                ->maxFiles(1)
                ->required($document->{Document::COLUMN_IS_REQUIRED})
                ->helperText($helperText)
                ->downloadable()
                ->deletable()
                ->columnSpanFull();
        }

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
                            ->required()
                            ->unique(table: Rider::class, column: Rider::COLUMN_EMAIL, ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make(Rider::COLUMN_PHONE_NUMBER)
                            ->label(trans('riders.admin.fields.phone_number'))
                            ->tel()
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
                            ->required()
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

                ...(! empty($documentFields) ? [
                    Section::make(trans('riders.admin.form.documents'))
                        ->icon('heroicon-o-document-text')
                        ->description(trans('riders.admin.form.documents_description'))
                        ->schema($documentFields),
                ] : []),
            ]);
    }
}
