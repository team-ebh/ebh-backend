<?php

declare(strict_types=1);

return [
    'admin' => [
        'fields' => [
            'full_name' => 'Full Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'company' => 'Company',
            'status' => 'Status',
            'enabled' => 'Is Enabled?',
            'profile_photo' => 'Profile Photo',
            'accessibility_certifications' => [
                'label' => 'Accessibility Certifications',
                'wheelchair_accessible' => 'Wheelchair Accessible',
                'arabic_sign_language' => 'Arabic Sign Language',
                'visual_assistance' => 'Visual Assistance',
                'hearing_assistance' => 'Hearing Assistance',
            ],
        ],
        'statuses' => [
            'online' => 'Online',
            'offline' => 'Offline',
            'busy' => 'Busy',
            'deleted' => 'Deleted',
        ],
        'model_label' => 'Rider',
        'plural_model_label' => 'Riders',
        'navigation_label' => 'Riders',
        'stats' => [
            'total_riders' => 'Total Riders',
            'total_riders_description' => 'All registered riders',
            'online_now' => 'Online Now',
            'online_now_description' => 'Currently online riders',
            'accessibility_certified' => 'Accessibility Certified',
            'accessibility_certified_description' => 'Riders with accessibility training',
            'avg_rating' => 'Avg Rating',
            'avg_rating_description' => 'Average rider rating',
        ],
        'form' => [
            'personal_information' => 'Personal Information',
            'personal_information_description' => 'Enter the rider\'s basic personal information',
            'profile_photo_helper' => 'Upload a square profile photo (max 2MB). JPG, JPEG, WEBP or PNG formats are accepted.',
            'accessibility_certifications' => 'Accessibility Certifications',
            'accessibility_certifications_description' => 'Select the accessibility certifications this rider has',
            'documents' => 'Documents',
            'documents_description' => 'Upload required documents for the rider',
            'add_document' => 'Add Document',
            'document_file' => 'Document File',
        ],
        'infolist' => [
            'personal_information' => 'Personal Information',
            'personal_information_description' => 'Basic personal details of the rider',
            'status_information' => 'Status Information',
            'status_information_description' => 'Current status and accessibility certifications',
            'vehicle_information' => 'Vehicle Information',
            'vehicle_information_description' => 'Details about the rider\'s vehicle',
            'documents' => 'Documents',
            'documents_description' => 'Uploaded documents and certificates',
            'timestamps' => 'Timestamps',
        ],
        'exceptions' => [
            'has_active_trip' => 'Cannot disable rider. The rider has an active trip in progress.',
            'cannot_edit_deleted' => 'Cannot edit a deleted rider account.',
        ],
    ],
    'api' => [
        'validation' => [
            'decline_reason' => [
                'required' => 'Decline reason is required',
                'max' => 'Decline reason must not exceed 500 characters',
            ],
            'status' => [
                'required' => 'Status is required',
                'in' => 'Status must be either online or offline',
            ],
            'profile' => [
                'full_name' => [
                    'required' => 'Full name is required',
                    'string' => 'Full name must be a string',
                    'min' => 'Full name must be at least 2 characters',
                    'max' => 'Full name must not exceed 60 characters',
                ],
                'email' => [
                    'email' => 'Please enter a valid email address',
                    'max' => 'Email must not exceed 255 characters',
                ],
                'phone_number' => [
                    'required' => 'Phone number is required',
                    'regex' => 'Phone number must be exactly 8 digits',
                ],
                'image' => [
                    'required' => 'Profile image is required',
                    'image' => 'File must be an image',
                    'mimes' => 'Image must be a JPEG, JPG, PNG, or WebP file',
                    'max' => 'Image size must not exceed 2MB',
                ],
            ],
        ],
        'errors' => [
            'trip_not_available' => 'Trip is no longer available',
            'trip_already_assigned' => 'This trip has already been assigned to another rider',
            'rider_not_available' => 'Rider is not available',
            'rider_must_be_available' => 'You must be available to accept trips',
        ],
        'exceptions' => [
            'account_disabled' => 'Your account is disabled. Please contact support.',
            'account_deleted' => 'Your account has been deleted.',
            'cannot_change_status' => 'Cannot change status. You must complete or cancel your active trip first, or wait until you are not busy.',
        ],
        'success' => [
            'trip_accepted' => 'Trip accepted successfully',
            'trip_declined' => 'Trip declined successfully',
            'status_updated' => 'Status updated successfully',
        ],
        'location_updated_successfully' => 'Location updated successfully',
        'earnings' => [
            'filters' => [
                'today' => 'Today',
                'this_week' => 'This Week',
                'past_trips' => 'Past Trips',
            ],
            'validation' => [
                'filter' => [
                    'enum' => 'The selected filter is invalid.',
                ],
            ],
            'comparison' => [
                'no_change' => 'No change from previous period',
                'text' => ':prefix:percentage% from :period',
                'periods' => [
                    'yesterday' => 'yesterday',
                    'last_week' => 'last week',
                ],
            ],
            'route_text' => 'From :from to :to',
        ],
    ],
    'location_updated_successfully' => 'Location updated successfully',
];
