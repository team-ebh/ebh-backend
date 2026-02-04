<?php

declare(strict_types=1);

namespace App\Filament\Resources\SmsLogs;

use App\Filament\Resources\SmsLogs\Pages\ListSmsLogs;
use App\Filament\Resources\SmsLogs\Pages\ViewSmsLog;
use App\Filament\Resources\SmsLogs\Schemas\SmsLogInfolist;
use App\Filament\Resources\SmsLogs\Tables\SmsLogsTable;
use App\Models\Admin;
use App\Models\SmsLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SmsLogResource extends Resource
{
    protected static ?string $model = SmsLog::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function getNavigationLabel(): string
    {
        return __('sms.resource.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('sms.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sms.resource.plural_label');
    }

    public static function infolist(Schema $schema): Schema
    {
        return SmsLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SmsLogsTable::configure($table);
    }

    public static function canAccess(): bool
    {
        /** @var Admin|null $admin */
        $admin = auth()->user();

        if (! $admin) {
            return false;
        }

        return $admin->{Admin::COLUMN_EMAIL} === config('auth-credentials.admin.email');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmsLogs::route('/'),
            'view' => ViewSmsLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
