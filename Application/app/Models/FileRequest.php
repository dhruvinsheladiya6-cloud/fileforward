<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'folder_id',
        'token',
        'title',
        'description',
        'password',
        'max_file_size',
        'expires_at',
        'is_active',
        'uploads_count',
        'views_count',
        'storage_limit',
    ];

    protected $dates = ['expires_at'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function folder()
    {
        return $this->belongsTo(FileEntry::class, 'folder_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isActive(): bool
    {
        return $this->is_active && (!$this->expires_at || $this->expires_at->isFuture());
    }

    /** Max size allowed for a single file in bytes. Default = 2GB */
    public function maxFileSizeBytes(): int
    {
        if ($this->max_file_size) {
            return (int) $this->max_file_size;
        }
        // Default 2GB per file
        return 2 * 1024 * 1024 * 1024; // 2GB
    }

    public function getFolderPathAttribute(): string
    {
        if (!$this->folder) {
            return __('Root');
        }

        $segments = [];
        $current = $this->folder;
        while ($current) {
            array_unshift($segments, $current->name);
            $current = $current->parent;
        }

        return implode(' / ', $segments);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('file-request.show', $this->token);
    }
}
