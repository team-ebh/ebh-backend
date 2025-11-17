<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('rider.{riderId}', function ($user, $riderId) {
    return $user && ((int) $user->id === (int) $riderId);
});

Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    return $user && ((int) $user->id === (int) $customerId);
});

// Public monitoring channels for development (non-authenticated)
Broadcast::channel('monitor.rider.{riderId}', function () {
    return true;
});

Broadcast::channel('monitor.customer.{customerId}', function () {
    return true;
});
