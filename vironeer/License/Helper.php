<?php

function licenseType($type = null)
{
    $licenseType = config('vironeer.system.license_type');
    if ($type) {
        return ($type == $licenseType) ? true : false;
    } else {
        return $licenseType;
    }
}

function extensionAvailability($name)
{
    if (!extension_loaded($name)) {
        $response = false;
    } else {
        $response = true;
    }
    return $response;
}

function phpExtensions()
{
    $extensions = [
        'BCMath',
        'Ctype',
        'Fileinfo',
        'JSON',
        'Mbstring',
        'OpenSSL',
        'PDO',
        'pdo_mysql',
        'Tokenizer',
        'XML',
        'cURL',
        'GD',
        'zip',
    ];
    return $extensions;
}

function filePermissionValidation($name)
{
    $perm = substr(sprintf('%o', fileperms($name)), -4);
    if ($perm >= '0775') {
        $response = true;
    } else {
        $response = false;
    }
    return $response;
}

function filePermissions()
{
    $filePermissions = [
        base_path('addons/'),
        base_path('addons/src/'),
        base_path('bootstrap/'),
        base_path('bootstrap/cache/'),
        base_path('storage/'),
        base_path('storage/app/'),
        base_path('storage/framework/'),
        base_path('storage/logs/'),
        'images/',
        'images/avatars/',
        'uploads/',
    ];
    return $filePermissions;
}
