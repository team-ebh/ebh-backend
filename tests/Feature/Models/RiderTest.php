<?php

declare(strict_types=1);

use App\Enums\Rider\RiderStatusEnum;
use App\Models\Company;
use App\Models\Rider;

describe('Rider Model Status Methods', function () {
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
        ]);
    });

    describe('isOnline()', function () {
        it('returns true when rider status is ONLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE]);

            expect($this->rider->isOnline())->toBeTrue()
                ->and($this->rider->isOffline())->toBeFalse()
                ->and($this->rider->isBusy())->toBeFalse();
        });

        it('returns false when rider status is OFFLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE]);

            expect($this->rider->isOnline())->toBeFalse();
        });

        it('returns false when rider status is BUSY', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::BUSY]);

            expect($this->rider->isOnline())->toBeFalse();
        });
    });

    describe('isOffline()', function () {
        it('returns true when rider status is OFFLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE]);

            expect($this->rider->isOffline())->toBeTrue()
                ->and($this->rider->isOnline())->toBeFalse()
                ->and($this->rider->isBusy())->toBeFalse();
        });

        it('returns false when rider status is ONLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE]);

            expect($this->rider->isOffline())->toBeFalse();
        });

        it('returns false when rider status is BUSY', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::BUSY]);

            expect($this->rider->isOffline())->toBeFalse();
        });
    });

    describe('isBusy()', function () {
        it('returns true when rider status is BUSY', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::BUSY]);

            expect($this->rider->isBusy())->toBeTrue()
                ->and($this->rider->isOnline())->toBeFalse()
                ->and($this->rider->isOffline())->toBeFalse();
        });

        it('returns false when rider status is ONLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE]);

            expect($this->rider->isBusy())->toBeFalse();
        });

        it('returns false when rider status is OFFLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE]);

            expect($this->rider->isBusy())->toBeFalse();
        });
    });

    describe('Default Status', function () {
        it('has OFFLINE status by default', function () {
            $newRider = Rider::query()->create([
                Rider::COLUMN_FULL_NAME => 'New Rider',
                Rider::COLUMN_EMAIL => sprintf('new-rider%d@test.com', rand(1, 9999)),
                Rider::COLUMN_PHONE_NUMBER => sprintf('+9655000%04d', rand(1, 9999)),
                Rider::COLUMN_COMPANY_ID => $this->rider->{Rider::COLUMN_COMPANY_ID},
            ]);

            expect($newRider->{Rider::COLUMN_STATUS})->toBe(RiderStatusEnum::OFFLINE)
                ->and($newRider->isOffline())->toBeTrue()
                ->and($newRider->isOnline())->toBeFalse()
                ->and($newRider->isBusy())->toBeFalse();
        });
    });

    describe('Status Transitions', function () {
        it('can transition from OFFLINE to ONLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE]);
            expect($this->rider->isOffline())->toBeTrue();

            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE]);
            $this->rider->refresh();

            expect($this->rider->isOnline())->toBeTrue()
                ->and($this->rider->isOffline())->toBeFalse();
        });

        it('can transition from ONLINE to BUSY', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE]);
            expect($this->rider->isOnline())->toBeTrue();

            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::BUSY]);
            $this->rider->refresh();

            expect($this->rider->isBusy())->toBeTrue()
                ->and($this->rider->isOnline())->toBeFalse();
        });

        it('can transition from BUSY to ONLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::BUSY]);
            expect($this->rider->isBusy())->toBeTrue();

            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE]);
            $this->rider->refresh();

            expect($this->rider->isOnline())->toBeTrue()
                ->and($this->rider->isBusy())->toBeFalse();
        });

        it('can transition from ONLINE to OFFLINE', function () {
            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::ONLINE]);
            expect($this->rider->isOnline())->toBeTrue();

            $this->rider->update([Rider::COLUMN_STATUS => RiderStatusEnum::OFFLINE]);
            $this->rider->refresh();

            expect($this->rider->isOffline())->toBeTrue()
                ->and($this->rider->isOnline())->toBeFalse();
        });
    });
});
