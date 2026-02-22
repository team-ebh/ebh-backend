<?php

declare(strict_types=1);

namespace App\Filament\Resources\OnboardingPageResource\Tables;

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Models\OnboardingPage;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OnboardingPageTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make(OnboardingPage::COLUMN_NAME)
                    ->label(trans('onboarding-pages.admin.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make(OnboardingPage::COLUMN_APPLICATION_TYPE)
                    ->label(trans('onboarding-pages.admin.fields.application_type'))
                    ->badge()
                    ->formatStateUsing(fn (OnboardingApplicationTypeEnum $state): string => $state->getLabel())
                    ->color(fn (OnboardingApplicationTypeEnum $state): string => $state->getColor())
                    ->sortable()
                    ->searchable(false),

                TextColumn::make('banners_count')
                    ->label(trans('onboarding-pages.admin.fields.banners'))
                    ->counts('banners')
                    ->badge()
                    ->color('gray')
                    ->searchable(false),

                IconColumn::make(OnboardingPage::COLUMN_ENABLED)
                    ->label(trans('onboarding-pages.admin.fields.enabled'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make(OnboardingPage::COLUMN_CREATED_AT)
                    ->label(trans('general.admin.created_at'))
                    ->description(fn ($record) => $record->created_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->searchable(false)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make(OnboardingPage::COLUMN_UPDATED_AT)
                    ->label(trans('general.admin.updated_at'))
                    ->description(fn ($record) => $record->updated_at->format(adminPanelTimeFormat()))
                    ->dateTime(adminPanelDataFormat())
                    ->sortable()
                    ->searchable(false)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make(OnboardingPage::COLUMN_APPLICATION_TYPE)
                    ->label(trans('onboarding-pages.admin.fields.application_type'))
                    ->options(OnboardingApplicationTypeEnum::class)
                    ->native(false),

                SelectFilter::make(OnboardingPage::COLUMN_ENABLED)
                    ->label(trans('onboarding-pages.admin.fields.enabled'))
                    ->options([
                        '1' => 'Yes',
                        '0' => 'No',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (OnboardingPage $record): bool => ! $record->{OnboardingPage::COLUMN_ENABLED}),
            ]);
    }
}
