<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FileEntryShare;

class FileEntry extends Model
{
    use HasFactory;

    /**
     * Get the current user entries
     */
    public function scopeCurrentUser($query)
    {
        $query->where('user_id', userAuthInfo()->id);
    }

    /**
     * Get only none expired
     */
    public function scopeNotExpired($query)
    {
        $query->where(function ($query) {
            $query->where('expiry_at', '>', Carbon::now())->orWhereNull('expiry_at');
        });
    }

    /**
     * Get only non-trashed files (ADD THIS)
     */
    public function scopeNotTrashed($query)
    {
        $query->whereNull('deleted_at');
    }

    /**
     * Get only trashed files (ADD THIS)
     */
    public function scopeTrashed($query)
    {
        $query->whereNotNull('deleted_at');
    }

    /**
     * Get users entries
     */
    public function scopeUserEntry($query)
    {
        $query->where('user_id', '!=', null);
    }

    /**
     * Get guests entries
     */
    public function scopeGuestEntry($query)
    {
        $query->where('user_id', null);
    }

    /**
     * File can be previewed
     */
    public function scopeHasPreview($query)
    {
        $query->whereIn('type', ['image', 'pdf']);
    }

    /**
     * File without parent
     */
    public function scopeHasNoParent($query)
    {
        $query->where('parent_id', null);
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'ip',
        'shared_id',
        'user_id',
        'parent_id',
        'storage_provider_id',
        'name',
        'filename',
        'mime',
        'size',
        'extension',
        'type',
        'path',
        'link',
        'access_status',
        'password',
        'downloads',
        'views',
        'admin_has_viewed',
        'expiry_at',
        'deleted_at',
        'purge_at',
        'uploaded_by',
        'uploaded_via_share_id',
    ];

    /**
     * The attributes that should be mutated to dates.
     */
    protected $dates = ['expiry_at', 'deleted_at', 'purge_at'];

    /** Items that have been scheduled for permanent purge */
    public function scopePendingPurge($query)
    {
        return $query->whereNotNull('purge_at');
    }

    /** Items whose purge date is due (now or in the past) */
    public function scopeDueForPurge($query)
    {
        return $query->whereNotNull('purge_at')->where('purge_at', '<=', Carbon::now());
    }

    /**
     * Recursively mark this entry (and descendants if folder) for purge at a given datetime.
     * Does NOT touch storage or delete DB rows. It only sets purge_at (and leaves deleted_at as-is).
     */
    public function markForPurgeRecursive(Carbon $when): void
    {
        // mark self
        $this->update(['purge_at' => $when]);

        if ($this->type === 'folder') {
            // mark descendants
            $children = FileEntry::where('parent_id', $this->id)->get();
            foreach ($children as $child) {
                $child->markForPurgeRecursive($when);
            }
        }
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /** trashed AND scheduled for purge (the queue) */
    public function scopeTrashedAndScheduled($q)
    {
        return $q->whereNotNull('deleted_at')->whereNotNull('purge_at');
    }

    /** Build a nested tree (folders with their files) from a flat collection */
    public static function buildTree(\Illuminate\Support\Collection $entries)
    {
        $byId = $entries->keyBy('id');
        $children = [];

        foreach ($entries as $e) {
            $pid = $e->parent_id ?: 0;
            if (!isset($children[$pid])) $children[$pid] = [];
            $children[$pid][] = $e->id;
        }

        $makeNode = function ($id) use (&$makeNode, $children, $byId) {
            if (!$byId->has($id)) return null;
            $node = $byId[$id];
            $node->children_nodes = collect();
            if (isset($children[$id])) {
                foreach ($children[$id] as $cid) {
                    $childNode = $makeNode($cid);
                    if ($childNode) $node->children_nodes->push($childNode);
                }
            }
            return $node;
        };

        // roots = entries whose parent is null or not in the set
        $rootIds = [];
        foreach ($entries as $e) {
            if (!$e->parent_id || !$byId->has($e->parent_id)) {
                $rootIds[] = $e->id;
            }
        }

        $tree = collect();
        foreach ($rootIds as $rid) {
            $n = $makeNode($rid);
            if ($n) $tree->push($n);
        }
        return $tree;
    }


    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function storageProvider()
    {
        return $this->belongsTo(StorageProvider::class, 'storage_provider_id', 'id');
    }

    public function reports()
    {
        return $this->hasMany(FileReport::class, 'file_entry_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(FileEntry::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FileEntry::class, 'parent_id');
    }

    public function getBreadcrumbPathAttribute()
    {
        $path = [];
        $current = $this;
        
        while ($current && $current->parent_id) {
            $current = $current->parent;
            if ($current) {
                array_unshift($path, $current);
            }
        }
        
        return $path;
    }



    // app/Models/FileEntry.php
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function viaShare() { return $this->belongsTo(FileEntryShare::class, 'uploaded_via_share_id'); }

    public function getUploaderDisplayNameAttribute(): ?string
    {
        $u = $this->uploader;
        if (!$u) return null;

        $name = trim(($u->firstname ?? '').' '.($u->lastname ?? ''));
        if ($name === '') {
            $name = $u->username ?? $u->email ?? null;
        }
        return $name ?: null;
    }

    // share with me
    public function owner()  { return $this->belongsTo(User::class, 'user_id'); }

    public function shares()
    {
        return $this->hasMany(FileEntryShare::class, 'file_entry_id')
                    ->whereNull('revoked_at');
    }

    public function shareFor($userId)
{
    return $this->shares()->where('recipient_user_id', $userId)->first();
}

    /** Centralized access check */
    public function isAccessibleBy(User $user, ?string $email = null): bool
    {
        if (!$this->access_status) { return false; }           // your existing public/private gate
        if ($user && $this->user_id === $user->id) { return true; }

        $query = $this->shares()
            ->whereNull('revoked_at')
            ->where(function ($q) use ($user, $email) {
                if ($user) { $q->orWhere('recipient_user_id', $user->id); }
                if ($email) { $q->orWhere('recipient_email', $email); }
            });

        $share = $query->first();
        if (!$share) { return false; }
        if ($share->expires_at && now()->greaterThan($share->expires_at)) { return false; }

        return true;
    }

    public static function descendantIdsFor(int $rootId): array
    {
        $ids = [];
        $frontier = [$rootId];
        do {
            $children = self::whereIn('parent_id', $frontier)->pluck('id')->all();
            $frontier = $children;
            $ids = array_merge($ids, $children);
        } while (!empty($frontier));
        return array_values(array_unique($ids));
    }

    /** Check if this entry is a (strict) descendant of a given ancestor */
    public function isDescendantOf(FileEntry $ancestor): bool
    {
        $cur = $this;
        while ($cur && $cur->parent_id) {
            if ((int)$cur->parent_id === (int)$ancestor->id) return true;
            $cur = $cur->parent;
        }
        return false;
    }


    public function uploadRequests()
    {
        return $this->hasMany(FileRequest::class, 'folder_id');
    }

}
