<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

use App\Models\Admin;
use App\Models\User;
use Database\Seeders\AdminInitializerSeeder;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function adminPanelLogin(?Admin $admin = null, bool $isSuperAdmin = true): void
{
    if ($isSuperAdmin) {
        seed(AdminInitializerSeeder::class);

        $admin = Admin::query()
            ->where(Admin::COLUMN_EMAIL, config('auth-credentials.admin.email'))
            ->first();
    }

    $admin ??= Admin::factory()
        ->enabled()
        ->create();

    actingAs($admin);
}

function apiPanelLogin(?User $user = null): void
{
    $user ??= User::factory()->create();

    actingAs($user);
}
