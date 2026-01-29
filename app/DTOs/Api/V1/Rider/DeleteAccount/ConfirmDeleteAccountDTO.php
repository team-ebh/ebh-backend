<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Rider\DeleteAccount;

use App\Interfaces\DTOs\RequestDataTransferObject;
use App\Models\Rider;
use Illuminate\Http\Request;

class ConfirmDeleteAccountDTO implements RequestDataTransferObject
{
    public Rider $rider;

    public string $token;

    public function getDataFromRequest(Request $request): void
    {
        $this->rider = getAuthenticatedUser();
        $this->token = (string) $request->post('token');
    }
}
