<?php

declare(strict_types=1);

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;

return [
    'api' => [
        'trip_types' => [
            TripTypeEnum::RIDE_NOW->name => 'رحلة الآن',
            TripTypeEnum::SCHEDULED->name => 'مجدولة',
        ],
        'ride_types' => [
            RideTypeEnum::ONE_WAY->name => 'رحلة ذهاب فقط',
            RideTypeEnum::ONE_WAY->name . '_description' => 'رحلة واحدة بدون عودة',
            RideTypeEnum::ROUND_TRIP->name => 'رحلة ذهاب وعودة',
            RideTypeEnum::ROUND_TRIP->name . '_description' => 'الذهاب والعودة',
            RideTypeEnum::ROUND_TRIP_WAIT->name => 'رحلة ذهاب وعودة مع انتظار',
            RideTypeEnum::ROUND_TRIP_WAIT->name . '_description' => 'السائق ينتظر حتى الجاهزية',
        ],
        'vehicle_types' => [
            TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->name => 'متاح للكراسي المتحركة',
            TripVehicleTypeEnum::WHEELCHAIR_ACCESSIBLE->name . '_description' => 'نقل الكراسي المتحركة القياسي',
            TripVehicleTypeEnum::BED_TRANSPORT->name => 'نقل السرير',
            TripVehicleTypeEnum::BED_TRANSPORT->name . '_description' => 'لنقل النقالات والأسرّة',
        ],
        'accessibility_requirements' => [
            AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->name => 'متاح للكراسي المتحركة',
            AccessibilityRequirementsEnum::WHEELCHAIR_ACCESSIBLE->name . '_description' => 'نقل الكراسي المتحركة القياسي',
            AccessibilityRequirementsEnum::OXYGEN_SUPPORT->name => 'دعم الأكسجين',
            AccessibilityRequirementsEnum::OXYGEN_SUPPORT->name . '_description' => 'دعم الأكسجين المحمول',
            AccessibilityRequirementsEnum::PORTABLE_RAMP->name => 'منحدر محمول',
            AccessibilityRequirementsEnum::PORTABLE_RAMP->name . '_description' => 'مجهز بمنحدر للوصول',
        ],
        'trip_statuses' => [
            TripStatusEnum::PENDING->name => 'قيد الانتظار',
            TripStatusEnum::CONFIRMED->name => 'مؤكدة',
            TripStatusEnum::DRIVER_ASSIGNED->name => 'تم تعيين السائق',
            TripStatusEnum::IN_PROGRESS->name => 'قيد التنفيذ',
            TripStatusEnum::ARRIVED->name => 'وصل',
            TripStatusEnum::COMPLETED->name => 'مكتملة',
            TripStatusEnum::CANCELLED->name => 'ملغاة',
            TripStatusEnum::CANCELLED_BY_DRIVER->name => 'ملغاة من قبل السائق',
        ],
        'price_estimation' => 'تقدير السعر',
        'waiting_time_rate_description' => ':price لكل :minutes دقيقة',
        'breakdown' => [
            'base_fare' => 'الأجرة الأساسية',
            'one_way' => 'ذهاب فقط',
            'return_fare' => 'أجرة العودة',
            'return_trip' => 'رحلة العودة',
            'round_trip_fee' => 'رسوم الذهاب والعودة',
            'waiting_time_charge' => 'رسوم وقت الانتظار',
            'accessibility_services' => 'خدمات إمكانية الوصول',
            'to_be_calculated' => 'سيتم حسابها',
        ],
    ],
];
