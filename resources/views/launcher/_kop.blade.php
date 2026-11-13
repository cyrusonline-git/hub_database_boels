{{-- Welkomstkop + snelzoeken; in de dashboardindeling staat dit in de middenkolom ($compact) --}}
<div class="text-center {{ !empty($compact) ? 'mt-0 mb-3' : 'mt-3 mb-4' }}">
    <h3 class="mb-1">Welkom, {{ explode(' ', $u->name)[0] }}</h3>
    <p class="text-muted mb-0">{{ \Illuminate\Support\Str::ucfirst(now()->locale('nl')->translatedFormat('l j F Y')) }}
        @if($u->last_login_at) · vorige login {{ $u->last_login_at->locale('nl')->diffForHumans() }} @endif
    </p>
</div>

{{-- Snelzoeken: klanten, collega's, artikelen --}}
<div class="dash-search mb-2">
    <i class="bi bi-search"></i>
    <input type="search" id="dashSearch" placeholder="Zoek een klant, collega of artikel…" autocomplete="off">
    <div id="dashResults"></div>
</div>
<div class="text-center {{ !empty($compact) ? 'mb-4' : 'mb-5' }}">
    <a href="{{ route('articles.index') }}" class="btn btn-boels btn-sm px-3">
        <i class="bi bi-box-seam"></i> Artikelen zoeken op naam of nummer
    </a>
</div>
