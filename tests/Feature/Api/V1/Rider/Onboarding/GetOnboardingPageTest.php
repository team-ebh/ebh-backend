<?php

declare(strict_types=1);

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Models\OnboardingPage;
use App\Models\OnboardingPageBanner;

beforeEach(function () {
    $this->url = route('v1.riders.onboarding.index');
});

it('returns onboarding page with banners for rider app', function () {
    $page = OnboardingPage::factory()->enabled()->forRider()->create();

    OnboardingPageBanner::factory()->count(3)->sequence(
        [OnboardingPageBanner::COLUMN_SORT => 1, OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id],
        [OnboardingPageBanner::COLUMN_SORT => 2, OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id],
        [OnboardingPageBanner::COLUMN_SORT => 3, OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id],
    )->create([OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id]);

    $response = $this->getJson($this->url);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'banners' => [
                    '*' => [
                        'id',
                        'title',
                        'subtitle',
                        'image',
                    ],
                ],
            ],
        ]);

    expect($response->json('data.banners'))->toHaveCount(3);
});

it('returns 404 when no enabled rider onboarding page exists', function () {
    $this->getJson($this->url)->assertNotFound();
});

it('does not return disabled onboarding page', function () {
    OnboardingPage::factory()->forRider()->create([
        OnboardingPage::COLUMN_ENABLED => false,
    ]);

    $this->getJson($this->url)->assertNotFound();
});

it('does not return customer onboarding page for rider endpoint', function () {
    $page = OnboardingPage::factory()->enabled()->forCustomer()->create();

    OnboardingPageBanner::factory()->count(3)->sequence(
        [OnboardingPageBanner::COLUMN_SORT => 1],
        [OnboardingPageBanner::COLUMN_SORT => 2],
        [OnboardingPageBanner::COLUMN_SORT => 3],
    )->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
    ]);

    $this->getJson($this->url)->assertNotFound();
});

it('returns translated content in arabic for rider', function () {
    $page = OnboardingPage::factory()->enabled()->forRider()->create();

    OnboardingPageBanner::factory()->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
        OnboardingPageBanner::COLUMN_TITLE => 'Go Live and Accept Rides',
        OnboardingPageBanner::COLUMN_TITLE_AR => 'ابدأ واقبل الرحلات',
        OnboardingPageBanner::COLUMN_SUBTITLE => 'Tap the button to go online.',
        OnboardingPageBanner::COLUMN_SUBTITLE_AR => 'اضغط الزر للدخول إلى الخدمة.',
        OnboardingPageBanner::COLUMN_SORT => 1,
    ]);

    $response = $this->getJson($this->url, ['Language' => 'ar']);

    $response->assertOk();

    $banner = $response->json('data.banners.0');
    expect($banner['title'])->toBe('ابدأ واقبل الرحلات')
        ->and($banner['subtitle'])->toBe('اضغط الزر للدخول إلى الخدمة.');
});
