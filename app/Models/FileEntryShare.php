<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\FileEntry;

class FileEntryShare extends Model
{
    protected $table = 'file_entry_shares';

    protected $fillable = [
        'file_entry_id', 'owner_id', 'recipient_user_id', 'recipient_email',
        'permission', 'can_download', 'can_reshare', 'token',
        'expires_at', 'accepted_at', 'revoked_at',
    ];

    protected $casts = [
        'can_download' => 'boolean',
        'can_reshare'  => 'boolean',
        'expires_at'   => 'datetime',
        'accepted_at'  => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    public function file()   { return $this->belongsTo(FileEntry::class, 'file_entry_id'); }
    public function owner()  { return $this->belongsTo(User::class, 'owner_id'); }
    public function recipient(){ return $this->belongsTo(User::class, 'recipient_user_id'); }


     // Normalized permission
    public function permissionLevel(): string
    {
        return strtolower($this->permission ?? 'view'); // 'view' | 'comment' | 'edit'
    }

    public function canView(): bool      { return true; } // any valid share implies view
    public function canComment(): bool   { return in_array($this->permissionLevel(), ['comment','edit'], true); }
    public function canEdit(): bool      { return $this->permissionLevel() === 'edit'; }

    public function canDownload(): bool  { return (bool)$this->can_download; }
    public function canReshare(): bool   { return (bool)$this->can_reshare; }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at) && !$this->isExpired();
    }

    public function isOwner(User $user): bool
    {
        return (int)$this->owner_id === (int)$user->id;
    }
}
