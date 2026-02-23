<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Customer\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('onboarding', OnboardingController::class)->name('onboarding.index');
