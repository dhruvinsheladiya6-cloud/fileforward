<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    use HasFactory;

    public function scopeActive($query)
    {
        $query->where('status', 1);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'api_key',
        'logo',
        'name',
        'symbol',
        'version',
        'status',
        'action_text',
        'action_link',
    ];
}
