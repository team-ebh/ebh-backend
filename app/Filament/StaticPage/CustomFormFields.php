<?php

declare(strict_types=1);

namespace App\Filament\StaticPage;

use App\Enums\StaticPage\ApplicationTypeEnum;
use Filament\Forms\Components\Select;
use Nizek\StaticPage\Contracts\CustomFormFieldsContract;

class CustomFormFields implements CustomFormFieldsContract
{
    /**
     * Get custom form fields to be added before content fields.
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function getFields(): array
    {
        return [
            Select::make('application_type')
                ->label(trans('static_pages.admin.application_type'))
                ->options(ApplicationTypeEnum::class)
                ->default(ApplicationTypeEnum::CUSTOMER->value)
                ->required()
                ->columnSpanFull()
                ->native(false),
        ];
    }
}
