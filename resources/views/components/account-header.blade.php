@props([
    'align' => 'center',
    'size' => 'normal',
    'showLogo' => true,
    'documentTitle' => null,
])

@php
    $profile = account_profile();
@endphp

<div {{ $attributes->merge(['class' => 'account-header-wrapper text-' . $align . ' pb-3 border-b border-slate-300']) }}>
    @if($showLogo && $profile->hasLogo())
        <div class="account-logo-container mb-2 {{ $align === 'center' ? 'mx-auto' : '' }}">
            <img 
                src="{{ $profile->logo_url }}" 
                alt="{{ $profile->account_name }}" 
                class="{{ $size === 'compact' ? 'max-h-10 max-w-[120px]' : 'max-h-16 max-w-[180px]' }} object-contain {{ $align === 'center' ? 'mx-auto' : '' }}"
            />
        </div>
    @endif

    <div>
        <h2 class="{{ $size === 'compact' ? 'text-sm' : 'text-base sm:text-lg' }} font-black text-slate-900 uppercase tracking-wide leading-tight">
            {{ $profile->account_name }}
        </h2>

        @if($profile->formatted_address)
            <p class="text-xs text-slate-600 mt-0.5 leading-snug">
                {{ $profile->formatted_address }}
            </p>
        @endif

        <div class="flex flex-wrap items-center justify-{{ $align === 'center' ? 'center' : 'start' }} gap-x-3 text-[11px] text-slate-500 mt-1">
            @if($profile->phone)
                <span><i class="fa-solid fa-phone mr-1 text-[10px]"></i>{{ $profile->phone }}</span>
            @endif
            @if($profile->email)
                <span><i class="fa-solid fa-envelope mr-1 text-[10px]"></i>{{ $profile->email }}</span>
            @endif
            @if($profile->registration_number)
                <span><i class="fa-solid fa-certificate mr-1 text-[10px]"></i>Reg: {{ $profile->registration_number }}</span>
            @endif
        </div>

        @if($documentTitle)
            <div class="mt-2.5 pt-2 border-t border-dashed border-slate-200">
                <span class="inline-block px-3 py-1 bg-slate-100 text-slate-800 font-extrabold uppercase tracking-wider {{ $size === 'compact' ? 'text-xs' : 'text-sm' }} rounded">
                    {{ $documentTitle }}
                </span>
            </div>
        @endif
    </div>
</div>
