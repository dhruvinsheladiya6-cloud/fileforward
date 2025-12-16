<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadLink extends Model
{
    use HasFactory;

    /**
     * Get only expired
     *
     * @return $id
     */
    public function scopeHasExpired($query)
    {
        $query->where(function ($query) {
            $query->where('expiry_at', '<', Carbon::now());
        });
    }

    /**
     * Get only none expired
     *
     * @return $id
     */
    public function scopeNotExpired($query)
    {
        $query->where(function ($query) {
            $query->where('expiry_at', '>', Carbon::now());
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'file_entry_id',
        'expiry_at',
    ];

    public function fileEntry()
    {
        return $this->belongsTo(FileEntry::class, 'file_entry_id', 'id');
    }
}
