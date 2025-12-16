<?php

namespace App\Http\Methods;

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

class FileDetailsDetector
{
    public static function lookupExtension($mimeType)
    {
        $arr = array_flip(GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS);
        return $arr[$mimeType] ?? null;
    }

    public static function lookupMimeType(string $extension): ?string
    {
        return GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS[$extension] ?? null;
    }
}
