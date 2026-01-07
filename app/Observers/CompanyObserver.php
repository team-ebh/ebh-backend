<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\Setting\SettingEnum;
use App\Models\Company;
use App\Models\Setting;

class CompanyObserver
{
    /**
     * Handle the Company "creating" event.
     * Set default commission rate when company is created.
     */
    public function creating(Company $company): void
    {
        // If commission_rate is not set, use default
        if ($company->{Company::COLUMN_COMMISSION_RATE} === null) {
            $company->{Company::COLUMN_COMMISSION_RATE} = Setting::get(SettingEnum::DEFAULT_COMMISSION_RATE);
            $company->{Company::COLUMN_IS_CUSTOM_RATE} = false;
        }
    }
}
