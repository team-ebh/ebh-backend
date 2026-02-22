<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OnboardingPageResource\Pages\CreateOnboardingPage;
use App\Filament\Resources\OnboardingPageResource\Pages\EditOnboardingPage;
use App\Filament\Resources\OnboardingPageResource\Pages\ListOnboardingPages;
use App\Filament\Resources\OnboardingPageResource\Schemas\OnboardingPageSchema;
use App\Filament\Resources\OnboardingPageResource\Tables\OnboardingPageTable;
use App\Models\OnboardingPage;
use App\Traits\Filament\TranslatableResourceLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OnboardingPageResource extends Resource
{
    use TranslatableResourceLabels;

    protected static ?string $model = OnboardingPage::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    public static function getNavigationGroup(): ?string
    {
        return trans('general.admin.navigation.app_management');
    }

    public static function form(Schema $schema): Schema
    {
        return OnboardingPageSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnboardingPageTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOnboardingPages::route('/'),
            'create' => CreateOnboardingPage::route('/create'),
            'edit' => EditOnboardingPage::route('/{record}/edit'),
        ];
    }
}
