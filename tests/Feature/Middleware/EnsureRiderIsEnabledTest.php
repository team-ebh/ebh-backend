<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

describe('EnsureRiderIsEnabled Middleware', function () {
    beforeEach(function () {
        // Create a rider with enabled = true
        $this->rider = Rider::factory()->create([
            'enabled' => true,
        ]);
    });

    it('allows enabled rider to access protected routes', function () {
        Sanctum::actingAs($this->rider, ['*'], 'rider');

        $response = getJson(route('v1.riders.profile'));

        $response->assertStatus(200);
    });

    it('blocks disabled rider and revokes tokens', function () {
        // Create token for rider
        $token = $this->rider->createToken('test-token')->plainTextToken;

        // Disable the rider
        $this->rider->update(['enabled' => false]);

        $response = getJson(route('v1.riders.profile'), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(406)
            ->assertJson([
                'meta' => [
                    'message' => trans('riders.api.exceptions.account_disabled'),
                ],
            ]);

        // Verify all tokens were revoked
        expect($this->rider->tokens()->count())->toBe(0);
    });

    it('blocks deleted rider and revokes tokens', function () {
        // Create token for rider
        $token = $this->rider->createToken('test-token')->plainTextToken;

        // Set rider status to deleted
        $this->rider->update(['status' => RiderStatusEnum::DELETED]);

        $response = getJson(route('v1.riders.profile'), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(406)
            ->assertJson([
                'meta' => [
                    'message' => trans('riders.api.exceptions.account_deleted'),
                ],
            ]);

        // Verify all tokens were revoked
        expect($this->rider->tokens()->count())->toBe(0);
    });

    it('allows unauthenticated requests to pass through', function () {
        // This should fail with 401 Unauthenticated, not 406 Account Disabled
        $response = getJson(route('v1.riders.profile'));

        $response->assertStatus(401);
    });

    it('does not affect public routes', function () {
        // Auth routes are public and should work regardless of rider enabled status
        $response = postJson(route('v1.riders.auth.sign-in'), [
            'phone_number' => '12345678',
        ]);

        // Will fail with validation error (422), not account disabled (406)
        $response->assertStatus(422);
    });
});
