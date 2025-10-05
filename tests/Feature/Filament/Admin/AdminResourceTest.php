<?php

declare(strict_types=1);

use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Models\Admin;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    adminPanelLogin();
});

//it('can not access the admins without permission.', function () {
//    get(AdminResource::getUrl())->assertForbidden();
//});

it('can render index page of the admin resource.', function () {
    adminPanelLogin();

    get(AdminResource::getUrl())->assertOk();
});

it('can list admins in the datatable.', function () {
    $admin1 = Admin::factory()->create(['name' => 'Test Admin 1']);
    $admin2 = Admin::factory()->create(['name' => 'Test Admin 2']);

    livewire(ListAdmins::class)
        ->call('loadTable')
        ->assertSuccessful()
        ->assertSeeHtml('Test Admin 1')
        ->assertSeeHtml('Test Admin 2');
});

it('can create admin.', function () {
    $admin = Admin::factory()->make();

    livewire(CreateAdmin::class)
        ->fillForm([
            Admin::COLUMN_NAME => $admin->{Admin::COLUMN_NAME},
            Admin::COLUMN_EMAIL => $admin->{Admin::COLUMN_EMAIL},
            Admin::COLUMN_PASSWORD => $admin->{Admin::COLUMN_PASSWORD},
            Admin::COLUMN_PHONE_NUMBER => $admin->{Admin::COLUMN_PHONE_NUMBER},
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $admin = Admin::query()->first();

    expect($admin)
        ->{Admin::COLUMN_NAME}->toBe($admin->{Admin::COLUMN_NAME})
        ->{Admin::COLUMN_EMAIL}->toBe($admin->{Admin::COLUMN_EMAIL})
        ->{Admin::COLUMN_PASSWORD}->toBe($admin->{Admin::COLUMN_PASSWORD})
        ->{Admin::COLUMN_PHONE_NUMBER}->toBe($admin->{Admin::COLUMN_PHONE_NUMBER});
});

it('can render edit admin page.', function () {
    get(AdminResource::getUrl('edit', [
        'record' => Admin::factory()->create(),
    ]))
        ->assertSuccessful();
});

it('can update admin.', function () {
    $admin = Admin::factory()->create();
    $newAdmin = Admin::factory()->make();

    livewire(EditAdmin::class, [
        'record' => $admin->getKey(),
    ])
        ->fillForm([
            Admin::COLUMN_NAME => $newAdmin->{Admin::COLUMN_NAME},
//            Admin::COLUMN_EMAIL => $newAdmin->{Admin::COLUMN_EMAIL}, email can not be change.
            Admin::COLUMN_PASSWORD => $newAdmin->{Admin::COLUMN_PASSWORD},
            Admin::COLUMN_PHONE_NUMBER => $newAdmin->{Admin::COLUMN_PHONE_NUMBER},
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($admin->refresh())
        ->{Admin::COLUMN_NAME}->toBe($newAdmin->{Admin::COLUMN_NAME})
        ->{Admin::COLUMN_EMAIL}->toBe($admin->{Admin::COLUMN_EMAIL}) // email can not be change.
        ->{Admin::COLUMN_PASSWORD}->toBe($newAdmin->{Admin::COLUMN_PASSWORD})
        ->{Admin::COLUMN_PHONE_NUMBER}->toBe($newAdmin->{Admin::COLUMN_PHONE_NUMBER});
});

//it('can render view admin page.', function () {
//
//    get(AdminResource::getUrl('view', [
//        'record' => Admin::factory()->create(),
//    ]))
//        ->assertSuccessful();
//});

it('can reset admin password.', function () {
    $admin = Admin::factory()->create();
    $oldPassword = $admin->{Admin::COLUMN_PASSWORD};
    $newPassword = 'NewPassword123!';

    livewire(ListAdmins::class)
        ->call('loadTable')
        ->callTableAction('resetPassword', $admin, [
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ])
        ->assertHasNoTableActionErrors();

    expect($admin->refresh())
        ->{Admin::COLUMN_PASSWORD}->not->toBe($oldPassword);
});
