<?php

declare(strict_types=1);

namespace App\Traits\Filament;

trait HasFilamentNotifications
{
    protected function getCreatedNotificationTitle(): ?string
    {
        $className = class_basename(static::class);

        // Extract entity name from CreateEntityName class
        $entity = preg_replace('/(?<!^)([A-Z])/', ' $1', str_replace('Create', '', $className));

        return trans('general.admin.notifications.created', ['entity' => $entity]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        $className = class_basename(static::class);

        // Extract entity name from EditEntityName class
        $entity = preg_replace('/(?<!^)([A-Z])/', ' $1', str_replace('Edit', '', $className));

        return trans('general.admin.notifications.updated', ['entity' => $entity]);
    }
}
