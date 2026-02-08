<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Customer;
use App\Models\Rider;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\withHeaders;

describe('Sign In', function () {
    it('can sign in existing rider', function () {
        $rider = Rider::factory()->create([
            'phone_number' => '12345678',
        ]);

        $response = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in'), [
                'phone_number' => '12345678',
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'otp_expires_at',
                ],
            ]);

        expect($response->json('data.otp_expires_at'))->not->toBeNull();

        $rider->refresh();
        expect($rider->otp)->not->toBeNull();
        expect($rider->otp_expires_at)->not->toBeNull();
    });

    it('validates required phone number for sign in', function () {
        $response = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in'), [])
            ->assertStatus(422);

        $errors = $response->json('meta.errors');
        $errorFields = collect($errors)->pluck('field')->toArray();

        expect($errorFields)->toContain('phone_number');
    });

    it('validates phone number format for sign in', function () {
        $response = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in'), [
                'phone_number' => 'invalid',
            ])
            ->assertStatus(422);

        $errors = $response->json('meta.errors');
        $errorFields = collect($errors)->pluck('field')->toArray();

        expect($errorFields)->toContain('phone_number');
    });

    it('returns error for non-existent rider', function () {
        $response = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in'), [
                'phone_number' => '99999999',
            ]);

        // Note: The 'exists' validation rule triggers before the action, so it returns 422 not 404
        $response->assertStatus(422);

        $errors = $response->json('meta.errors');
        expect($errors)->toBeArray();
    });
});

describe('Verify OTP', function () {
    it('can verify OTP and get token in test environment', function () {
        Config::set('app.env', 'testing');

        $rider = Rider::factory()->create([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $response = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => $rider->phone_number,
                'otp' => '1234',
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'rider' => [
                        'id',
                        'full_name',
                        'email',
                        'phone',
                        'status',
                        'rating',
                        'joined_at',
                    ],
                ],
            ]);

        expect($response->json('data.token'))->not->toBeNull();
    });

    it('can verify correct OTP and get token in production environment', function () {
        Config::set('app.env', 'production');

        $rider = Rider::factory()->create([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $response = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => $rider->phone_number,
                'otp' => '1234', // Correct OTP
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'rider' => [
                        'id',
                        'full_name',
                        'email',
                        'phone',
                        'status',
                        'rating',
                        'joined_at',
                    ],
                ],
            ]);

        expect($response->json('data.token'))->not->toBeNull();
    });

    it('validates required fields for verify OTP', function () {
        $response = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [])
            ->assertStatus(422);

        $errors = $response->json('meta.errors');
        $errorFields = collect($errors)->pluck('field')->toArray();

        expect($errorFields)->toContain('phone_number')
            ->and($errorFields)->toContain('otp');
    });

    it('validates OTP format', function () {
        $response = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => '12345678',
                'otp' => '12345', // Should be 4 digits
            ])
            ->assertStatus(422);

        $errors = $response->json('meta.errors');
        $errorFields = collect($errors)->pluck('field')->toArray();

        expect($errorFields)->toContain('otp');
    });

    it('returns error for invalid OTP in production environment', function () {
        Config::set('app.env', 'production');

        $rider = Rider::factory()->create([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => $rider->phone_number,
                'otp' => '9999', // Wrong OTP
            ])
            ->assertStatus(406); // InvalidOtpException returns 406
    });

    it('accepts any OTP in test environment', function () {
        Config::set('app.env', 'testing');

        $rider = Rider::factory()->create([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => $rider->phone_number,
                'otp' => '9999', // Any OTP works in test environment
            ])
            ->assertStatus(200);
    });

    it('returns error for expired OTP in production environment', function () {
        Config::set('app.env', 'production');

        $rider = Rider::factory()->create([
            'otp' => '1234',
            'otp_expires_at' => now()->subMinutes(10), // Expired
        ]);

        withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => $rider->phone_number,
                'otp' => '1234',
            ])
            ->assertStatus(406); // InvalidOtpException returns 406
    });

    it('accepts expired OTP in test environment', function () {
        Config::set('app.env', 'testing');

        $rider = Rider::factory()->create([
            'otp' => '1234',
            'otp_expires_at' => now()->subMinutes(10), // Expired but should pass in test env
        ]);

        withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => $rider->phone_number,
                'otp' => '1234',
            ])
            ->assertStatus(200);
    });

    it('returns error for non-existent rider', function () {
        withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => '99999999',
                'otp' => '1234',
            ])
            ->assertStatus(406);
    });
});

describe('Sign Out', function () {
    it('can sign out authenticated rider', function () {
        $rider = Rider::factory()->create();
        $token = $rider->createToken('test')->plainTextToken;

        withHeaders([
            'Host' => 'api.localhost',
            'Authorization' => "Bearer {$token}",
        ])
            ->post(route('v1.riders.auth.sign-out'))
            ->assertStatus(200);

        expect($rider->tokens()->count())->toBe(0);
    });

    it('requires authentication for sign out', function () {
        withHeaders([
            'Host' => 'api.localhost',
            'Accept' => 'application/json',
        ])
            ->post(route('v1.riders.auth.sign-out'))
            ->assertStatus(401);
    });
});

describe('Complete Auth Flow', function () {
    it('can complete signin flow', function () {
        // Step 1: Create rider
        $rider = Rider::factory()->create([
            'phone_number' => '12345678',
        ]);

        // Step 2: Sign in
        withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in'), [
                'phone_number' => $rider->phone_number,
            ])
            ->assertStatus(200);

        // Step 3: Get updated OTP
        $rider->refresh();
        $otp = $rider->otp;

        // Step 4: Verify OTP
        $verifyResponse = withHeaders(['Host' => 'api.localhost'])
            ->post(route('v1.riders.auth.sign-in.verify-otp'), [
                'phone_number' => $rider->phone_number,
                'otp' => $otp,
            ])
            ->assertStatus(200);

        expect($verifyResponse->json('data.token'))->not->toBeNull();
    });
});

describe('Guard Isolation', function () {
    it('rider token cannot access customer endpoints', function () {
        $rider = Rider::factory()->create();
        $token = $rider->createToken('test')->plainTextToken;

        // Try to access customer endpoint with rider token
        withHeaders([
            'Host' => 'api.localhost',
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ])
            ->post(route('v1.customers.auth.sign-out'))
            ->assertStatus(401);
    });

    it('customer token cannot access rider endpoints', function () {
        $customer = Customer::factory()->create();
        $token = $customer->createToken('test')->plainTextToken;

        // Try to access rider endpoint with customer token
        withHeaders([
            'Host' => 'api.localhost',
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$token}",
        ])
            ->post(route('v1.riders.auth.sign-out'))
            ->assertStatus(401);
    });
});
