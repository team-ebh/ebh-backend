<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse extends \Filament\Auth\Http\Responses\LoginResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        if (Filament::getCurrentPanel()->getId() === 'api') {
            return redirect()->to('/docs/v1/drivers');
        }

        return parent::toResponse($request);
    }
}
