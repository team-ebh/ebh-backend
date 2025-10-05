<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminInitializerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('auth-credentials') as $credentials) {
            $this->createUserIfDontExists($credentials);
        }
    }

    public function createUserIfDontExists(array $userInfo): void
    {
        if (Admin::query()
            ->where('email', $userInfo['email'])
            ->exists()) {
            return;
        }

        $user = new Admin;
        $user->{Admin::COLUMN_NAME} = $userInfo['name'];
        $user->{Admin::COLUMN_EMAIL} = $userInfo['email'];
        $user->{Admin::COLUMN_PASSWORD} = $userInfo['password'];
        $user->save();
    }
}
