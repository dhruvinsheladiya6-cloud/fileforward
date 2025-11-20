<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Author Information
    |--------------------------------------------------------------------------
    |
    | This section contains information about the author of the configuration.
    |
     */

    'author' => [
        'name' => 'Vironeer', // Author's name
        'email' => 'support@vironeer.com', // Author's email
        'website' => 'https://vironeer.com', // Author's website
        'profile' => 'https://codecanyon.net/user/vironeer', // Author's profile link
    ],

    /*
    |--------------------------------------------------------------------------
    | Item Information
    |--------------------------------------------------------------------------
    |
    | This section contains information about the item.
    |
     */

    'item' => [
        'alias' => 'filebob', // Item alias
        'version' => '1.8', // Item version
    ],

    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    |
    | This section contains information about the API.
    |
     */

    'api' => [
        'license' => 'https://trustlicence.com/api/v1/licence', // API license URL
    ],

    /*
    |--------------------------------------------------------------------------
    | System Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration options for the system.
    |
     */

    'system' => [
        'demo_mode' => env('DEMO_MODE', false), // System demo mode setting
        'license_type' => env('LICENCE_TYPE', 1), // System license type
    ],

    /*
    |--------------------------------------------------------------------------
    | Installation Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration options for the installation process.
    |
     */

    'install' => [
        'requirements' => env('VIRONEER_REQUIREMENTS', false), // Installation requirements setting
        'file_permissions' => env('VIRONEER_FILEPERMISSIONS', false), // File permissions setting
        'license' => env('VIRONEER_LICENCE', false), // Installation license setting
        'database_info' => env('VIRONEER_INFODATABASE', false), // Database information setting
        'database_import' => env('VIRONEER_INFODBIMPORT', false), // Database import setting
        'complete' => env('VIRONEER_INFOBUILDING', false), // Installation completion setting
    ],

];
