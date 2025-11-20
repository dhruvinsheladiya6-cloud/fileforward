<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileReport extends Model
{
    use HasFactory;


    /**
     * Only show reports whose file is still "active" for the admin list:
     *  - NOT expired
     *  - NOT trashed (deleted_at is null)
     *  - NOT scheduled for purge (purge_at is null)
     */
    public function scopeFileEntryActive($query)
    {
        return $query->whereHas('fileEntry', function ($q) {
            $q->notExpired()
              ->whereNull('purge_at');
        });
    }

    /** Waiting for review */
    public function scopeWaitingReview($query)
    {
        return $query->where('admin_has_viewed', 0);
    }

    /** Reviewed */
    public function scopeReviewed($query)
    {
        return $query->where('admin_has_viewed', 1);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'file_entry_id',
        'ip',
        'name',
        'email',
        'reason',
        'details',
        'admin_has_viewed',
    ];

    /** Casts for convenience */
    protected $casts = [
        'admin_has_viewed' => 'boolean',
    ];

    public function fileEntry()
    {
        return $this->belongsTo(FileEntry::class, 'file_entry_id', 'id');
    }
}
