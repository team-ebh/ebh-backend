<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;

trait HasTimestampsHeaderActions
{
    protected function getTimestampsHeaderActions(): array
    {
        $record = $this->record;

        $hasCreatedAt = ($record->created_at ?? null) !== null;
        $hasUpdatedAt = ($record->updated_at ?? null) !== null;

        if (! $hasCreatedAt && ! $hasUpdatedAt) {
            return [];
        }

        $formComponents = [];

        if ($hasCreatedAt) {
            $createdAtFormatted = $record->created_at->format(adminPanelDataFormat() . ' ' . adminPanelTimeFormat());
            $formComponents[] = Placeholder::make('created_at')
                ->label(trans('general.admin.created_at'))
                ->content($createdAtFormatted);
        }

        if ($hasUpdatedAt) {
            $updatedAtFormatted = $record->updated_at->format(adminPanelDataFormat() . ' ' . adminPanelTimeFormat());
            $formComponents[] = Placeholder::make('updated_at')
                ->label(trans('general.admin.updated_at'))
                ->content($updatedAtFormatted);
        }

        return [
            Action::make('timestamps')
                ->label(trans('general.admin.timestamps'))
                ->icon('heroicon-o-clock')
                ->color('info')
                ->slideOver()
                ->form([
                    Section::make()
                        ->schema($formComponents),
                ])
                ->modalSubmitAction(false)
                ->modalCancelAction(false),
        ];
    }
}
