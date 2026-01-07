<?php

declare(strict_types=1);

use App\Services\TripPricingService;

beforeEach(function () {
    $this->service = new TripPricingService;
});

describe('calculateWaitingCharge', function () {
    it('returns null for null input', function () {
        expect($this->service->calculateWaitingCharge(null))->toBeNull();
    });

    it('returns null for zero minutes', function () {
        expect($this->service->calculateWaitingCharge(0))->toBeNull();
    });

    it('returns null for negative minutes', function () {
        expect($this->service->calculateWaitingCharge(-10))->toBeNull();
    });

    it('returns null for less than 30 minutes (no complete interval)', function () {
        expect($this->service->calculateWaitingCharge(1))->toBeNull()
            ->and($this->service->calculateWaitingCharge(15))->toBeNull()
            ->and($this->service->calculateWaitingCharge(29))->toBeNull();
    });

    it('charges 2.500 KWD for exactly 30 minutes (1 complete interval)', function () {
        expect($this->service->calculateWaitingCharge(30))->toBe(2.5);
    });

    it('charges 2.500 KWD for 31-59 minutes (still 1 complete interval)', function () {
        expect($this->service->calculateWaitingCharge(31))->toBe(2.5)
            ->and($this->service->calculateWaitingCharge(45))->toBe(2.5)
            ->and($this->service->calculateWaitingCharge(59))->toBe(2.5);
    });

    it('charges 5.000 KWD for 60-89 minutes (2 complete intervals)', function () {
        expect($this->service->calculateWaitingCharge(60))->toBe(5.0)
            ->and($this->service->calculateWaitingCharge(75))->toBe(5.0)
            ->and($this->service->calculateWaitingCharge(89))->toBe(5.0);
    });

    it('charges 7.500 KWD for 90-119 minutes (3 complete intervals)', function () {
        expect($this->service->calculateWaitingCharge(90))->toBe(7.5)
            ->and($this->service->calculateWaitingCharge(100))->toBe(7.5)
            ->and($this->service->calculateWaitingCharge(119))->toBe(7.5);
    });

    it('charges correctly for large waiting times', function () {
        // 120 minutes = 4 intervals = 10.000 KWD
        expect($this->service->calculateWaitingCharge(120))->toBe(10.0)
            ->and($this->service->calculateWaitingCharge(180))->toBe(15.0)
            ->and($this->service->calculateWaitingCharge(240))->toBe(20.0);

        // 180 minutes = 6 intervals = 15.000 KWD

        // 240 minutes = 8 intervals = 20.000 KWD
    });
});

describe('calculateBaseFare', function () {
    it('calculates base fare correctly', function () {
        // BASE_FARE (2.000) + (distance * PER_KM_RATE (0.500))
        expect($this->service->calculateBaseFare(0))->toBe(2.0)
            ->and($this->service->calculateBaseFare(1))->toBe(2.5)
            ->and($this->service->calculateBaseFare(10))->toBe(7.0)
            ->and($this->service->calculateBaseFare(20))->toBe(12.0);
    });
});
