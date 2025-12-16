<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadSettings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'storage_space',
        'file_size',
        'files_duration',
        'password_protection',
        'upload_at_once',
        'advertisements',
        'status',
        'upload_mode', // 'for Custom or regular uploads'
    ];


    
     /**
     * Get the current upload mode
     */
    public static function getUploadMode()
    {
        $setting = self::first();
        return $setting && $setting->upload_mode ? $setting->upload_mode : 'regular';
    }

    /**
     * Set the upload mode
     */
    public static function setUploadMode($mode)
    {
        $setting = self::first();
        if (!$setting) {
            $setting = new self([
                'storage_space' => 0,
                'file_size' => 0,
                'files_duration' => 0,
                'password_protection' => 0,
                'upload_at_once' => 0,
                'advertisements' => 0,
                'status' => 1,
                'upload_mode' => $mode
            ]);
        } else {
            $setting->upload_mode = $mode;
        }
        $setting->save();
        return $setting;
    }

    /**
     * Toggle between custom and regular mode
     */
    public static function toggleUploadMode()
    {
        $currentMode = self::getUploadMode();
        $newMode = $currentMode === 'custom' ? 'regular' : 'custom';
        return self::setUploadMode($newMode);
    }
}
