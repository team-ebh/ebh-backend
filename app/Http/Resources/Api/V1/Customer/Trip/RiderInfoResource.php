<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Customer\Trip;

use App\Enums\Rider\AccessibilityCertificationEnum;
use App\Http\Resources\Api\V1\Customer\PhoneCodeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Rider Info Resource
 *
 * Formats rider information for trip status response
 */
class RiderInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /**
             * Rider ID
             *
             * @example 1
             *
             * @var int
             */
            'id' => $this->resource['id'],

            /**
             * Rider Image
             *
             * @example "https://api.ebhapp.com/riders/avatars/1.png"
             *
             * @var string
             */
            'image' => $this->resource['image'],

            /**
             * Rider Name
             *
             * @example "Ahmed Al-Mansour"
             *
             * @var string
             */
            'name' => $this->resource['name'],

            /**
             * Phone information
             *
             * @var PhoneCodeResource
             */
            'phone' => new PhoneCodeResource($this->resource['phone_number']),

            /**
             * Rider Rating
             *
             * Average rating from 0 to 5
             *
             * @example 4.8
             *
             * @var float
             */
            'rating' => $this->resource['rating'],

            /**
             * Accessibility Certifications
             *
             * List of accessibility certifications the rider has
             *
             * @example ["wheelchair_accessible", "hearing_assistance"]
             *
             * @var array<string>
             */
            'accessibility_certifications' => $this->formatAccessibilityCertifications(),
        ];
    }

    /**
     * Format accessibility certifications with labels
     *
     * @return array<int, array{id: string, label: string}>
     */
    private function formatAccessibilityCertifications(): array
    {
        $certifications = $this->resource['accessibility_certifications'] ?? [];

        // Check if certifications is already an array of certification types (from new table structure)
        if (empty($certifications)) {
            return [];
        }

        // If it's a collection of objects (from relationship), extract certification_type
        if (is_object($certifications) && method_exists($certifications, 'pluck')) {
            $certifications = $certifications->pluck('certification_type')->toArray();
        }

        return collect($certifications)
            ->map(function (AccessibilityCertificationEnum $certification) {

                return [
                    'id' => $certification->value,
                    'label' => $certification->getLabel(),
                ];
            })
            ->values()
            ->toArray();
    }
}
