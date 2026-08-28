{{--
    CORE-woordmerk (logo aangeleverd 28-08-2026): "C CORE" met oranje kern.
    Bestanden: public/images/core-logo-woordmerk.png (alleen woordmerk) en
    core-logo-volledig.png (met "POWERED BY BOELS INDUSTRIAL").
    Gebruik: @include('partials.core-logo', ['size' => 44])
    Optioneel: ['light' => true] -> wit kader, voor op de oranje navbalk.
--}}
@php
    $size = $size ?? 44;
    $light = $light ?? false;
@endphp
@if ($light)
    <span style="display:inline-flex; align-items:center; background:#fff; padding:7px 12px; border-radius:9px;">
        <img src="{{ asset('images/core-logo-woordmerk.png') }}" alt="CORE"
             style="height:{{ (int) round($size * 0.76) }}px; display:block;">
    </span>
@else
    <img src="{{ asset('images/core-logo-woordmerk.png') }}" alt="CORE"
         style="height:{{ (int) round($size * 0.85) }}px; display:inline-block; vertical-align:middle;">
@endif
