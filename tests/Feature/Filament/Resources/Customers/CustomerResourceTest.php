<?php

declare(strict_types=1);

use App\Enums\Currency\CurrencyEnum;
use App\Enums\Customer\CustomerStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\Payment\PaymentMethodEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;
use App\Exceptions\Customer\CustomerHasActiveTripException;
use App\Exceptions\Customer\CustomerHasPendingPaymentException;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Trip;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

beforeEach(function () {
    adminPanelLogin();
});

it('can render index page', function () {
    get(CustomerResource::getUrl('index'))
        ->assertSuccessful();
});

it('can render create page', function () {
    get(CustomerResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create customer', function () {
    $newData = Customer::factory()->make();

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            Customer::COLUMN_FIRST_NAME => $newData->first_name,
            Customer::COLUMN_LAST_NAME => $newData->last_name,
            Customer::COLUMN_EMAIL => $newData->email,
            Customer::COLUMN_PHONE_NUMBER => $newData->phone_number,
            Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Customer::class, [
        Customer::COLUMN_FIRST_NAME => $newData->first_name,
        Customer::COLUMN_LAST_NAME => $newData->last_name,
        Customer::COLUMN_EMAIL => $newData->email,
        Customer::COLUMN_PHONE_NUMBER => $newData->phone_number,
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE->value,
    ]);
});

it('can render edit page', function () {
    $customer = Customer::factory()->create();

    get(CustomerResource::getUrl('edit', ['record' => $customer]))
        ->assertSuccessful();
});

it('can update customer', function () {
    $customer = Customer::factory()->create();
    $newData = Customer::factory()->make();

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm([
            Customer::COLUMN_FIRST_NAME => $newData->first_name,
            Customer::COLUMN_LAST_NAME => $newData->last_name,
            Customer::COLUMN_EMAIL => $newData->email,
            Customer::COLUMN_STATUS => CustomerStatusEnum::INACTIVE->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $customer->refresh();

    expect($customer)
        ->first_name->toBe($newData->first_name)
        ->last_name->toBe($newData->last_name)
        ->email->toBe($newData->email)
        ->status->toBe(CustomerStatusEnum::INACTIVE);
});

it('can render view page', function () {
    $customer = Customer::factory()->create();

    get(CustomerResource::getUrl('view', ['record' => $customer]))
        ->assertSuccessful();
});

// Note: Delete action is not available on edit page - customers should be managed via status changes
// it('can delete customer from edit page', function () {
//     $customer = Customer::factory()->create();
//
//     Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
//         ->callAction('delete');
//
//     expect(Customer::find($customer->id))->toBeNull();
// });

it('validates required fields', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm([
            Customer::COLUMN_FIRST_NAME => null,
            Customer::COLUMN_LAST_NAME => null,
            Customer::COLUMN_PHONE_NUMBER => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            Customer::COLUMN_FIRST_NAME => 'required',
            Customer::COLUMN_LAST_NAME => 'required',
            Customer::COLUMN_PHONE_NUMBER => 'required',
        ]);
});

it('can activate customer from view page', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::INACTIVE,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::ACTIVE->value,
        ]);

    $customer->refresh();

    expect($customer->status)->toBe(CustomerStatusEnum::ACTIVE);
});

it('can deactivate customer from view page', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::INACTIVE->value,
        ]);

    $customer->refresh();

    expect($customer->status)->toBe(CustomerStatusEnum::INACTIVE);
});

it('can suspend customer from view page', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::SUSPENDED->value,
        ]);

    $customer->refresh();

    expect($customer->status)->toBe(CustomerStatusEnum::SUSPENDED);
});

it('can list customers with stats', function () {
    Customer::factory()->count(5)->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);
    Customer::factory()->count(2)->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::SUSPENDED,
    ]);

    Livewire::test(ListCustomers::class)
        ->assertSuccessful();

    expect(Customer::count())->toBe(7);
});

it('cannot deactivate customer with active trip', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create an active trip (PENDING_RIDER status)
    Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::INACTIVE->value,
        ])
        ->assertNotified();

    $customer->refresh();

    // Status should not change
    expect($customer->status)->toBe(CustomerStatusEnum::ACTIVE);
});

it('cannot suspend customer with active trip', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create an active trip (IN_PROGRESS status)
    Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::SUSPENDED->value,
        ])
        ->assertNotified();

    $customer->refresh();

    // Status should not change
    expect($customer->status)->toBe(CustomerStatusEnum::ACTIVE);
});

