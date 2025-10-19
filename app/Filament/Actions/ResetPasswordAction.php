<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Admin;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ResetPasswordAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'resetPassword';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(trans('admins.admin.actions.reset_password'))
            ->icon('heroicon-o-key')
            ->slideOver()
            ->form([
                TextInput::make('new_password')
                    ->label(trans('admins.admin.actions.new_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->rules([
                        Password::min(6),
                    ])
                    ->confirmed(),
                TextInput::make('new_password_confirmation')
                    ->label(trans('admins.admin.actions.confirm_new_password'))
                    ->revealable()
                    ->password()
                    ->required(),
            ])
            ->action(function (Admin $record, array $data): void {
                $record->update([
                    Admin::COLUMN_PASSWORD => Hash::make($data['new_password']),
                ]);

                Notification::make()
                    ->title(trans('admins.admin.actions.password_reset_success'))
                    ->success()
                    ->send();
            })
            ->visible(fn (Admin $record): bool => $record->{Admin::COLUMN_EMAIL} !== config('auth-credentials.admin.email'));
    }
}
