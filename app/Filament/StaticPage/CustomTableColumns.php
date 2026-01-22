<?php

declare(strict_types=1);

namespace App\Filament\StaticPage;

use App\Enums\StaticPage\ApplicationTypeEnum;
use Filament\Tables\Columns\TextColumn;
use Nizek\StaticPage\Contracts\CustomTableColumnsContract;

class CustomTableColumns implements CustomTableColumnsContract
{
    /**
     * Get custom table columns to be added after the enabled column.
     *
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    public static function getColumns(): array
    {
        return [
            TextColumn::make('application_type')
                ->label(trans('static_pages.admin.application_type'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => ApplicationTypeEnum::from($state)->getLabel())
                ->color(fn (string $state): string => ApplicationTypeEnum::from($state)->getColor())
                ->searchable()
                ->sortable(),
        ];
    }
}
