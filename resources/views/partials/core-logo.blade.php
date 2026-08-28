{{--
    CORE-woordmerk (logo aangeleverd 28-08-2026): donker vlak, witte CORE,
    oranje ring als O. Bestand: public/images/core-logo-woordmerk.png
    (ronde hoeken zitten in de PNG zelf).
    Gebruik: @include('partials.core-logo', ['size' => 44])
    Optioneel: ['light' => true] -> vaste navbalk-hoogte (past bij het Boels-kadertje).
--}}
@php
    $size = $size ?? 44;
    $light = $light ?? false;
    $hoogte = $light ? 40 : (int) round($size * 0.9);
@endphp
<img src="{{ asset('images/core-logo-woordmerk.png') }}" alt="CORE"
     style="height:{{ $hoogte }}px; display:inline-block; vertical-align:middle;">