it('cannot deactivate customer with pending payment', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create a completed trip with unpaid payment
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
    ]);

    // Create payment with PENDING status
    Payment::create([
        Payment::COLUMN_CUSTOMER_ID => $customer->id,
        Payment::COLUMN_TRIP_ID => $trip->id,
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-' . uniqid(),
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_AMOUNT => 5.000,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PENDING,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::INACTIVE->value,
        ])
        ->assertNotified();

    $customer->refresh();

    // Status should not change
    expect($customer->status)->toBe(CustomerStatusEnum::ACTIVE);
});

it('can deactivate customer with completed and paid trip', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create a completed trip with paid payment
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
    ]);

    // Create payment with PAID status
    Payment::create([
        Payment::COLUMN_CUSTOMER_ID => $customer->id,
        Payment::COLUMN_TRIP_ID => $trip->id,
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-' . uniqid(),
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_AMOUNT => 5.000,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PAID,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::INACTIVE->value,
        ]);

    $customer->refresh();

    // Status should change
    expect($customer->status)->toBe(CustomerStatusEnum::INACTIVE);
});

it('can deactivate customer with cancelled trip', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create a cancelled trip
    Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::CANCELED_BY_CUSTOMER,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::INACTIVE->value,
        ]);

    $customer->refresh();

    // Status should change
    expect($customer->status)->toBe(CustomerStatusEnum::INACTIVE);
});

it('can deactivate customer with cash payment trip', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create a completed trip with CASH payment (no payment record needed)
    Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::CASH,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('change_status', data: [
            'status' => CustomerStatusEnum::INACTIVE->value,
        ]);

    $customer->refresh();

    // Status should change (CASH is considered paid immediately)
    expect($customer->status)->toBe(CustomerStatusEnum::INACTIVE);
});

it('cannot edit customer to inactive when has active trip', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create an active trip
    Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::PENDING_RIDER,
    ]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm([
            Customer::COLUMN_STATUS => CustomerStatusEnum::INACTIVE->value,
        ])
        ->call('save')
        ->assertNotified();

    $customer->refresh();

    // Status should not change
    expect($customer->status)->toBe(CustomerStatusEnum::ACTIVE);
});

it('cannot edit customer to suspended when has active trip', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create an active trip
    Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::IN_PROGRESS,
    ]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm([
            Customer::COLUMN_STATUS => CustomerStatusEnum::SUSPENDED->value,
        ])
        ->call('save')
        ->assertNotified();

    $customer->refresh();

    // Status should not change
    expect($customer->status)->toBe(CustomerStatusEnum::ACTIVE);
});

it('cannot edit customer to inactive when has pending payment', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create a completed trip with unpaid payment
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
    ]);

    // Create payment with PENDING status
    Payment::create([
        Payment::COLUMN_CUSTOMER_ID => $customer->id,
        Payment::COLUMN_TRIP_ID => $trip->id,
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-' . uniqid(),
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_AMOUNT => 5.000,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PENDING,
    ]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm([
            Customer::COLUMN_STATUS => CustomerStatusEnum::INACTIVE->value,
        ])
        ->call('save')
        ->assertNotified();

    $customer->refresh();

    // Status should not change
    expect($customer->status)->toBe(CustomerStatusEnum::ACTIVE);
});

it('can edit customer to inactive when completed and paid', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    // Create a completed trip with paid payment
    $trip = Trip::create([
        Trip::COLUMN_CUSTOMER_ID => $customer->id,
        Trip::COLUMN_TRIP_TYPE_ID => TripTypeEnum::RIDE_NOW->value,
        Trip::COLUMN_VEHICLE_TYPE_ID => TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->value,
        Trip::COLUMN_PASSENGER_COUNT => 1,
        Trip::COLUMN_PAYMENT_METHOD => PaymentMethodEnum::KNET,
        Trip::COLUMN_TOTAL_PRICE => 5.000,
        Trip::COLUMN_CURRENCY => CurrencyEnum::KWD->value,
        Trip::COLUMN_STATUS => TripStatusEnum::COMPLETED,
    ]);

    // Create payment with PAID status
    Payment::create([
        Payment::COLUMN_CUSTOMER_ID => $customer->id,
        Payment::COLUMN_TRIP_ID => $trip->id,
        Payment::COLUMN_PAYMENT_NUMBER => 'PAY-' . uniqid(),
        Payment::COLUMN_GATEWAY => PaymentGatewayEnum::UPAYMENTS,
        Payment::COLUMN_AMOUNT => 5.000,
        Payment::COLUMN_CURRENCY => CurrencyEnum::KWD,
        Payment::COLUMN_STATUS => PaymentStatusEnum::PAID,
    ]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm([
            Customer::COLUMN_STATUS => CustomerStatusEnum::INACTIVE->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $customer->refresh();

    // Status should change
    expect($customer->status)->toBe(CustomerStatusEnum::INACTIVE);
});
