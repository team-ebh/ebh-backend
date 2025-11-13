<?php

declare(strict_types=1);

namespace App\Traits\Model;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity as SpatieLogsActivity;

trait LogsActivity
{
    use SpatieLogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept([
                'created_at',
                'updated_at',
                'deleted_at',
                'email_verified_at',
                'password',
                'remember_token',
                'otp',
                'otp_expires_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(class_basename($this))
            ->setDescriptionForEvent(function (string $eventName) {
                $model = class_basename($this);
                $identifier = $this->getActivityIdentifier();

                return match ($eventName) {
                    'created' => "{$model} {$identifier} was created",
                    'updated' => "{$model} {$identifier} was updated",
                    'deleted' => "{$model} {$identifier} was deleted",
                    'restored' => "{$model} {$identifier} was restored",
                    default => "{$model} {$identifier} was {$eventName}",
                };
            });
    }

    /**
     * Get identifier for activity log description
     */
    protected function getActivityIdentifier(): string
    {
        // Try common identifier fields
        $identifierFields = ['name', 'title', 'full_name', 'email', 'phone_number', 'code', 'number'];

        foreach ($identifierFields as $field) {
            if (isset($this->{$field}) && ! empty($this->{$field})) {
                return "'{$this->{$field}}'";
            }
        }

        // Fallback to ID
        return "#{$this->id}";
    }

    /**
     * Get activity timeline for this model
     */
    public function getActivityTimeline()
    {
        return $this->activities()
            ->with('causer')
            ->latest()
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'event' => $activity->event,
                    'causer' => $activity->causer,
                    'causer_name' => $this->getCauserName($activity),
                    'properties' => $activity->properties,
                    'changes' => $this->formatChanges($activity),
                    'created_at' => $activity->created_at,
                    'formatted_date' => $activity->created_at->format('Y-m-d H:i:s'),
                    'diff_for_humans' => $activity->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get causer name from activity
     */
    protected function getCauserName($activity): string
    {
        if (! $activity->causer) {
            return 'System';
        }

        $causer = $activity->causer;

        // Check for common name fields
        if (isset($causer->name)) {
            return $causer->name;
        }

        if (isset($causer->full_name)) {
            return $causer->full_name;
        }

        if (isset($causer->email)) {
            return $causer->email;
        }

        return class_basename($causer) . ' #' . $causer->id;
    }

    /**
     * Format changes for display
     */
    protected function formatChanges($activity): array
    {
        $changes = [];
        $properties = $activity->properties->toArray();

        if (isset($properties['attributes']) && isset($properties['old'])) {
            $attributes = $properties['attributes'];
            $old = $properties['old'];

            foreach ($attributes as $key => $newValue) {
                $oldValue = $old[$key] ?? null;

                if ($oldValue !== $newValue) {
                    $changes[] = [
                        'field' => $this->formatFieldName($key),
                        'old' => $this->formatValue($oldValue),
                        'new' => $this->formatValue($newValue),
                    ];
                }
            }
        } elseif (isset($properties['attributes'])) {
            // For created event
            foreach ($properties['attributes'] as $key => $value) {
                if (! empty($value)) {
                    $changes[] = [
                        'field' => $this->formatFieldName($key),
                        'old' => null,
                        'new' => $this->formatValue($value),
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Format field name for display
     */
    protected function formatFieldName(string $field): string
    {
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Format value for display
     */
    protected function formatValue($value): string
    {
        if (is_null($value)) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
