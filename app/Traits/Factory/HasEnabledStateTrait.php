<?php

declare(strict_types=1);

namespace App\Traits\Factory;

trait HasEnabledStateTrait
{
    public function enabled(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'enabled' => true,
            ];
        });
    }
}
