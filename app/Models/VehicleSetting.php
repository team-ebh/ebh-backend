<?php

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\Model\TranslatableInterface;
use App\Traits\Model\HasDefaultColumnModelTrait;
use App\Traits\Model\HasTranslatable;
use App\Traits\Model\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleSetting extends Model implements TranslatableInterface
{
    use HasDefaultColumnModelTrait;
    use HasTranslatable;
    use LogsActivity;
    use SoftDeletes;

    public const string COLUMN_TYPE = 'type';

    public const string COLUMN_NAME = 'name';

    public const string COLUMN_NAME_AR = 'name_ar';

    public const string COLUMN_CAPACITY = 'capacity';

    public const string COLUMN_ORDER = 'order';

    public const string TYPE_CAR_TYPES = 'car_types';

    public const string TYPE_CAR_COLORS = 'car_colors';

    public const string TYPE_PASSENGER_CAPACITY = 'passenger_capacity';

    public const string TYPE_CAR_MAKES = 'car_makes';

    public const string TYPE_CAR_MODELS = 'car_models';

    public const string TYPE_VEHICLE_TYPES = 'vehicle_types';

    public function getTranslatableColumns(): array
    {
        return [self::COLUMN_NAME];
    }

    public static function getByType(string $type): Collection
    {
        return self::where(self::COLUMN_TYPE, $type)
            ->orderBy(self::COLUMN_ORDER)
            ->get();
    }

    public static function createItem(string $type, string $name, string $nameAr, ?int $capacity = null, int $order = 0): self
    {
        return self::create([
            self::COLUMN_TYPE => $type,
            self::COLUMN_NAME => $name,
            self::COLUMN_NAME_AR => $nameAr,
            self::COLUMN_CAPACITY => $capacity,
            self::COLUMN_ORDER => $order,
        ]);
    }

    public static function updateOrCreateItems(string $type, array $items): void
    {
        $submittedIds = [];

        // Update or create items
        foreach ($items as $index => $item) {
            $capacity = null;
            if (isset($item['capacity'])) {
                $capacity = is_numeric($item['capacity']) ? (int) $item['capacity'] : null;
            }

            $name = $item['name'] ?? '';
            $nameAr = $item['name_ar'] ?? '';
            $id = $item['id'] ?? null;

            if ($id) {
                // Update existing item by ID
                $existingItem = self::find($id);
                if ($existingItem) {
                    $existingItem->update([
                        self::COLUMN_NAME => $name,
                        self::COLUMN_NAME_AR => $nameAr,
                        self::COLUMN_CAPACITY => $capacity,
                        self::COLUMN_ORDER => $index,
                    ]);
                    $submittedIds[] = $existingItem->id;
                }
            } else {
                // Create new item
                $newItem = self::createItem(
                    type: $type,
                    name: $name,
                    nameAr: $nameAr,
                    capacity: $capacity,
                    order: $index
                );
                $submittedIds[] = $newItem->id;
            }
        }

        // Delete items that were not in the submitted list
        self::where(self::COLUMN_TYPE, $type)
            ->whereNotIn('id', $submittedIds)
            ->delete();
    }

    public static function getOptionsForSelect(string $type, string $labelColumn = self::COLUMN_NAME): array
    {
        return self::getByType($type)
            ->pluck($labelColumn, 'id')
            ->toArray();
    }

    public static function addItem(string $type, string $name, string $nameAr, ?int $capacity = null): int
    {
        $maxOrder = self::where(self::COLUMN_TYPE, $type)->max(self::COLUMN_ORDER) ?? -1;

        $item = self::createItem(
            type: $type,
            name: $name,
            nameAr: $nameAr,
            capacity: $capacity,
            order: $maxOrder + 1
        );

        return $item->id;
    }

    public static function findItemById(int $id): ?array
    {
        $item = self::find($id);

        if (! $item) {
            return null;
        }

        return [
            'name' => $item->{self::COLUMN_NAME},
            'name_ar' => $item->{self::COLUMN_NAME_AR},
            'capacity' => $item->{self::COLUMN_CAPACITY},
        ];
    }

    public static function updateItemById(int $id, string $newName, string $newNameAr, ?int $newCapacity = null): int
    {
        $item = self::find($id);

        if ($item) {
            $item->update([
                self::COLUMN_NAME => $newName,
                self::COLUMN_NAME_AR => $newNameAr,
                self::COLUMN_CAPACITY => $newCapacity,
            ]);
        }

        return $id;
    }
}
