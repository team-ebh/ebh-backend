<?php

declare(strict_types=1);

namespace App\Traits\Model;

use App\Enums\LanguageEnum;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasTranslatableMedia
{
    /**
     * Get all media items (collection) for a specific field and language (in-memory, collection-based).
     * This avoids additional queries by using the eager-loaded media relation.
     *
     * @return Collection|Media[]
     */
    public function translatedMedia(?string $field = null, ?string $language = null): Collection
    {
        $localizedField = $this->getFieldBaseOnCurrentLocale($field, $language);

        return $this
            ->loadMissing('media')
            ->media
            ->when(
                $localizedField,
                fn ($query) => $query
                    ->filter(fn (Media $m) => data_get($m->custom_properties, 'name') === $localizedField)
            )
            ->sortBy('order_column')
            ->values();
    }

    /**
     * Get the first media item for a specific field and language (in-memory).
     */
    public function translatedFirstMedia(?string $field = null, ?string $language = null): ?Media
    {
        return $this->translatedMedia($field, $language)->first();
    }

    /**
     * Get the first translated media URL (first image link) for a specific field and language.
     */
    public function getFirstTranslatedMediaLink(?string $field = null, ?string $language = null): ?string
    {
        $media = $this->translatedFirstMedia($field, $language);

        return $media?->getUrl();
    }

    public function getFirstTranslatedMediaLinkWithFallback(?string $field = null, ?string $preferredLanguage = null): ?string
    {
        $preferredLanguage = $preferredLanguage ?: app()->getLocale();

        $media = $this->translatedFirstMedia($field, $preferredLanguage);

        if (! $media && $preferredLanguage !== LanguageEnum::ENGLISH->value) {
            $media = $this->translatedFirstMedia($field, LanguageEnum::ENGLISH->value);
        }

        return $media?->getUrl();
    }

    /**
     * Get all translated media URLs for a specific field and language.
     *
     * @return string[]
     */
    public function getTranslatedMediaLinks(?string $field = null, ?string $language = null): array
    {
        return $this->translatedMedia($field, $language)
            ->map(fn (Media $m) => $m->getUrl())
            ->all();
    }

    public function getTranslatedMediaLinksWithFallback(?string $field = null, ?string $preferredLanguage = null): array
    {
        $preferredLanguage = $preferredLanguage ?: app()->getLocale();

        $media = $this->translatedMedia($field, $preferredLanguage);

        if ($media->isEmpty() && $preferredLanguage !== LanguageEnum::ENGLISH->value) {
            $media = $this->translatedMedia($field, LanguageEnum::ENGLISH->value);
        }

        return $media
            ->map(fn (Media $m) => $m->getUrl())
            ->all();
    }

    /**
     * Determine the localized field suffix based on current or given locale.
     */
    private function getFieldBaseOnCurrentLocale(?string $field = null, ?string $language = null): ?string
    {
        if (! $field) {
            return null;
        }

        $locale = $language ?: app()->getLocale();

        // English (fallback) has no suffix
        if ($locale === LanguageEnum::ENGLISH->value) {
            return $field;
        }

        return "{$field}_{$locale}";
    }
}
