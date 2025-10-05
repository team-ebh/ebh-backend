<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Filament\Actions\Action;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

/**
 * Trait to customize create action buttons
 *
 * For admins: "Save" and "Save & Create another"
 */
trait HasCustomCreateActions
{
    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(trans('general.admin.save'))
            ->color('primary')
            ->icon('heroicon-o-check')
            ->submit('create')
            ->keyBindings(['mod+s']);
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return Action::make('createAnother')
            ->label(trans('general.admin.save_and_create_another'))
            ->action('createAnother')
            ->color('primary')
            ->icon('heroicon-o-check')
            ->keyBindings(['mod+shift+s']);
    }

    public static function getWizardAction(): Action
    {
        return Action::make('create')
            ->label(trans('general.admin.save'))
            ->icon('heroicon-o-check')
            ->submit('create')
            ->keyBindings(['mod+s']);
    }

    /**
     * Get HTML with both submit and cancel buttons for wizard final step
     */
    public static function getWizardSubmitActionWithCancel(): HtmlString
    {
        return new HtmlString(
            Blade::render(<<<'BLADE'
                <div class="flex justify-end gap-3">
                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-x-mark"
                        onclick="window.history.back();"
                    >
                        {{ trans('general.admin.actions.cancel') }}
                    </x-filament::button>
                    <x-filament::button
                        type="submit"
                        color="primary"
                        icon="heroicon-o-check"
                    >
                        {{ trans('general.admin.save') }}
                    </x-filament::button>
                </div>
            BLADE)
        );
    }
}
