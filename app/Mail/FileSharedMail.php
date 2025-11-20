<?php

namespace App\Mail;

use App\Models\FileEntryShare;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FileSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public FileEntryShare $share;

    public function __construct(FileEntryShare $share)
    {
        // eager-load for template usage
        $this->share = $share->load(['file.owner']);
    }

    public function build()
    {
        $file   = $this->share->file;
        $owner  = $file->owner;
        $openUrl    = route('shared.open', $this->share->token);
        $previewUrl = route('file.preview', $file->shared_id);

        // normalize owner name
        $ownerName = $owner->name ?? trim(($owner->firstname ?? '').' '.($owner->lastname ?? '')) ?: 'Someone';

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject("{$ownerName} shared “{$file->name}” with you")
            ->markdown('frontend.emails.file_shared', [
                'ownerName'    => $ownerName,
                'fileName'     => $file->name,
                'permission'   => $this->share->permission, // view|comment|edit
                'canDownload'  => (bool) $this->share->can_download,
                'canReshare'   => (bool) $this->share->can_reshare,
                'expiresAt'    => $this->share->expires_at,
                'messageText'  => $this->share->message,
                'openUrl'      => $openUrl,
                'previewUrl'   => $previewUrl,
            ]);
    }
}
