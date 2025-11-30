<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('rider.{riderId}', function ($user, $riderId) {
    // Support both rider and sanctum guards
    $authenticatedUser = $user ?? auth('rider')->user() ?? auth('sanctum')->user();

    return $authenticatedUser && ((int) $authenticatedUser->id === (int) $riderId);
});

Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    // Support both customer and sanctum guards
    $authenticatedUser = $user ?? auth('customer')->user() ?? auth('sanctum')->user();

    return $authenticatedUser && ((int) $authenticatedUser->id === (int) $customerId);
});

// Public monitoring channels for development (non-authenticated)
Broadcast::channel('monitor.rider.{riderId}', function () {
    return true;
});

Broadcast::channel('monitor.customer.{customerId}', function () {
    return true;
});
