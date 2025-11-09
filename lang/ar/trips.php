<?php

declare(strict_types=1);

use App\Enums\Trip\AccessibilityRequirementsEnum;
use App\Enums\Trip\RideTypeEnum;
use App\Enums\Trip\TripStatusEnum;
use App\Enums\Trip\TripTypeEnum;
use App\Enums\Trip\TripVehicleTypeEnum;

return [
    'location_types' => [
        'origin' => 'نقطة الانطلاق',
        'destination' => 'الوجهة',
    ],
    'location_statuses' => [
        'draft' => 'مسودة',
        'pending_rider' => 'في انتظار السائق',
        'accepted_rider' => 'مقبولة من السائق',
        'arrived' => 'وصل',
        'canceled_by_customer' => 'ملغاة من قبل العميل',
        'cancelled_by_rider' => 'ملغاة من قبل السائق',
        'picked_up' => 'تم الاستلام',
        'completed' => 'مكتملة',
    ],
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
            TripStatusEnum::DRAFT->name => 'مسودة',
            TripStatusEnum::PENDING_RIDER->name => 'في انتظار السائق',
            TripStatusEnum::ACCEPTED_RIDER->name => 'مقبولة من السائق',
            TripStatusEnum::ARRIVED->name => 'وصل',
            TripStatusEnum::CANCELED_BY_CUSTOMER->name => 'ملغاة من قبل العميل',
            TripStatusEnum::CANCELLED_BY_RIDER->name => 'ملغاة من قبل السائق',
            TripStatusEnum::PICKED_UP->name => 'تم الاستلام',
            TripStatusEnum::COMPLETED->name => 'مكتملة',
        ],
        'price_estimation' => 'تقدير السعر',
        'waiting_time_rate_description' => ':price لكل :minutes دقيقة',
        'time_units' => [
            'minutes' => 'دقيقة',
            'hours' => 'ساعة',
        ],
        'breakdown' => [
            'base_fare' => 'الأجرة الأساسية',
            'one_way' => 'ذهاب فقط',
            'return_fare' => 'أجرة العودة',
            'return_trip' => 'رحلة العودة',
            'round_trip_fee' => 'الذهاب والعودة',
            'waiting_time_charge' => 'رسوم وقت الانتظار',
            'accessibility_services' => 'خدمات إمكانية الوصول',
            'to_be_calculated' => 'سيتم حسابها',
            'included' => 'مشمول',
        ],
        'exceptions' => [
            'trip_not_pending' => 'لا يمكن تأكيد هذه الرحلة. يمكن فقط تأكيد الرحلات المسودة.',
            'trip_cannot_be_cancelled' => 'لا يمكن إلغاء هذه الرحلة. يمكن فقط إلغاء الرحلات المسودة أو في انتظار السائق.',
            'rider_location_not_available' => 'موقع السائق غير متاح. تتبع الموقع متاح فقط عندما يقبل السائق أو يصل أو يتم الاستلام.',
            'trip_status_cannot_be_checked' => 'لا يمكن التحقق من حالة الرحلة. التحقق من الحالة غير متاح للرحلات المسودة أو الملغاة أو المكتملة.',
        ],
    ],
];
