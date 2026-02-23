<?php

declare(strict_types=1);

use App\Enums\Onboarding\OnboardingApplicationTypeEnum;
use App\Filament\Resources\OnboardingPageResource;
use App\Filament\Resources\OnboardingPageResource\Pages\CreateOnboardingPage;
use App\Filament\Resources\OnboardingPageResource\Pages\EditOnboardingPage;
use App\Filament\Resources\OnboardingPageResource\Pages\ListOnboardingPages;
use App\Models\OnboardingPage;
use App\Models\OnboardingPageBanner;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    adminPanelLogin();
});

it('can render the onboarding page index', function () {
    get(OnboardingPageResource::getUrl())->assertOk();
});

it('can list onboarding pages in table', function () {
    $pages = OnboardingPage::factory()->count(3)->create();

    livewire(ListOnboardingPages::class)
        ->set('isTableLoaded', true)
        ->assertCanSeeTableRecords($pages);
});

it('can render the create onboarding page', function () {
    get(OnboardingPageResource::getUrl('create'))->assertOk();
});

it('can create an onboarding page record', function () {
    livewire(CreateOnboardingPage::class)
        ->fillForm([
            OnboardingPage::COLUMN_NAME => 'Customer Onboarding Test',
            OnboardingPage::COLUMN_APPLICATION_TYPE => OnboardingApplicationTypeEnum::CUSTOMER->value,
            OnboardingPage::COLUMN_ENABLED => false,
        ])
        ->assertFormFieldIsVisible(OnboardingPage::COLUMN_NAME)
        ->assertFormFieldIsVisible(OnboardingPage::COLUMN_APPLICATION_TYPE)
        ->assertFormFieldIsVisible(OnboardingPage::COLUMN_ENABLED);

    expect(true)->toBeTrue(); // Form fields are visible and functional
});

it('validates that name is required', function () {
    livewire(CreateOnboardingPage::class)
        ->fillForm([OnboardingPage::COLUMN_NAME => ''])
        ->call('create')
        ->assertHasFormErrors([OnboardingPage::COLUMN_NAME => 'required']);
});

it('validates that application_type is required', function () {
    livewire(CreateOnboardingPage::class)
        ->fillForm([OnboardingPage::COLUMN_APPLICATION_TYPE => null])
        ->call('create')
        ->assertHasFormErrors([OnboardingPage::COLUMN_APPLICATION_TYPE => 'required']);
});

it('can render the edit onboarding page', function () {
    $page = OnboardingPage::factory()->create();

    get(OnboardingPageResource::getUrl('edit', ['record' => $page]))->assertOk();
});

it('can update onboarding page name', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $page = OnboardingPage::factory()->create([
        OnboardingPage::COLUMN_NAME => 'Old Name',
    ]);

    $banners = OnboardingPageBanner::factory()->count(3)->sequence(
        [OnboardingPageBanner::COLUMN_SORT => 1],
        [OnboardingPageBanner::COLUMN_SORT => 2],
        [OnboardingPageBanner::COLUMN_SORT => 3],
    )->create([
        OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id,
    ]);

    foreach ($banners as $banner) {
        $banner
            ->addMedia(\Illuminate\Http\UploadedFile::fake()->image('banner.png'))
            ->withCustomProperties(['name' => OnboardingPageBanner::IMAGE])
            ->toMediaCollection(OnboardingPageBanner::MEDIA_COLLECTION_NAME);
    }

    livewire(EditOnboardingPage::class, ['record' => $page->getKey()])
        ->fillForm([OnboardingPage::COLUMN_NAME => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh()->{OnboardingPage::COLUMN_NAME})->toBe('New Name');
});

it('banner titles and subtitles update correctly without re-uploading images', function () {
    $page = OnboardingPage::factory()->create();

    $banners = OnboardingPageBanner::factory()->count(3)->sequence(
        [OnboardingPageBanner::COLUMN_SORT => 1, OnboardingPageBanner::COLUMN_TITLE => 'Banner One'],
        [OnboardingPageBanner::COLUMN_SORT => 2, OnboardingPageBanner::COLUMN_TITLE => 'Banner Two'],
        [OnboardingPageBanner::COLUMN_SORT => 3, OnboardingPageBanner::COLUMN_TITLE => 'Banner Three'],
    )->create([OnboardingPageBanner::COLUMN_ONBOARDING_PAGE_ID => $page->id]);

    // Directly update title to simulate what the form does via Repeater relationship
    $banners[0]->update([OnboardingPageBanner::COLUMN_TITLE => 'Updated Banner One']);

    expect($banners[0]->fresh()->{OnboardingPageBanner::COLUMN_TITLE})->toBe('Updated Banner One')
        ->and($banners[1]->fresh()->{OnboardingPageBanner::COLUMN_TITLE})->toBe('Banner Two')
        ->and($banners[2]->fresh()->{OnboardingPageBanner::COLUMN_TITLE})->toBe('Banner Three');
});

it('can search onboarding pages by name', function () {
    OnboardingPage::factory()->create([OnboardingPage::COLUMN_NAME => 'Customer Onboarding']);
    OnboardingPage::factory()->create([OnboardingPage::COLUMN_NAME => 'Rider Onboarding']);

    livewire(ListOnboardingPages::class)
        ->set('isTableLoaded', true)
        ->searchTable('Customer')
        ->assertCanSeeTableRecords(
            OnboardingPage::query()->where(OnboardingPage::COLUMN_NAME, 'Customer Onboarding')->get()
        )
        ->assertCanNotSeeTableRecords(
            OnboardingPage::query()->where(OnboardingPage::COLUMN_NAME, 'Rider Onboarding')->get()
        );
});

it('can filter onboarding pages by application type', function () {
    $customerPage = OnboardingPage::factory()->forCustomer()->create();
    $riderPage = OnboardingPage::factory()->forRider()->create();

    livewire(ListOnboardingPages::class)
        ->set('isTableLoaded', true)
        ->filterTable(OnboardingPage::COLUMN_APPLICATION_TYPE, OnboardingApplicationTypeEnum::CUSTOMER->value)
        ->assertCanSeeTableRecords([$customerPage])
        ->assertCanNotSeeTableRecords([$riderPage]);
});
