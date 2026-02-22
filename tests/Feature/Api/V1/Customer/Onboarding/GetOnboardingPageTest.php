<?php

declare(strict_types=1);

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Models\OnboardingPage;
use App\Models\OnboardingPageBanner;

beforeEach(function () {
    $this->url = route('v1.customers.onboarding.index');
});

it('returns onboarding page with banners for customer app', function () {
    $page = OnboardingPage::factory()->enabled()->forCustomer()->create();

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

it('returns banners in correct sort order', function () {
    $page = OnboardingPage::factory()->enabled()->forCustomer()->create();

    $banner3 = OnboardingPageBanner::factory()->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
        OnboardingPageBanner::COLUMN_SORT => 3,
        OnboardingPageBanner::COLUMN_TITLE => 'Third',
    ]);
    $banner1 = OnboardingPageBanner::factory()->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
        OnboardingPageBanner::COLUMN_SORT => 1,
        OnboardingPageBanner::COLUMN_TITLE => 'First',
    ]);
    $banner2 = OnboardingPageBanner::factory()->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
        OnboardingPageBanner::COLUMN_SORT => 2,
        OnboardingPageBanner::COLUMN_TITLE => 'Second',
    ]);

    $response = $this->getJson($this->url);

    $response->assertOk();

    $banners = $response->json('data.banners');
    expect($banners[0]['id'])->toBe($banner1->id)
        ->and($banners[1]['id'])->toBe($banner2->id)
        ->and($banners[2]['id'])->toBe($banner3->id);
});

it('returns 404 when no enabled customer onboarding page exists', function () {
    $this->getJson($this->url)->assertNotFound();
});

it('does not return disabled onboarding page', function () {
    OnboardingPage::factory()->forCustomer()->create([
        OnboardingPage::COLUMN_ENABLED => false,
    ]);

    $this->getJson($this->url)->assertNotFound();
});

it('does not return rider onboarding page for customer endpoint', function () {
    $page = OnboardingPage::factory()->enabled()->forRider()->create();

    OnboardingPageBanner::factory()->count(3)->sequence(
        [OnboardingPageBanner::COLUMN_SORT => 1],
        [OnboardingPageBanner::COLUMN_SORT => 2],
        [OnboardingPageBanner::COLUMN_SORT => 3],
    )->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
    ]);

    $this->getJson($this->url)->assertNotFound();
});

it('returns translated title and subtitle in arabic locale', function () {
    $page = OnboardingPage::factory()->enabled()->forCustomer()->create();

    OnboardingPageBanner::factory()->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
        OnboardingPageBanner::COLUMN_TITLE => 'English Title',
        OnboardingPageBanner::COLUMN_TITLE_AR => 'عنوان عربي',
        OnboardingPageBanner::COLUMN_SUBTITLE => 'English Subtitle',
        OnboardingPageBanner::COLUMN_SUBTITLE_AR => 'عنوان فرعي عربي',
        OnboardingPageBanner::COLUMN_SORT => 1,
    ]);

    $response = $this->getJson($this->url, ['Language' => 'ar']);

    $response->assertOk();

    $banner = $response->json('data.banners.0');
    expect($banner['title'])->toBe('عنوان عربي')
        ->and($banner['subtitle'])->toBe('عنوان فرعي عربي');
});

it('returns english title and subtitle in english locale', function () {
    $page = OnboardingPage::factory()->enabled()->forCustomer()->create();

    OnboardingPageBanner::factory()->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
        OnboardingPageBanner::COLUMN_TITLE => 'English Title',
        OnboardingPageBanner::COLUMN_TITLE_AR => 'عنوان عربي',
        OnboardingPageBanner::COLUMN_SUBTITLE => 'English Subtitle',
        OnboardingPageBanner::COLUMN_SUBTITLE_AR => 'عنوان فرعي عربي',
        OnboardingPageBanner::COLUMN_SORT => 1,
    ]);

    $response = $this->getJson($this->url, ['Language' => 'en']);

    $response->assertOk();

    $banner = $response->json('data.banners.0');
    expect($banner['title'])->toBe('English Title')
        ->and($banner['subtitle'])->toBe('English Subtitle');
});
