<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Widgets;

use App\Enums\Customer\CustomerStatusEnum;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCustomers = Customer::query()->where(Customer::COLUMN_STATUS, '<>', CustomerStatusEnum::PENDING_VERIFICATION)->count();
        $activeCustomers = Customer::query()->where(Customer::COLUMN_STATUS, CustomerStatusEnum::ACTIVE)->count();
        $newThisMonth = Customer::query()
            ->whereMonth(Customer::COLUMN_CREATED_AT, now()->month)
            ->whereYear(Customer::COLUMN_CREATED_AT, now()->year)
            ->count();
        $suspendedUsers = Customer::query()->where(Customer::COLUMN_STATUS, CustomerStatusEnum::SUSPENDED)->count();

        return [
            Stat::make(trans('customers.admin.stats.total_customers'), (string) $totalCustomers)
                ->description(trans('customers.admin.stats.total_customers_description'))
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make(trans('customers.admin.stats.active_customers'), (string) $activeCustomers)
                ->description(trans('customers.admin.stats.active_customers_description'))
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(trans('customers.admin.stats.new_this_month'), (string) $newThisMonth)
                ->description(trans('customers.admin.stats.new_this_month_description'))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('info'),

            Stat::make(trans('customers.admin.stats.suspended_users'), (string) $suspendedUsers)
                ->description(trans('customers.admin.stats.suspended_users_description'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger'),
        ];
    }
}
