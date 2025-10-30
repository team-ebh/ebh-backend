<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;

use function Pest\Laravel\withHeaders;

describe('V1 Rider Auth API', function () {
    describe('Sign Up', function () {
        it('can sign up a new rider', function () {
            $riderData = [
                'full_name' => 'John Doe',
                'email' => 'rider@example.com',
                'phone_number' => '+1234567890',
            ];

            $response = withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-up'), $riderData)
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'otp_expires_at',
                        'status',
                        'message',
                    ],
                    'meta' => [
                        'message',
                        'errors',
                    ],
                ]);

            $this->assertDatabaseHas('riders', [
                'full_name' => 'John Doe',
                'email' => 'rider@example.com',
                'phone_number' => '+1234567890',
                'status' => RiderStatusEnum::OFFLINE->value,
            ]);

            expect($response->json('data.status'))->toBe('offline');
        });

        it('validates required fields for sign up', function () {
            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-up'), [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['full_name', 'phone_number']);
        });

        it('validates email format for sign up', function () {
            $riderData = [
                'full_name' => 'John Doe',
                'email' => 'invalid-email',
                'phone_number' => '+1234567890',
            ];

            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-up'), $riderData)
                ->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        it('validates unique phone number for sign up', function () {
            $existingRider = Rider::factory()->create();

            $riderData = [
                'full_name' => 'John Doe',
                'email' => 'rider@example.com',
                'phone_number' => $existingRider->phone_number,
            ];

            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-up'), $riderData)
                ->assertStatus(422)
                ->assertJsonValidationErrors(['phone_number']);
        });
    });

    describe('Sign In', function () {
        it('can sign in existing rider', function () {
            $rider = Rider::factory()->create([
                'phone_number' => '+1234567890',
            ]);

            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-in'), [
                    'phone_number' => '+1234567890',
                ])
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'otp_expires_at',
                        'message',
                    ],
                ]);

            $rider->refresh();
            expect($rider->otp)->not->toBeNull();
            expect($rider->otp_expires_at)->not->toBeNull();
        });

        it('validates required phone number for sign in', function () {
            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-in'), [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['phone_number']);
        });

        it('validates phone number format for sign in', function () {
            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-in'), [
                    'phone_number' => 'invalid',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['phone_number']);
        });

        it('returns error for non-existent rider', function () {
            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-in'), [
                    'phone_number' => '+9999999999',
                ])
                ->assertStatus(404);
        });
    });

    describe('Verify OTP', function () {
        it('can verify OTP and get token', function () {
            $rider = Rider::factory()->create([
                'otp' => '1234',
                'otp_expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            $response = withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.verify-otp'), [
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
                            'phone_number',
                            'status',
                        ],
                    ],
                ]);

            expect($response->json('data.token'))->not->toBeNull();
        });

        it('validates required fields for verify OTP', function () {
            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.verify-otp'), [])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['phone_number', 'otp']);
        });

        it('validates OTP format', function () {
            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.verify-otp'), [
                    'phone_number' => '+1234567890',
                    'otp' => '12345', // Should be 4 digits
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['otp']);
        });

        it('returns error for invalid OTP', function () {
            $rider = Rider::factory()->create([
                'otp' => '1234',
                'otp_expires_at' => now()->addMinutes(10)->timestamp,
            ]);

            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.verify-otp'), [
                    'phone_number' => $rider->phone_number,
                    'otp' => '9999',
                ])
                ->assertStatus(401);
        });

        it('returns error for expired OTP', function () {
            $rider = Rider::factory()->create([
                'otp' => '1234',
                'otp_expires_at' => now()->subMinutes(10)->timestamp,
            ]);

            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.verify-otp'), [
                    'phone_number' => $rider->phone_number,
                    'otp' => '1234',
                ])
                ->assertStatus(401);
        });

        it('returns error for non-existent rider', function () {
            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.verify-otp'), [
                    'phone_number' => '+9999999999',
                    'otp' => '1234',
                ])
                ->assertStatus(404);
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
            withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-out'))
                ->assertStatus(401);
        });
    });

    describe('Complete Auth Flow', function () {
        it('can complete full sign up and verify flow', function () {
            // Step 1: Sign up
            $riderData = [
                'full_name' => 'John Doe',
                'email' => 'rider@example.com',
                'phone_number' => '+1234567890',
            ];

            $signUpResponse = withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.sign-up'), $riderData)
                ->assertStatus(200);

            // Step 2: Get rider and OTP from database
            $rider = Rider::where('phone_number', '+1234567890')->first();
            $otp = $rider->otp;

            expect($otp)->not->toBeNull();
            expect($rider->isOtpValid())->toBeTrue();

            // Step 3: Verify OTP
            $verifyResponse = withHeaders(['Host' => 'api.localhost'])
                ->post(route('v1.riders.auth.verify-otp'), [
                    'phone_number' => '+1234567890',
                    'otp' => $otp,
                ])
                ->assertStatus(200);

            $token = $verifyResponse->json('data.token');
            expect($token)->not->toBeNull();

            // Step 4: Use token to sign out
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.auth.sign-out'))
                ->assertStatus(200);
        });

        it('can complete signin flow', function () {
            // Step 1: Create rider
            $rider = Rider::factory()->create([
                'phone_number' => '+1234567890',
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
                ->post(route('v1.riders.auth.verify-otp'), [
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
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.auth.sign-out'))
                ->assertStatus(401);
        });

        it('customer token cannot access rider endpoints', function () {
            $customer = \App\Models\Customer::factory()->create();
            $token = $customer->createToken('test')->plainTextToken;

            // Try to access rider endpoint with customer token
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.auth.sign-out'))
                ->assertStatus(401);
        });
    });
});
