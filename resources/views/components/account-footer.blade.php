@props([
    'align' => 'center',
    'showTimestamp' => false,
])

@php
    $profile = account_profile();
@endphp

<div {{ $attributes->merge(['class' => 'account-footer-wrapper text-' . $align . ' pt-3 border-t border-dashed border-slate-300 text-xs text-slate-500']) }}>
    @if($profile->footer_text)
        <p class="italic leading-relaxed font-medium text-slate-600">
            {{ $profile->footer_text }}
        </p>
    @endif

    @if($showTimestamp)
        <p class="text-[10px] text-slate-400 mt-1">
            Generated on {{ now()->setTimezone($profile->timezone ?? 'Asia/Karachi')->format('d M Y, h:i A') }}
        </p>
    @endif
</div>
