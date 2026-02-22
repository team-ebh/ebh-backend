<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingPageResource\Schemas;

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Models\OnboardingPage;
use App\Models\OnboardingPageBanner;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OnboardingPageSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(trans('onboarding-pages.admin.sections.general'))
                    ->icon('heroicon-o-device-phone-mobile')
                    ->columns(2)
                    ->schema([
                        TextInput::make(OnboardingPage::COLUMN_NAME)
                            ->label(trans('onboarding-pages.admin.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Select::make(OnboardingPage::COLUMN_APPLICATION_TYPE)
                            ->label(trans('onboarding-pages.admin.fields.application_type'))
                            ->options(OnboardingApplicationTypeEnum::class)
                            ->default(OnboardingApplicationTypeEnum::CUSTOMER->value)
                            ->native(false)
                            ->required()
                            ->columnSpan(1),

                        Toggle::make(OnboardingPage::COLUMN_ENABLED)
                            ->label(trans('onboarding-pages.admin.fields.enabled'))
                            ->hint(trans('onboarding-pages.admin.hints.enabled'))
                            ->hintColor('warning')
                            ->onColor('success')
                            ->offColor('danger')
                            ->onIcon('heroicon-m-check-circle')
                            ->offIcon('heroicon-m-x-circle')
                            ->default(false)
                            ->disabled(fn ($record) => $record?->{OnboardingPage::COLUMN_ENABLED} === true)
                            ->columnSpanFull(),
                    ]),

                Section::make(trans('onboarding-pages.admin.sections.banners'))
                    ->icon('heroicon-o-photo')
                    ->description(trans('onboarding-pages.admin.hints.banners'))
                    ->schema([
                        Repeater::make('banners')
                            ->relationship(modifyQueryUsing: fn ($query) => $query->with('media'))
                            ->label('')
                            ->orderColumn(OnboardingPageBanner::COLUMN_SORT)
                            ->schema([
                                SpatieMediaLibraryFileUpload::make(OnboardingPageBanner::IMAGE)
                                    ->label(trans('onboarding-pages.admin.fields.banner_image'))
                                    ->collection(OnboardingPageBanner::MEDIA_COLLECTION_NAME)
                                    ->image()
                                    ->imageEditor()
                                    ->required()
                                    ->maxSize(2048)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                                    ->columnSpanFull(),

                                TextInput::make(OnboardingPageBanner::COLUMN_TITLE)
                                    ->label(trans('onboarding-pages.admin.fields.banner_title'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                TextInput::make(OnboardingPageBanner::COLUMN_TITLE_AR)
                                    ->label(trans('onboarding-pages.admin.fields.banner_title_ar'))
                                    ->required()
                                    ->maxLength(255)
                                    ->extraAttributes(['dir' => 'rtl'])
                                    ->columnSpan(1),

                                TextInput::make(OnboardingPageBanner::COLUMN_SUBTITLE)
                                    ->label(trans('onboarding-pages.admin.fields.banner_subtitle'))
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpan(1),

                                TextInput::make(OnboardingPageBanner::COLUMN_SUBTITLE_AR)
                                    ->label(trans('onboarding-pages.admin.fields.banner_subtitle_ar'))
                                    ->required()
                                    ->maxLength(500)
                                    ->extraAttributes(['dir' => 'rtl'])
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->minItems(2)
                            ->maxItems(5)
                            ->addActionLabel(trans('onboarding-pages.admin.fields.banners'))
                            ->reorderableWithDragAndDrop(true),
                    ]),
            ]);
    }
}
