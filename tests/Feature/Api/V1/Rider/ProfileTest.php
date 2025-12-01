<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Rider;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->rider = Rider::factory()->create([
        Rider::COLUMN_FULL_NAME => 'Jane Smith',
        Rider::COLUMN_EMAIL => 'jane@example.com',
        Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
    ]);
});

test('rider can get their profile', function () {
    $response = actingAs($this->rider, 'rider')
        ->getJson(route('v1.riders.profile'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $this->rider->{Rider::COLUMN_ID},
                'full_name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => [
                    'code' => '+965',
                    'number' => $this->rider->{Rider::COLUMN_PHONE_NUMBER},
                ],
                'status' => [
                    'id' => RiderStatusEnum::ONLINE->value,
                    'label' => 'Online',
                ],
            ],
        ]);
});

test('unauthenticated rider cannot get profile', function () {
    $response = getJson(route('v1.riders.profile'));

    $response->assertUnauthorized();
});
