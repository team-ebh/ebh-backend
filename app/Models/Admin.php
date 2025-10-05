<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasEnabledTrait;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable implements FilamentUser
{
    use HasDefaultColumnModelTrait;
    use HasEnabledTrait;
    use HasFactory;
    use Notifiable;

    public const string COLUMN_NAME = 'name';

    public const string COLUMN_EMAIL = 'email';

    public const string COLUMN_PHONE_NUMBER = 'phone_number';

    public const string COLUMN_PASSWORD = 'password';

    public const string COLUMN_REMEMBER_TOKEN = 'remember_token';

    protected $hidden = [
        self::COLUMN_PASSWORD,
        self::COLUMN_REMEMBER_TOKEN,
    ];

    protected function casts(): array
    {
        return [
            self::COLUMN_PASSWORD => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isEnabled();
    }
}
