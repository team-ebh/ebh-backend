<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Rider\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('onboarding', OnboardingController::class)->name('onboarding.index');
