<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'model_type' => Admin::class,
            'model_id' => Admin::factory(),
            'uuid' => $this->faker->uuid(),
            'collection_name' => $this->faker->name(),
            'file_name' => $this->faker->imageUrl,
            'name' => $this->faker->name,
            'disk' => 'public',
            'size' => $this->faker->randomNumber(),
            'mime_type' => $this->faker->mimeType(),
            'conversions_disk' => json_encode([]),
            'manipulations' => json_encode([]),
            'custom_properties' => json_encode([]),
            'generated_conversions' => json_encode([]),
            'responsive_images' => json_encode([]),
            'order_column' => $this->faker->randomNumber(),
        ];
    }
}
