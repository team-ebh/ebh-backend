<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Company;
use App\Models\Rider;

function apiUrl(string $path): string
{
    return 'http://api.localhost' . $path;
}

beforeEach(function () {
    $company = Company::query()->create([
        Company::COLUMN_NAME => 'Test Company',
        Company::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Company::COLUMN_ADDRESS => 'Test Address',
    ]);

    $this->rider = Rider::query()->create([
        Rider::COLUMN_FULL_NAME => 'Test Rider',
        Rider::COLUMN_EMAIL => sprintf('rider%d@test.com', rand(1, 9999)),
        Rider::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
        Rider::COLUMN_COMPANY_ID => $company->id,
        Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE,
    ]);

    $this->token = $this->rider->createToken('test')->plainTextToken;
    $this->headers = [
        'Authorization' => "Bearer {$this->token}",
        'Accept' => 'application/json',
    ];
});

test('it can update rider location with valid coordinates', function () {
    $response = $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    $response->assertOk();

    // Verify database was updated
    $this->rider->refresh();
    expect($this->rider->{Rider::COLUMN_LATITUDE})->toBe('29.37590000');
    expect($this->rider->{Rider::COLUMN_LONGITUDE})->toBe('47.97740000');
    expect($this->rider->{Rider::COLUMN_LAST_LOCATION_UPDATE})->not->toBeNull();
});

test('it requires authentication', function () {
    $response = $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ]);

    $response->assertUnauthorized();
});

test('it updates location timestamp on each request', function () {
    // First update
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3759,
        'longitude' => 47.9774,
    ], $this->headers);

    $this->rider->refresh();
    $firstTimestamp = $this->rider->{Rider::COLUMN_LAST_LOCATION_UPDATE};

    // Wait a moment
    sleep(1);

    // Second update
    $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.3860,
        'longitude' => 47.9880,
    ], $this->headers);

    $this->rider->refresh();
    $secondTimestamp = $this->rider->{Rider::COLUMN_LAST_LOCATION_UPDATE};

    expect($secondTimestamp)->toBeGreaterThan($firstTimestamp);
});

test('it can handle decimal precision in coordinates', function () {
    $response = $this->postJson(apiUrl('/v1/riders/location'), [
        'latitude' => 29.37594567,
        'longitude' => 47.97745678,
    ], $this->headers);

    $response->assertOk();

    $this->rider->refresh();
    expect($this->rider->{Rider::COLUMN_LATITUDE})->toBe('29.37594567');
    expect($this->rider->{Rider::COLUMN_LONGITUDE})->toBe('47.97745678');
});
