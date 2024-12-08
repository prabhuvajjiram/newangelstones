<?php
require_once '../includes/config.php';
require_once '../includes/ContactManager.php';

try {
    $contactManager = new ContactManager($pdo);

    // Company Information Fields
    $companyFields = [
        [
            'field_name' => 'industry',
            'display_name' => 'Industry',
            'field_type' => 'dropdown',
            'field_group' => 'Company Info',
            'is_required' => true,
            'default_value' => null,
            'options' => json_encode([
                'Monument Delar',
                'Retail',
                'Manufacturing',
                'Services',
                'Supplier',
                'Cementery',
                'Individual',
                'Other'
            ])
        ],
        [
            'field_name' => 'annual_revenue',
            'display_name' => 'Annual Revenue',
            'field_type' => 'number',
            'field_group' => 'Company Info',
            'is_required' => false,
            'default_value' => null,
            'options' => null
        ],
        [
            'field_name' => 'website',
            'display_name' => 'Website',
            'field_type' => 'text',
            'field_group' => 'Company Info',
            'is_required' => false,
            'default_value' => null,
            'options' => null
        ]
    ];

    // Contact Information Fields
    $contactFields = [
        [
            'field_name' => 'job_title',
            'display_name' => 'Job Title',
            'field_type' => 'text',
            'field_group' => 'Contact Info',
            'is_required' => false,
            'default_value' => null,
            'options' => null
        ],
        [
            'field_name' => 'department',
            'display_name' => 'Department',
            'field_type' => 'dropdown',
            'field_group' => 'Contact Info',
            'is_required' => false,
            'default_value' => null,
            'options' => json_encode([
                'Executive',
                'Sales',
                'Marketing',
                'Operations',
                'Finance',
                'HR',
                'IT',
                'Other'
            ])
        ]
    ];

    // Additional Business Fields
    $businessFields = [
        [
            'field_name' => 'business_type',
            'display_name' => 'Business Type',
            'field_type' => 'dropdown',
            'field_group' => 'Business Info',
            'is_required' => false,
            'default_value' => null,
            'options' => json_encode([
                'Corporation',
                'LLC',
                'Partnership',
                'Sole Proprietorship',
                'Non-Profit',
                'Other'
            ])
        ],
        [
            'field_name' => 'employee_count',
            'display_name' => 'Number of Employees',
            'field_type' => 'dropdown',
            'field_group' => 'Business Info',
            'is_required' => false,
            'default_value' => null,
            'options' => json_encode([
                '1-10',
                '11-50',
                '51-200',
                '201-500',
                '501-1000',
                '1000+'
            ])
        ]
    ];

    // Combine all fields
    $allFields = array_merge($companyFields, $contactFields, $businessFields);

    // Add each field
    foreach ($allFields as $field) {
        try {
            $contactManager->addCustomField(
                $field['field_name'],
                $field['display_name'],
                $field['field_type'],
                $field['field_group'],
                $field['is_required'],
                $field['default_value'],
                $field['options']
            );
            echo "Successfully added field: {$field['display_name']}\n";
        } catch (PDOException $e) {
            // If field already exists, skip it
            if ($e->getCode() == '23000') { // Duplicate entry error
                echo "Field already exists: {$field['display_name']}\n";
            } else {
                throw $e;
            }
        }
    }

    echo "\nCustom fields setup completed successfully!\n";

} catch (Exception $e) {
    echo "Error setting up custom fields: " . $e->getMessage() . "\n";
    exit(1);
}
