@props([
    'subtitle' => null,
    'date' => null,
    'align' => 'center',
    'showLogo' => true,
    'class' => '',
])

@php
    $profile = account_profile();
    $businessName = !empty(trim((string)($profile?->account_name))) ? $profile->account_name : 'PHARMACY MANAGEMENT';
    $address = $profile?->receipt_address ?? $profile?->formatted_address;
    $phone = $profile?->receipt_phone ?? $profile?->phone;
    $email = $profile?->email;
    $taxNumber = $profile?->registration_number;
    $hasLogo = $showLogo && $profile && $profile->hasLogo();
@endphp

<div class="receipt-header text-{{ $align }} space-y-0.5 {{ $class }}">
    @if($hasLogo)
        <div class="mb-1.5 flex justify-{{ $align === 'center' ? 'center' : ($align === 'right' ? 'end' : 'start') }}">
            <img 
                src="{{ $profile->logo_url }}" 
                alt="{{ $businessName }}" 
                class="max-h-12 max-w-[140px] object-contain"
                style="max-height: 48px; max-width: 140px;"
            >
        </div>
    @endif

    <h2 class="text-base sm:text-lg font-bold tracking-tight text-gray-900 uppercase leading-snug">
        {{ $businessName }}
    </h2>

    @if(!empty(trim((string)$address)))
        <p class="text-gray-500 text-xs leading-tight">{{ $address }}</p>
    @endif

    @if(!empty(trim((string)$phone)))
        <p class="text-gray-500 text-xs leading-tight">Phone: {{ $phone }}</p>
    @endif

    @if(!empty(trim((string)$email)))
        <p class="text-gray-500 text-[11px] leading-tight">Email: {{ $email }}</p>
    @endif

    @if(!empty(trim((string)$taxNumber)))
        <p class="text-gray-500 text-[11px] leading-tight">NTN/Reg: {{ $taxNumber }}</p>
    @endif

    @if(!empty(trim((string)$subtitle)))
        <p class="text-[11px] text-gray-500 font-sans mt-0.5">{{ $subtitle }}</p>
    @endif

    @if(!empty(trim((string)$date)))
        <p class="text-[10px] text-gray-400 font-sans">{{ $date }}</p>
    @endif
</div>
