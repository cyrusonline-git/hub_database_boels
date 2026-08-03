{{--
    CORE woordmerk — de "O" is een kern met satellieten: het hart waar
    alle apps omheen draaien. Gebruik: @include('partials.core-logo', ['size' => 44])
    Optioneel: ['light' => true] voor gebruik op donkere/oranje achtergrond.
--}}
@php
    $size = $size ?? 44;
    $light = $light ?? false;
    $textColor = $light ? '#ffffff' : '#1a1a1a';
    $accent = config('boels.brand.color', '#FF6600');
@endphp
<span class="core-wordmark" style="display:inline-flex; align-items:center; gap:{{ round($size * 0.10) }}px;
        font-family:'Avenir Next','Futura','Segoe UI',system-ui,-apple-system,sans-serif;
        font-weight:800; font-size:{{ $size }}px; letter-spacing:{{ round($size * 0.16) }}px;
        color:{{ $textColor }}; line-height:1; user-select:none;">C<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 100 100" role="img" aria-label="O"
        style="margin-right:{{ round($size * 0.16) }}px; flex:0 0 auto;">
        {{-- de ring --}}
        <circle cx="50" cy="50" r="38" fill="none" stroke="{{ $accent }}" stroke-width="13"/>
        {{-- de kern --}}
        <circle cx="50" cy="50" r="13" fill="{{ $accent }}"/>
        {{-- satellieten: de apps om de kern heen --}}
        <circle cx="50" cy="6"  r="6" fill="{{ $accent }}"/>
        <circle cx="94" cy="50" r="6" fill="{{ $accent }}"/>
        <circle cx="50" cy="94" r="6" fill="{{ $accent }}"/>
        <circle cx="6"  cy="50" r="6" fill="{{ $accent }}"/>
    </svg>RE</span>
