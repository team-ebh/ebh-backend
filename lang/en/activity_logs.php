<?php

declare(strict_types=1);

return [
    'admin' => [
        'model_label' => 'Activity Log',
        'plural_model_label' => 'Activity Logs',
        'navigation_label' => 'Activity History',

        'fields' => [
            'event' => 'Event Type',
            'event_type' => 'Event Type',
            'log_type' => 'Log Type',
            'date_time' => 'Date & Time',
            'description' => 'Description',
            'subject' => 'Subject',
            'subject_model' => 'Model',
            'subject_id' => 'ID',
            'record_id' => 'Record ID',
            'subject_link' => 'View Record',
            'causer' => 'Performer',
            'causer_name' => 'Name',
            'causer_type' => 'Type & ID',
            'performed_by' => 'Performed By',
            'changes' => 'Changes',
            'changes_detail' => 'Changes Detail',
            'field' => 'Field',
            'previous_value' => 'Previous Value',
            'new_value' => 'New Value',
            'batch' => 'Batch',
            'batch_uuid' => 'Batch ID',
        ],

        'table' => [
            'filters' => [
                'log_type' => 'Log Type',
                'event' => 'Event',
                'model' => 'Model',
            ],
            'description' => 'Showing activities for :model #:id',
        ],

        'sections' => [
            'header' => 'Activity Information',
            'subject' => 'Subject Information',
            'subject_description' => 'The record that was affected by this activity',
            'performer' => 'Performer Information',
            'performer_description' => 'Who performed this activity',
            'changes' => 'Changes',
            'changes_description' => 'Detailed view of what was changed',
            'batch' => 'Batch Information',
            'batch_description' => 'Activities grouped in a batch operation',
        ],

        'events' => [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
        ],

        'messages' => [
            'no_changes' => 'No changes recorded',
            'no_detailed_changes' => 'No detailed changes available',
            'system' => 'System',
            'copy' => 'Copy',
            'null' => 'null',
            'empty_array' => 'empty array',
            'true' => 'true',
            'false' => 'false',
        ],
    ],
];
