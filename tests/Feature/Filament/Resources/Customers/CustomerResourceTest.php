<?php

declare(strict_types=1);

use App\Enums\Customer\CustomerStatusEnum;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
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

it('can delete customer from edit page', function () {
    $customer = Customer::factory()->create();

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('delete');

    expect(Customer::find($customer->id))->toBeNull();
});

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
        ->callAction('activate');

    $customer->refresh();

    expect($customer->status)->toBe(CustomerStatusEnum::ACTIVE);
});

it('can deactivate customer from view page', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('deactivate');

    $customer->refresh();

    expect($customer->status)->toBe(CustomerStatusEnum::INACTIVE);
});

it('can suspend customer from view page', function () {
    $customer = Customer::factory()->create([
        Customer::COLUMN_STATUS => CustomerStatusEnum::ACTIVE,
    ]);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
        ->callAction('suspend');

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
