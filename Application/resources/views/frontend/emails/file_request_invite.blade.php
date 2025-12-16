@component('mail::message')
{{-- Preheader (shows in inbox preview) --}}
<span style="display:none !important; visibility:hidden; mso-hide:all; opacity:0; color:transparent; height:0; width:0; overflow:hidden;">
{{ $ownerName }} requested files from you: {{ $title }}
</span>

# {{ $ownerName }} requested files from you

**"{{ $title }}"**

@isset($customMessage)
@component('mail::panel')
**Message from {{ $ownerName }}**  
{{ $customMessage }}
@endcomponent
@endisset

@component('mail::button', ['url' => $uploadUrl])
Upload files
@endcomponent

@if($expiresAt)
This request expires on {{ \Illuminate\Support\Carbon::parse($expiresAt)->toDayDateTimeString() }}.
@endif

---

If you didn't expect this request, you can safely ignore this email.

Thanks,  
{{ config('app.name') }}

{{-- Optional: small footer hint --}}
@slot('subcopy')
If the button above doesn't work, paste this link into your browser:<br>
{{ $uploadUrl }}
@endslot
@endcomponent
