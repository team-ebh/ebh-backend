<?php

declare(strict_types=1);

use App\Models\Customer;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->customer = Customer::factory()->create([
        Customer::COLUMN_FIRST_NAME => 'John',
        Customer::COLUMN_LAST_NAME => 'Doe',
        Customer::COLUMN_EMAIL => 'john@example.com',
    ]);
});

test('customer can get their profile', function () {
    $response = actingAs($this->customer, 'customer')
        ->getJson(route('v1.customers.profile'));

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $this->customer->{Customer::COLUMN_ID},
                'first_name' => 'John',
                'last_name' => 'Doe',
                'full_name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => [
                    'code' => '+965',
                    'number' => $this->customer->{Customer::COLUMN_PHONE_NUMBER},
                ],
                'status' => [
                    'id' => $this->customer->{Customer::COLUMN_STATUS}->value,
                    'label' => 'Active',
                ],
            ],
        ]);
});

test('unauthenticated customer cannot get profile', function () {
    $response = getJson(route('v1.customers.profile'));

    $response->assertUnauthorized();
});
