<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ApplicationEnvironmentEnum;
use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Models\OnboardingPage;
use App\Models\OnboardingPageBanner;
use Illuminate\Database\Seeder;

class OnboardingPageSeeder extends Seeder
{
    public function run(): void
    {
        if (ApplicationEnvironmentEnum::isProduction() && OnboardingPage::query()->exists()) {
            $this->command->info('Onboarding pages already seeded. Skipping on production.');

            return;
        }

        $this->seedForApplicationType(
            name: 'Customer Onboarding',
            type: OnboardingApplicationTypeEnum::CUSTOMER,
            banners: $this->getCustomerBanners(),
        );

        $this->seedForApplicationType(
            name: 'Rider Onboarding',
            type: OnboardingApplicationTypeEnum::RIDER,
            banners: $this->getRiderBanners(),
        );

        $this->command->info('Onboarding pages seeded successfully!');
    }

    private function seedForApplicationType(string $name, OnboardingApplicationTypeEnum $type, array $banners): void
    {
        $page = OnboardingPage::query()->updateOrCreate(
            [OnboardingPage::COLUMN_APPLICATION_TYPE => $type->value],
            [
                OnboardingPage::COLUMN_NAME => $name,
                OnboardingPage::COLUMN_ENABLED => true,
            ]
        );

        $now = now();

        $rows = array_map(fn (array $banner) => [
            OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->{OnboardingPage::COLUMN_ID},
            OnboardingPageBanner::COLUMN_TITLE => $banner['title'],
            OnboardingPageBanner::COLUMN_TITLE_AR => $banner['title_ar'],
            OnboardingPageBanner::COLUMN_SUBTITLE => $banner['subtitle'],
            OnboardingPageBanner::COLUMN_SUBTITLE_AR => $banner['subtitle_ar'],
            OnboardingPageBanner::COLUMN_SORT => $banner['sort'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $banners);

        OnboardingPageBanner::upsert(
            $rows,
            uniqueBy: [
                OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID,
                OnboardingPageBanner::COLUMN_SORT,
            ],
            update: [
                OnboardingPageBanner::COLUMN_TITLE,
                OnboardingPageBanner::COLUMN_TITLE_AR,
                OnboardingPageBanner::COLUMN_SUBTITLE,
                OnboardingPageBanner::COLUMN_SUBTITLE_AR,
                'updated_at',
            ],
        );

        foreach ($banners as $banner) {
            $bannerModel = OnboardingPageBanner::query()
                ->where(OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID, $page->{OnboardingPage::COLUMN_ID})
                ->where(OnboardingPageBanner::COLUMN_SORT, $banner['sort'])
                ->first();

            if (! $bannerModel || $bannerModel->hasMedia(OnboardingPageBanner::MEDIA_COLLECTION_NAME)) {
                continue;
            }

            $sourcePath = public_path('images/onboarding/' . $banner['image_filename']);

            if (! file_exists($sourcePath)) {
                $this->command->warn("Image not found: {$sourcePath}. Skipping media.");

                continue;
            }

            $bannerModel
                ->addMedia($sourcePath)
                ->preservingOriginal()
                ->withCustomProperties(['name' => OnboardingPageBanner::IMAGE])
                ->toMediaCollection(OnboardingPageBanner::MEDIA_COLLECTION_NAME);
        }

        $this->command->info("Seeded {$type->value} onboarding with " . count($rows) . ' banners.');
    }

    private function getCustomerBanners(): array
    {
        return [
            [
                'title' => 'Choose Your Route',
                'title_ar' => 'اختر وجهتك',
                'subtitle' => 'Select your pickup and drop-off locations to get started with a safe and accessible ride.',
                'subtitle_ar' => 'حدد موقع الاستلام والوصول للحصول على رحلة آمنة ومريحة.',
                'image_filename' => 'customer-1.png',
                'sort' => 1,
            ],
            [
                'title' => 'Select Vehicle & Support',
                'title_ar' => 'اختر المركبة والدعم',
                'subtitle' => 'Select a vehicle type and accessibility options that match your comfort and support needs.',
                'subtitle_ar' => 'اختر نوع المركبة وخيارات إمكانية الوصول التي تتناسب مع احتياجاتك.',
                'image_filename' => 'customer-2.png',
                'sort' => 2,
            ],
            [
                'title' => 'Your Ride Is on the Way',
                'title_ar' => 'رحلتك في الطريق',
                'subtitle' => 'Your ride has been accepted and the rider is on the way to your pickup point.',
                'subtitle_ar' => 'تم قبول رحلتك والسائق في طريقه إلى موقع الاستلام.',
                'image_filename' => 'customer-3.png',
                'sort' => 3,
            ],
        ];
    }

    private function getRiderBanners(): array
    {
        return [
            [
                'title' => 'Go Live and Accept Rides',
                'title_ar' => 'ابدأ واقبل الرحلات',
                'subtitle' => 'Tap the button to go online and start receiving ride requests from passengers in your area.',
                'subtitle_ar' => 'اضغط الزر للدخول إلى الخدمة وبدء استقبال طلبات الرحلات من الركاب في منطقتك.',
                'image_filename' => 'rider-1.png',
                'sort' => 1,
            ],
            [
                'title' => 'Receive Ride Requests',
                'title_ar' => 'استقبل طلبات الرحلات',
                'subtitle' => 'Incoming ride requests will appear here. Tap Accept to start the trip and assist passengers safely.',
                'subtitle_ar' => 'ستظهر طلبات الرحلات الواردة هنا. اضغط على قبول لبدء الرحلة ومساعدة الركاب بأمان.',
                'image_filename' => 'rider-2.png',
                'sort' => 2,
            ],
            [
                'title' => "You're On the Way",
                'title_ar' => 'أنت في الطريق',
                'subtitle' => 'The passenger is waiting. Drive to the pickup location to start the ride.',
                'subtitle_ar' => 'الراكب ينتظرك. توجه إلى موقع الاستلام لبدء الرحلة.',
                'image_filename' => 'rider-3.png',
                'sort' => 3,
            ],
        ];
    }
}
