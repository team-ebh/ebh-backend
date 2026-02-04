<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\DeleteAccountRequest;
use App\Models\Rider;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\withHeaders;

describe('V1 Rider Delete Account API', function () {
    describe('Send OTP', function () {
        it('can send OTP to rider for delete account', function () {
            $rider = Rider::factory()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.send-otp'))
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'otp_expires_at',
                    ],
                ]);

            // Verify OTP is generated
            $rider->refresh();
            expect($rider->otp)->not->toBeNull();
            expect($rider->otp_expires_at)->not->toBeNull();
        });

        it('returns existing OTP expiration if OTP is still valid', function () {
            $rider = Rider::factory()->withOtp()->create();
            $originalExpiration = $rider->otp_expires_at;
            $token = $rider->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.send-otp'))
                ->assertStatus(200);

            // Should return the same expiration time (not generate new OTP)
            expect($response->json('data.otp_expires_at'))->toBe($originalExpiration);
        });

        it('unauthenticated rider cannot send OTP for delete account', function () {
            withHeaders([
                'Host' => 'api.localhost',
                'Accept' => 'application/json',
            ])
                ->post(route('v1.riders.delete-account.send-otp'))
                ->assertStatus(401);
        });
    });

    describe('Verify OTP', function () {
        it('can verify OTP and receive security token', function () {
            $rider = Rider::factory()->withOtp()->create();
            $otp = $rider->otp;
            $token = $rider->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.verify-otp'), [
                    'otp' => $otp,
                ])
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'token',
                    ],
                ]);

            $securityToken = $response->json('data.token');
            expect($securityToken)->not->toBeNull();
            expect(strlen($securityToken))->toBe(32);

            // Verify OTP is cleared
            $rider->refresh();
            expect($rider->otp)->toBeNull();

            // Verify delete account request is created
            $this->assertDatabaseHas('delete_account_requests', [
                'requestable_type' => Rider::class,
                'requestable_id' => $rider->id,
                'security_token' => $securityToken,
            ]);
        });

        it('validates required OTP field', function () {
            $rider = Rider::factory()->withOtp()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.verify-otp'), [])
                ->assertStatus(422);

            $errors = $response->json('meta.errors');
            $errorFields = collect($errors)->pluck('field')->toArray();

            expect($errorFields)->toContain('otp');
        });

        it('validates OTP format (must be 4 digits)', function () {
            $rider = Rider::factory()->withOtp()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.verify-otp'), [
                    'otp' => '12345', // Invalid length
                ])
                ->assertStatus(422);

            $errors = $response->json('meta.errors');
            $errorFields = collect($errors)->pluck('field')->toArray();

            expect($errorFields)->toContain('otp');
        });

        it('returns error for invalid OTP in production environment', function () {
            // Simulate production environment for OTP validation
            Config::set('app.env', 'production');

            $rider = Rider::factory()->withOtp()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.verify-otp'), [
                    'otp' => '0000', // Wrong OTP
                ])
                ->assertStatus(406); // InvalidOtpException returns 406
        });

        it('accepts any OTP in test environment', function () {
            // Test environment accepts any OTP
            Config::set('app.env', 'testing');

            $rider = Rider::factory()->withOtp()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.verify-otp'), [
                    'otp' => '0000', // Any OTP works in test environment
                ])
                ->assertStatus(200);
        });

        it('unauthenticated rider cannot verify OTP', function () {
            withHeaders([
                'Host' => 'api.localhost',
                'Accept' => 'application/json',
            ])
                ->post(route('v1.riders.delete-account.verify-otp'), [
                    'otp' => '1234',
                ])
                ->assertStatus(401);
        });
    });

    describe('Confirm Delete Account', function () {
        it('can confirm account deletion with valid security token', function () {
            $rider = Rider::factory()->create();
            $originalPhoneNumber = $rider->phone_number;
            $token = $rider->createToken('test-token')->plainTextToken;

            // Create a valid delete account request with security token
            $securityToken = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';
            DeleteAccountRequest::create([
                'requestable_type' => Rider::class,
                'requestable_id' => $rider->id,
                'security_token' => $securityToken,
                'security_token_expires_at' => now()->addMinutes(30),
            ]);

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.confirm'), [
                    'token' => $securityToken,
                ])
                ->assertStatus(200);

            // Verify rider status is DELETED
            $rider->refresh();
            expect($rider->status)->toBe(RiderStatusEnum::DELETED);
            expect($rider->phone_number)->toBe($originalPhoneNumber . '-deleted');

            // Verify all tokens are revoked
            $this->assertDatabaseMissing('personal_access_tokens', [
                'tokenable_id' => $rider->id,
                'tokenable_type' => Rider::class,
            ]);

            // Verify delete account request is updated
            $this->assertDatabaseHas('delete_account_requests', [
                'requestable_type' => Rider::class,
                'requestable_id' => $rider->id,
                'security_token' => null,
            ]);

            $deleteRequest = DeleteAccountRequest::where('requestable_id', $rider->id)
                ->where('requestable_type', Rider::class)
                ->first();
            expect($deleteRequest->account_deleted_at)->not->toBeNull();
        });

        it('validates required token field', function () {
            $rider = Rider::factory()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.confirm'), [])
                ->assertStatus(422);

            $errors = $response->json('meta.errors');
            $errorFields = collect($errors)->pluck('field')->toArray();

            expect($errorFields)->toContain('token');
        });

        it('validates token format (must be 32 characters)', function () {
            $rider = Rider::factory()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.confirm'), [
                    'token' => 'short-token',
                ])
                ->assertStatus(422);

            $errors = $response->json('meta.errors');
            $errorFields = collect($errors)->pluck('field')->toArray();

            expect($errorFields)->toContain('token');
        });

        it('returns error for invalid security token', function () {
            $rider = Rider::factory()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.confirm'), [
                    'token' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                ])
                ->assertStatus(406);
        });

        it('returns error for expired security token', function () {
            $rider = Rider::factory()->create();
            $token = $rider->createToken('test-token')->plainTextToken;

            // Create an expired delete account request
            $securityToken = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';
            DeleteAccountRequest::create([
                'requestable_type' => Rider::class,
                'requestable_id' => $rider->id,
                'security_token' => $securityToken,
                'security_token_expires_at' => now()->subMinutes(10), // Expired
            ]);

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.confirm'), [
                    'token' => $securityToken,
                ])
                ->assertStatus(406);
        });

        it('unauthenticated rider cannot confirm delete account', function () {
            withHeaders([
                'Host' => 'api.localhost',
                'Accept' => 'application/json',
            ])
                ->post(route('v1.riders.delete-account.confirm'), [
                    'token' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                ])
                ->assertStatus(401);
        });
    });

    describe('Complete Delete Account Flow', function () {
        it('can complete full delete account flow', function () {
            // Step 1: Create rider
            $rider = Rider::factory()->create();
            $originalPhoneNumber = $rider->phone_number;
            $token = $rider->createToken('test-token')->plainTextToken;

            // Step 2: Send OTP
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.send-otp'))
                ->assertStatus(200);

            // Step 3: Get OTP from database
            $rider->refresh();
            $otp = $rider->otp;

            // Step 4: Verify OTP and get security token
            $verifyResponse = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.verify-otp'), [
                    'otp' => $otp,
                ])
                ->assertStatus(200);

            $securityToken = $verifyResponse->json('data.token');

            // Step 5: Confirm deletion
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.riders.delete-account.confirm'), [
                    'token' => $securityToken,
                ])
                ->assertStatus(200);

            // Verify account is deleted
            $rider->refresh();
            expect($rider->status)->toBe(RiderStatusEnum::DELETED);
            expect($rider->phone_number)->toBe($originalPhoneNumber . '-deleted');
        });

        it('handles multiple account deletions', function () {
            // Create two riders with different phone numbers
            $rider1 = Rider::factory()->create(['phone_number' => '11111111']);
            $rider2 = Rider::factory()->create(['phone_number' => '22222222']);

            // Create delete requests for both riders
            $securityToken1 = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';
            $securityToken2 = 'b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7';

            DeleteAccountRequest::create([
                'requestable_type' => Rider::class,
                'requestable_id' => $rider1->id,
                'security_token' => $securityToken1,
                'security_token_expires_at' => now()->addMinutes(30),
            ]);

            DeleteAccountRequest::create([
                'requestable_type' => Rider::class,
                'requestable_id' => $rider2->id,
                'security_token' => $securityToken2,
                'security_token_expires_at' => now()->addMinutes(30),
            ]);

            $token1 = $rider1->createToken('test-token')->plainTextToken;
            $token2 = $rider2->createToken('test-token')->plainTextToken;

            // Delete first rider
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token1}",
            ])
                ->post(route('v1.riders.delete-account.confirm'), [
                    'token' => $securityToken1,
                ])
                ->assertStatus(200);

            // Clear cached auth state before second request
            // (first deletion revoked tokens which may affect guard cache)
            auth()->forgetGuards();

            // Delete second rider
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token2}",
            ])
                ->post(route('v1.riders.delete-account.confirm'), [
                    'token' => $securityToken2,
                ])
                ->assertStatus(200);

            // Verify both riders have unique anonymized phone numbers
            $rider1->refresh();
            $rider2->refresh();

            expect($rider1->phone_number)->toBe('11111111-deleted');
            expect($rider2->phone_number)->toBe('22222222-deleted');
            expect($rider1->status)->toBe(RiderStatusEnum::DELETED);
            expect($rider2->status)->toBe(RiderStatusEnum::DELETED);
        });
    });
});
