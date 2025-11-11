<?php

declare(strict_types=1);

return [
    'admin' => [
        'navigation_label' => 'Documents',
        'model_label' => 'Document',
        'plural_model_label' => 'Documents',
        'fields' => [
            'name' => 'Document Name',
            'description' => 'Description',
            'validity_period' => 'Validity Period',
            'accepted_formats' => 'Accepted Formats',
            'is_required' => 'This document is required for all riders',
            'is_required_helper' => 'If enabled, this document will be required when creating or editing a rider',
            'enabled' => 'Is Enabled?',
        ],
        'status' => [
            'no_file_uploaded' => 'No file uploaded',
            'expired' => 'Expired',
            'expires_soon' => 'Expires in :days days',
        ],
    ],
];
