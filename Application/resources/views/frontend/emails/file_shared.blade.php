@php
    // humanize
    $permLabel = [
        'view' => 'Viewer',
        'comment' => 'Commenter',
        'edit' => 'Editor',
    ][$permission] ?? ucfirst($permission ?? 'view');

    $expiresLine = $expiresAt ? ' • Expires: '.\Illuminate\Support\Carbon::parse($expiresAt)->toDayDateTimeString() : '';
    $abilities = collect([
        $permLabel,
        $canDownload ? 'Can download' : null,
        $canReshare ? 'Can reshare' : null,
    ])->filter()->implode(' • ');
@endphp

@component('mail::message')
{{-- Preheader (shows in inbox preview) --}}
<span style="display:none !important; visibility:hidden; mso-hide:all; opacity:0; color:transparent; height:0; width:0; overflow:hidden;">
{{ $ownerName }} shared “{{ $fileName }}” with you. {{ $abilities }}{{ $expiresLine }}
</span>

# {{ $ownerName }} shared a file with you

**“{{ $fileName }}”**  
{{ $abilities }}{!! $expiresLine ? ' <span style="color:#6b7280;">'.$expiresLine.'</span>' : '' !!}

@isset($messageText)
@component('mail::panel')
**Message from {{ $ownerName }}**  
{{ $messageText }}
@endcomponent
@endisset

@component('mail::button', ['url' => $openUrl])
Open file
@endcomponent

@if(!empty($previewUrl))
[Preview in browser]({{ $previewUrl }})
@endif

---

If you didn’t expect this, you can safely ignore this email.

Thanks,  
{{ config('app.name') }}

{{-- Optional: small footer hint --}}
@slot('subcopy')
If the button above doesn’t work, paste this link into your browser:<br>
{{ $openUrl }}
@endslot
@endcomponent
