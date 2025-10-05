<?php

declare(strict_types=1);

namespace App\Interfaces\Model;

interface TranslatableInterface
{
    public function translated(string $field): mixed;

    public function getTranslatableColumns(): array;
}
