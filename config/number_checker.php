<?php

return [
    'sources' => [
        'main_db' => [
            'label' => 'Main DB',
            'connection' => env('NUMBER_CHECK_MAIN_CONNECTION', 'mysql'),
            'table' => env('NUMBER_CHECK_MAIN_TABLE', 'customers'),
            'column' => env('NUMBER_CHECK_MAIN_COLUMN', 'phone_number'),
        ],
        'legacy_crm' => [
            'label' => 'Legacy CRM',
            'connection' => env('NUMBER_CHECK_LEGACY_CONNECTION', 'legacy'),
            'table' => env('NUMBER_CHECK_LEGACY_TABLE', 'contacts'),
            'column' => env('NUMBER_CHECK_LEGACY_COLUMN', 'phone'),
        ],
        'data_warehouse' => [
            'label' => 'Data Warehouse',
            'connection' => env('NUMBER_CHECK_DWH_CONNECTION', 'warehouse'),
            'table' => env('NUMBER_CHECK_DWH_TABLE', 'phone_dimension'),
            'column' => env('NUMBER_CHECK_DWH_COLUMN', 'msisdn'),
        ],
    ],
];
