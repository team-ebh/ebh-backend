<?php

declare(strict_types=1);

namespace App\Filament\Resources\Admins\Schemas;

use App\Models\Admin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make(Admin::COLUMN_NAME)
                            ->label(trans('admins.admin.fields.name'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(100),
                        TextInput::make(Admin::COLUMN_EMAIL)
                            ->label(trans('admins.admin.fields.email'))
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->email()
                            ->required()
                            ->unique(table: Admin::class, column: Admin::COLUMN_EMAIL, ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make(Admin::COLUMN_PHONE_NUMBER)
                            ->label(trans('admins.admin.fields.phone_number'))
                            ->tel()
                            ->unique(table: Admin::class, column: Admin::COLUMN_PHONE_NUMBER, ignoreRecord: true)
                            ->prefix(defaultPrefixPhoneNumber())
                            ->telRegex('/^[0-9]{8}$/')
                            ->maxLength(255),
                        TextInput::make(Admin::COLUMN_PASSWORD)
                            ->label(trans('admins.admin.fields.password'))
                            ->password()
                            ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rule(Password::default())
                            ->hidden(fn (string $operation) => $operation === 'edit')
                            ->autocomplete('new-password')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->live(debounce: 500),
                        Toggle::make(Admin::COLUMN_ENABLED)
                            ->inline(false)
                            ->default(true)
                            ->label(trans('admins.admin.fields.enabled'))
                            ->onColor('success')
                            ->offColor('danger')
                            ->onIcon('heroicon-m-check-circle')
                            ->offIcon('heroicon-m-x-circle'),
                    ]),
            ]);
    }
}
