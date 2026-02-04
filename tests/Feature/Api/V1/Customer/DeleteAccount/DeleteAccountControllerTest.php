<?php

declare(strict_types=1);

use App\Enums\Customer\CustomerStatusEnum;
use App\Models\Customer;
use App\Models\DeleteAccountRequest;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\withHeaders;

describe('V1 Customer Delete Account API', function () {
    describe('Send OTP', function () {
        it('can send OTP to customer for delete account', function () {
            $customer = Customer::factory()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.send-otp'))
                ->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'otp_expires_at',
                    ],
                ]);

            // Verify OTP is generated
            $customer->refresh();
            expect($customer->otp)->not->toBeNull();
            expect($customer->otp_expires_at)->not->toBeNull();
        });

        it('returns existing OTP expiration if OTP is still valid', function () {
            $customer = Customer::factory()->withOtp()->create();
            $originalExpiration = $customer->otp_expires_at;
            $token = $customer->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.send-otp'))
                ->assertStatus(200);

            // Should return the same expiration time (not generate new OTP)
            expect($response->json('data.otp_expires_at'))->toBe($originalExpiration);
        });

        it('unauthenticated customer cannot send OTP for delete account', function () {
            withHeaders([
                'Host' => 'api.localhost',
                'Accept' => 'application/json',
            ])
                ->post(route('v1.customers.delete-account.send-otp'))
                ->assertStatus(401);
        });
    });

    describe('Verify OTP', function () {
        it('can verify OTP and receive security token', function () {
            $customer = Customer::factory()->withOtp()->create();
            $otp = $customer->otp;
            $token = $customer->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.verify-otp'), [
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
            $customer->refresh();
            expect($customer->otp)->toBeNull();

            // Verify delete account request is created
            $this->assertDatabaseHas('delete_account_requests', [
                'requestable_type' => Customer::class,
                'requestable_id' => $customer->id,
                'security_token' => $securityToken,
            ]);
        });

        it('validates required OTP field', function () {
            $customer = Customer::factory()->withOtp()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.verify-otp'), [])
                ->assertStatus(422);

            $errors = $response->json('meta.errors');
            $errorFields = collect($errors)->pluck('field')->toArray();

            expect($errorFields)->toContain('otp');
        });

        it('validates OTP format (must be 4 digits)', function () {
            $customer = Customer::factory()->withOtp()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.verify-otp'), [
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

            $customer = Customer::factory()->withOtp()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.verify-otp'), [
                    'otp' => '0000', // Wrong OTP
                ])
                ->assertStatus(406); // InvalidOtpException returns 406
        });

        it('accepts any OTP in test environment', function () {
            // Test environment accepts any OTP
            Config::set('app.env', 'testing');

            $customer = Customer::factory()->withOtp()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.verify-otp'), [
                    'otp' => '0000', // Any OTP works in test environment
                ])
                ->assertStatus(200);
        });

        it('unauthenticated customer cannot verify OTP', function () {
            withHeaders([
                'Host' => 'api.localhost',
                'Accept' => 'application/json',
            ])
                ->post(route('v1.customers.delete-account.verify-otp'), [
                    'otp' => '1234',
                ])
                ->assertStatus(401);
        });
    });

    describe('Confirm Delete Account', function () {
        it('can confirm account deletion with valid security token', function () {
            $customer = Customer::factory()->create();
            $originalPhoneNumber = $customer->phone_number;
            $token = $customer->createToken('test-token')->plainTextToken;

            // Create a valid delete account request with security token
            $securityToken = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';
            DeleteAccountRequest::create([
                'requestable_type' => Customer::class,
                'requestable_id' => $customer->id,
                'security_token' => $securityToken,
                'security_token_expires_at' => now()->addMinutes(30),
            ]);

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.confirm'), [
                    'token' => $securityToken,
                ])
                ->assertStatus(200);

            // Verify customer status is DELETED
            $customer->refresh();
            expect($customer->status)->toBe(CustomerStatusEnum::DELETED);
            expect($customer->phone_number)->toBe($originalPhoneNumber . '-deleted');

            // Verify all tokens are revoked
            $this->assertDatabaseMissing('personal_access_tokens', [
                'tokenable_id' => $customer->id,
                'tokenable_type' => Customer::class,
            ]);

            // Verify delete account request is updated
            $this->assertDatabaseHas('delete_account_requests', [
                'requestable_type' => Customer::class,
                'requestable_id' => $customer->id,
                'security_token' => null,
            ]);

            $deleteRequest = DeleteAccountRequest::where('requestable_id', $customer->id)
                ->where('requestable_type', Customer::class)
                ->first();
            expect($deleteRequest->account_deleted_at)->not->toBeNull();
        });

        it('validates required token field', function () {
            $customer = Customer::factory()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.confirm'), [])
                ->assertStatus(422);

            $errors = $response->json('meta.errors');
            $errorFields = collect($errors)->pluck('field')->toArray();

            expect($errorFields)->toContain('token');
        });

        it('validates token format (must be 32 characters)', function () {
            $customer = Customer::factory()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            $response = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.confirm'), [
                    'token' => 'short-token',
                ])
                ->assertStatus(422);

            $errors = $response->json('meta.errors');
            $errorFields = collect($errors)->pluck('field')->toArray();

            expect($errorFields)->toContain('token');
        });

        it('returns error for invalid security token', function () {
            $customer = Customer::factory()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.confirm'), [
                    'token' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                ])
                ->assertStatus(406);
        });

        it('returns error for expired security token', function () {
            $customer = Customer::factory()->create();
            $token = $customer->createToken('test-token')->plainTextToken;

            // Create an expired delete account request
            $securityToken = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';
            DeleteAccountRequest::create([
                'requestable_type' => Customer::class,
                'requestable_id' => $customer->id,
                'security_token' => $securityToken,
                'security_token_expires_at' => now()->subMinutes(10), // Expired
            ]);

            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.confirm'), [
                    'token' => $securityToken,
                ])
                ->assertStatus(406);
        });

        it('unauthenticated customer cannot confirm delete account', function () {
            withHeaders([
                'Host' => 'api.localhost',
                'Accept' => 'application/json',
            ])
                ->post(route('v1.customers.delete-account.confirm'), [
                    'token' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6',
                ])
                ->assertStatus(401);
        });
    });

    describe('Complete Delete Account Flow', function () {
        it('can complete full delete account flow', function () {
            // Step 1: Create customer
            $customer = Customer::factory()->create();
            $originalPhoneNumber = $customer->phone_number;
            $token = $customer->createToken('test-token')->plainTextToken;

            // Step 2: Send OTP
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.send-otp'))
                ->assertStatus(200);

            // Step 3: Get OTP from database
            $customer->refresh();
            $otp = $customer->otp;

            // Step 4: Verify OTP and get security token
            $verifyResponse = withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.verify-otp'), [
                    'otp' => $otp,
                ])
                ->assertStatus(200);

            $securityToken = $verifyResponse->json('data.token');

            // Step 5: Confirm deletion
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token}",
            ])
                ->post(route('v1.customers.delete-account.confirm'), [
                    'token' => $securityToken,
                ])
                ->assertStatus(200);

            // Verify account is deleted
            $customer->refresh();
            expect($customer->status)->toBe(CustomerStatusEnum::DELETED);
            expect($customer->phone_number)->toBe($originalPhoneNumber . '-deleted');
        });

        it('handles multiple account deletions', function () {
            // Create two customers with different phone numbers
            $customer1 = Customer::factory()->create(['phone_number' => '11111111']);
            $customer2 = Customer::factory()->create(['phone_number' => '22222222']);

            // Create delete requests for both customers
            $securityToken1 = 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';
            $securityToken2 = 'b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7';

            DeleteAccountRequest::create([
                'requestable_type' => Customer::class,
                'requestable_id' => $customer1->id,
                'security_token' => $securityToken1,
                'security_token_expires_at' => now()->addMinutes(30),
            ]);

            DeleteAccountRequest::create([
                'requestable_type' => Customer::class,
                'requestable_id' => $customer2->id,
                'security_token' => $securityToken2,
                'security_token_expires_at' => now()->addMinutes(30),
            ]);

            $token1 = $customer1->createToken('test-token')->plainTextToken;
            $token2 = $customer2->createToken('test-token')->plainTextToken;

            // Delete first customer
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token1}",
            ])
                ->post(route('v1.customers.delete-account.confirm'), [
                    'token' => $securityToken1,
                ])
                ->assertStatus(200);

            // Clear cached auth state before second request
            // (first deletion revoked tokens which may affect guard cache)
            auth()->forgetGuards();

            // Delete second customer
            withHeaders([
                'Host' => 'api.localhost',
                'Authorization' => "Bearer {$token2}",
            ])
                ->post(route('v1.customers.delete-account.confirm'), [
                    'token' => $securityToken2,
                ])
                ->assertStatus(200);

            // Verify both customers have unique anonymized phone numbers
            $customer1->refresh();
            $customer2->refresh();

            expect($customer1->phone_number)->toBe('11111111-deleted');
            expect($customer2->phone_number)->toBe('22222222-deleted');
            expect($customer1->status)->toBe(CustomerStatusEnum::DELETED);
            expect($customer2->status)->toBe(CustomerStatusEnum::DELETED);
        });
    });
});
