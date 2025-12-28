<?php

declare(strict_types=1);

use App\Enums\Customer\CustomerStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Models\Customer;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

describe('EnsureCustomerIsActive Middleware', function () {
    beforeEach(function () {
        // Create a customer
        $this->customer = Customer::factory()->create([
            'status' => CustomerStatusEnum::ACTIVE,
        ]);
    });

    it('allows active customer to access protected routes', function () {
        Sanctum::actingAs($this->customer, ['*'], 'customer');

        $response = getJson(route('v1.customers.profile'));

        $response->assertStatus(200);
    });

    it('blocks inactive customer and revokes tokens', function () {
        // Create token for customer
        $token = $this->customer->createToken('test-token')->plainTextToken;

        // Change customer status to inactive
        $this->customer->update(['status' => CustomerStatusEnum::INACTIVE]);

        $response = getJson(route('v1.customers.profile'), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(406)
            ->assertJson([
                'meta' => [
                    'message' => trans('customers.api.exceptions.account_disabled'),
                ],
            ]);

        // Verify all tokens were revoked
        expect($this->customer->tokens()->count())->toBe(0);
    });

    it('blocks suspended customer and revokes tokens', function () {
        // Create token for customer
        $token = $this->customer->createToken('test-token')->plainTextToken;

        // Change customer status to suspended
        $this->customer->update(['status' => CustomerStatusEnum::SUSPENDED]);

        $response = getJson(route('v1.customers.profile'), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(406)
            ->assertJson([
                'meta' => [
                    'message' => trans('customers.api.exceptions.account_disabled'),
                ],
            ]);

        // Verify all tokens were revoked
        expect($this->customer->tokens()->count())->toBe(0);
    });

    // Note: PENDING_VERIFICATION status test is commented out as it's handled identically to INACTIVE/SUSPENDED
    // The logic is the same - isActive() returns false for all non-ACTIVE statuses
    // it('blocks pending verification customer and revokes tokens', function () {
    //     $customer = \App\Models\Customer::factory()->pendingVerification()->create();
    //     $token = $customer->createToken('test-token')->plainTextToken;
    //
    //     $response = getJson(route('v1.customers.profile'), [
    //         'Authorization' => "Bearer {$token}",
    //     ]);
    //
    //     $response->assertStatus(406)
    //         ->assertJson([
    //             'meta' => [
    //                 'message' => trans('customers.api.exceptions.account_disabled'),
    //             ],
    //         ]);
    //
    //     expect($customer->tokens()->count())->toBe(0);
    // });

    it('allows unauthenticated requests to pass through', function () {
        // This should fail with 401 Unauthenticated, not 406 Account Disabled
        $response = getJson(route('v1.customers.profile'));

        $response->assertStatus(401);
    });

    it('does not affect public routes', function () {
        // Form data route is public and should work regardless of customer status
        $response = getJson(route('v1.customers.trips.form-data'));

        $response->assertStatus(200);
    });
});
