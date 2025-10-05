<?php

declare(strict_types=1);

namespace App\Traits\Model;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasMediaTrait
{
    use HasTranslatableMedia;
    use InteractsWithMedia;

    public function firstMedia(?string $customPropertyName = null): ?Media
    {
        return $this
            ->loadMissing('media')
            ->media
            ->when($customPropertyName, fn ($query) => $query->where('custom_properties.name', $customPropertyName))
            ->sortBy('order_column')
            ->first();
    }

    public function getFirstMediaLink(?string $customPropertyName = null): ?string
    {
        return $this->firstMedia($customPropertyName)?->getUrl();
    }

    public function getMediaLinks(): array
    {
        $medias = $this->media;

        $links = [];

        foreach ($medias as $media) {
            $links[] = $media->getUrl();
        }

        return $links;
    }
}
