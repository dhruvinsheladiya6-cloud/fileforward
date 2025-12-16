<?php

namespace App\Mail;

use App\Models\FileRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FileRequestInvite extends Mailable
{
    use Queueable, SerializesModels;

    public FileRequest $fileRequest;
    public ?string $customMessage;

    public function __construct(FileRequest $fileRequest, ?string $customMessage = null)
    {
        $this->fileRequest = $fileRequest->load('owner');
        $this->customMessage = $customMessage;
    }

    public function build()
    {
        $owner = $this->fileRequest->owner;
        $ownerName = $owner->name ?? trim(($owner->firstname ?? '') . ' ' . ($owner->lastname ?? '')) ?: 'Someone';

        $uploadUrl = $this->fileRequest->public_url;

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject("{$ownerName} requested files from you")
            ->markdown('frontend.emails.file_request_invite', [
                'ownerName' => $ownerName,
                'title' => $this->fileRequest->title,
                'customMessage' => $this->customMessage,
                'uploadUrl' => $uploadUrl,
                'expiresAt' => $this->fileRequest->expires_at,
            ]);
    }
}
