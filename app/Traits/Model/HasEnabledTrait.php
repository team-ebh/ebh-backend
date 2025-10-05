<?php

declare(strict_types=1);

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasEnabledTrait
{
    public const string COLUMN_ENABLED = 'enabled';

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where('enabled', true);
    }

    public function isEnabled(): bool
    {
        return (bool) $this->enabled;
    }
}
