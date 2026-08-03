{{--
    CORE woordmerk v2 — de "O" is een kern met satellieten óp de ring.
    Typografisch strak: de O heeft exact kapitaalhoogte en staat op de
    basislijn, krappe spatiëring, zwaar geometrisch gewicht.
    Gebruik: @include('partials.core-logo', ['size' => 44])
    Optioneel: ['light' => true] voor donkere/oranje achtergrond.
--}}
@php
    $size = $size ?? 44;
    $light = $light ?? false;
    $textColor = $light ? '#ffffff' : '#111111';
    $accent = config('boels.brand.color', '#FF6600');
@endphp
<span class="core-wordmark" style="display:inline-block; line-height:1; user-select:none; white-space:nowrap;
        font-family:'Inter','Segoe UI','Helvetica Neue',Arial,sans-serif;
        font-weight:900; font-size:{{ $size }}px; letter-spacing:0.045em;
        color:{{ $textColor }};">C<svg viewBox="0 0 100 100" role="img" aria-label="O"
        style="height:0.72em; width:0.72em; vertical-align:baseline; margin:0 0.055em 0 0.035em;">
        {{-- ring --}}
        <circle cx="50" cy="50" r="38" fill="none" stroke="{{ $accent }}" stroke-width="16"/>
        {{-- kern --}}
        <circle cx="50" cy="50" r="15" fill="{{ $accent }}"/>
        {{-- satellieten óp de ring (diagonaal — de apps om de kern) --}}
        <circle cx="76.9" cy="23.1" r="7.5" fill="{{ $accent }}" stroke="{{ $light ? 'rgba(255,255,255,.9)' : '#fff' }}" stroke-width="3"/>
        <circle cx="76.9" cy="76.9" r="7.5" fill="{{ $accent }}" stroke="{{ $light ? 'rgba(255,255,255,.9)' : '#fff' }}" stroke-width="3"/>
        <circle cx="23.1" cy="76.9" r="7.5" fill="{{ $accent }}" stroke="{{ $light ? 'rgba(255,255,255,.9)' : '#fff' }}" stroke-width="3"/>
        <circle cx="23.1" cy="23.1" r="7.5" fill="{{ $accent }}" stroke="{{ $light ? 'rgba(255,255,255,.9)' : '#fff' }}" stroke-width="3"/>
    </svg>RE</span>
