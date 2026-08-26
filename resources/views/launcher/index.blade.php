@extends('layouts.app')
@section('title','Dashboard')

@push('styles')
<style>
    .dash-search { position: relative; max-width: 640px; margin: 0 auto; }
    .dash-search input {
        width: 100%; padding: 13px 20px 13px 46px; font-size: 16px;
        border: 2px solid #e4e6ea; border-radius: 14px; outline: none;
        background: #fff; transition: border-color .15s ease;
    }
    .dash-search input:focus { border-color: var(--boels-orange); }
    .dash-search .bi-search {
        position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
        color: #999; font-size: 18px;
    }
    #dashResults {
        position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 1050;
        background: #fff; border-radius: 14px; box-shadow: 0 12px 40px rgba(0,0,0,.18);
        overflow: hidden; display: none; max-height: 60vh; overflow-y: auto;
        text-align: left;
    }
    #dashResults .grp {
        padding: 8px 16px 4px; font-size: 11px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; color: #999; border-top: 1px solid #f0f0f0;
    }
    #dashResults .grp:first-child { border-top: 0; }
    #dashResults .hit { display: block; padding: 8px 16px; text-decoration: none; color: #212529; }
    #dashResults a.hit:hover { background: #fff4ec; }
    #dashResults .hit .lbl { font-weight: 600; font-size: 14px; }
    #dashResults .hit .sub { font-size: 12px; color: #888; }
    #dashResults .empty { padding: 14px 16px; color: #888; font-size: 14px; }

    .stat-card { border: 0; border-radius: 14px; }
    .stat-card .num { font-size: 26px; font-weight: 800; line-height: 1.1; }
    .stat-card .lbl { font-size: 12px; color: #888; }
    .stat-icon {
        width: 44px; height: 44px; border-radius: 12px; flex: 0 0 auto;
        display: flex; align-items: center; justify-content: center; font-size: 20px;
        background: #fff4ec; color: var(--boels-orange);
    }
    .app-badge {
        position: absolute; top: 10px; right: 10px; z-index: 2;
        min-width: 24px; height: 24px; border-radius: 12px; padding: 0 7px;
        background: #dc3545; color: #fff; font-size: 13px; font-weight: 700;
        display: none; align-items: center; justify-content: center;
        box-shadow: 0 2px 6px rgba(0,0,0,.25);
    }
    .link-cat {
        font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
        color: #98a1ab; margin: 10px 0 4px;
    }
    .link-cat:first-child { margin-top: 0; }
    .side-link {
        display: flex; align-items: center; gap: 10px; padding: 8px 10px;
        border-radius: 10px; text-decoration: none; color: #212529; font-size: 14px; font-weight: 600;
    }
    .side-link:hover { background: #fff4ec; color: #212529; }
    .side-link > i:first-child { color: var(--boels-orange); font-size: 17px; flex: 0 0 auto; }
    .quick-link {
        display: flex; align-items: center; gap: 10px; padding: 10px 14px;
        border-radius: 12px; background: #fff; text-decoration: none; color: #212529;
        border: 1px solid #eceef1; transition: border-color .15s ease, transform .15s ease;
        font-size: 14px; font-weight: 600;
    }
    .quick-link:hover { border-color: var(--boels-orange); transform: translateY(-2px); color: #212529; }
    .quick-link i { color: var(--boels-orange); font-size: 17px; }
    .section-title {
        font-size: 13px; font-weight: 700; letter-spacing: .6px;
        text-transform: uppercase; color: #98a1ab; margin-bottom: 12px;
    }
    .activity-row { display: flex; gap: 10px; padding: 9px 0; border-bottom: 1px solid #f2f3f5; font-size: 13px; }
    .activity-row:last-child { border-bottom: 0; }
    .activity-row .when { color: #aab; flex: 0 0 auto; white-space: nowrap; }
</style>
@endpush

@section('content')
@php($u = auth()->user())

<div class="text-center mt-3 mb-4">
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
<div class="text-center mb-5">
    <a href="{{ route('articles.index') }}" class="btn btn-boels btn-sm px-3">
        <i class="bi bi-box-seam"></i> Artikelen zoeken op naam of nummer
    </a>
</div>

{{-- Drie kolommen: links de tools (handige links), in het midden de
     applicatie-tegels (3 per rij), rechts de snelkoppelingen.
     Op mobiel komen de tegels bovenaan (order-classes). --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 order-2 order-lg-1">
        <div class="section-title">Handige links</div>
        <div class="card stat-card shadow-sm p-3 mb-3">
            @forelse($quickLinks as $category => $links)
                <div class="link-cat">{{ $category }}</div>
                @foreach($links as $link)
                    <a href="{{ $link->url }}" target="_blank" class="side-link" @if($link->description) title="{{ $link->description }}" @endif>
                        <i class="{{ $link->icon ?: 'bi-link-45deg' }}"></i>
                        <span class="flex-grow-1">
                            {{ $link->title }}
                            @if($link->description)<span class="d-block small text-muted">{{ $link->description }}</span>@endif
                        </span>
                        <i class="bi bi-box-arrow-up-right small text-muted"></i>
                    </a>
                @endforeach
            @empty
                <div class="text-muted small">
                    Nog geen links toegevoegd.
                    @if($isAdmin)
                        <a href="{{ url('/admin/handige-links') }}">Voeg rekentools, documenten of sites toe</a> —
                        bijvoorbeeld de generator-rekentool.
                    @endif
                </div>
            @endforelse
            @if($isAdmin && $quickLinks->isNotEmpty())
                <a href="{{ url('/admin/handige-links') }}" class="small mt-2 d-inline-block"><i class="bi bi-pencil"></i> links beheren</a>
            @endif
        </div>
    </div>

    <div class="col-lg-6 order-1 order-lg-2">
        <div class="section-title text-center">Jouw applicaties</div>
        @if($apps->isEmpty())
            <div class="alert alert-warning">
                Je hebt nog geen toegang tot een applicatie. Neem contact op met een beheerder.
            </div>
        @else
        <div class="row g-4">
            @foreach($apps as $app)
                <div class="col-6 col-md-4">
                    <a href="{{ $app->url ?: '#' }}" target="_blank" class="text-decoration-none">
                        <div class="card text-center p-4 app-tile h-100 position-relative">
                            @php($b = $badges[$app->id] ?? 0)
                            <span class="app-badge" data-badge-app="{{ $app->id }}"
                                  @if($b > 0) style="display:flex;" @endif>{{ $b > 99 ? '99+' : $b }}</span>
                            <div class="icon-circle" style="background: {{ $app->color }}; color: #fff;">
                                <i class="{{ $app->icon ?: 'bi-app' }}"></i>
                            </div>
                            <h6 class="mb-1 text-dark">{{ $app->name }}</h6>
                            <small class="text-muted">{{ $app->description }}</small>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="col-lg-3 order-3">
        <div class="section-title">Snelkoppelingen</div>
        <div class="d-flex flex-column gap-2 mb-3">
            <a href="#" class="quick-link" onclick="document.getElementById('chatFab')?.click(); return false;">
                <i class="bi bi-chat-dots"></i> Chat met een collega
            </a>
            <a href="{{ route('articles.index') }}" class="quick-link"><i class="bi bi-box-seam"></i> Artikelen zoeken</a>
            <a href="{{ route('password.change') }}" class="quick-link"><i class="bi bi-key"></i> Wachtwoord wijzigen</a>
            @if($isAdmin)
                <a href="{{ url('/admin/klanten') }}" class="quick-link"><i class="bi bi-building"></i> Klanten</a>
                <a href="{{ url('/admin/materieel') }}" class="quick-link"><i class="bi bi-tools"></i> Materieel</a>
                <a href="{{ url('/admin/employees') }}" class="quick-link"><i class="bi bi-people"></i> Medewerkers</a>
                <a href="{{ url('/admin/users') }}" class="quick-link"><i class="bi bi-person-gear"></i> Gebruikers</a>
                <a href="{{ url('/admin/imports') }}" class="quick-link"><i class="bi bi-upload"></i> Import</a>
                <a href="{{ url('/admin/audit-log') }}" class="quick-link"><i class="bi bi-clock-history"></i> Audit log</a>
            @endif
        </div>
    </div>
</div>

@if($isAdmin && $admin)
{{-- Beheer-overzicht --}}
<div class="section-title text-center">Platform-overzicht</div>
<div class="row g-3 justify-content-center mb-4">
    @foreach([
        ['bi-people', number_format($admin['stats']['employees'], 0, ',', '.'), 'Actieve medewerkers', url('/admin/employees')],
        ['bi-building', number_format($admin['stats']['customers'], 0, ',', '.'), 'Klanten', url('/admin/klanten')],
        ['bi-tools', number_format($admin['stats']['machines'], 0, ',', '.'), 'Machines', url('/admin/materieel')],
        ['bi-person-check', number_format($admin['stats']['users_active'], 0, ',', '.'), 'Actieve accounts', url('/admin/users')],
        ['bi-hourglass-split', number_format($admin['stats']['users_pending'], 0, ',', '.'), 'Wacht op activatie', url('/admin/users?status=pending_activation')],
    ] as [$icon, $num, $lbl, $href])
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ $href }}" class="text-decoration-none">
                <div class="card stat-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon"><i class="{{ $icon }}"></i></div>
                        <div>
                            <div class="num text-dark">{{ $num }}</div>
                            <div class="lbl">{{ $lbl }}</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 justify-content-center mb-4">
    <div class="col-lg-6">
        <div class="card stat-card shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong><i class="bi bi-clock-history text-boels"></i> Laatste activiteit</strong>
                <a href="{{ url('/admin/audit-log') }}" class="small">alles →</a>
            </div>
            @forelse($admin['auditLogs'] as $log)
                <div class="activity-row">
                    <span class="when">{{ $log->created_at->locale('nl')->diffForHumans(short: true) }}</span>
                    <span class="flex-grow-1">
                        <strong>{{ $log->user->name ?? 'Systeem' }}</strong>
                        {{ ['created' => 'maakte', 'updated' => 'wijzigde', 'deleted' => 'verwijderde'][$log->event] ?? $log->event }}
                        {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                    </span>
                </div>
            @empty
                <div class="text-muted small">Nog geen activiteit gelogd.</div>
            @endforelse
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong><i class="bi bi-hourglass-split text-boels"></i> Accounts in afwachting</strong>
                <a href="{{ url('/admin/users') }}" class="small">beheer →</a>
            </div>
            @forelse($admin['pendingUsers'] as $p)
                <div class="activity-row">
                    <span class="when">{{ $p->created_at?->locale('nl')->diffForHumans(short: true) }}</span>
                    <span class="flex-grow-1"><strong>{{ $p->name }}</strong> <span class="text-muted">{{ $p->email }}</span></span>
                </div>
            @empty
                <div class="text-muted small">Geen accounts wachten op activatie. 👍</div>
            @endforelse

            @if($admin['lastImport'])
                <hr class="my-2">
                <div class="small text-muted">
                    <i class="bi bi-upload"></i> Laatste import:
                    <a href="{{ url('/admin/imports/'.$admin['lastImport']->id) }}">{{ $admin['lastImport']->original_filename }}</a>
                    ({{ $admin['lastImport']->status }}, {{ $admin['lastImport']->created_at->locale('nl')->diffForHumans() }})
                </div>
            @endif
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('dashSearch');
    const box = document.getElementById('dashResults');
    let timer = null, seq = 0;

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function render(data) {
        const groups = [
            ['Klanten', data.customers], ["Collega's", data.employees],
            ['Producttypes', data.subgroups],
        ];
        let html = '';
        for (const [title, hits] of groups) {
            if (!hits || !hits.length) continue;
            html += `<div class="grp">${title}</div>`;
            for (const h of hits) {
                const inner = `<span class="lbl">${esc(h.label)}</span>` +
                    (h.sub ? ` <span class="sub">${esc(h.sub)}</span>` : '') +
                    (h.email ? `<div class="sub"><i class="bi bi-envelope"></i> ${esc(h.email)}${h.phone ? ' · <i class="bi bi-telephone"></i> ' + esc(h.phone) : ''}</div>` : '');
                html += h.url
                    ? `<a class="hit" href="${esc(h.url)}">${inner}</a>`
                    : `<div class="hit">${inner}</div>`;
            }
        }
        box.innerHTML = html || '<div class="empty">Niets gevonden.</div>';
        box.style.display = 'block';
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { box.style.display = 'none'; return; }
        timer = setTimeout(async () => {
            const mySeq = ++seq;
            try {
                const r = await fetch('{{ route('launcher.search') }}?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                });
                if (!r.ok) return;
                const data = await r.json();
                if (mySeq === seq) render(data);
            } catch (e) { /* stil falen — dashboard blijft bruikbaar */ }
        }, 250);
    });

    // Tegel-bolletjes elke minuut verversen (child-apps melden tellers bij CORE)
    async function refreshBadges() {
        try {
            const r = await fetch('{{ route('launcher.badges') }}', { headers: { 'Accept': 'application/json' } });
            if (!r.ok) return;
            const counts = await r.json();
            document.querySelectorAll('[data-badge-app]').forEach(el => {
                const c = counts[el.dataset.badgeApp] || 0;
                el.textContent = c > 99 ? '99+' : c;
                el.style.display = c > 0 ? 'flex' : 'none';
            });
        } catch (e) { /* stil falen */ }
    }
    setInterval(refreshBadges, 60000);

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dash-search')) box.style.display = 'none';
    });
    input.addEventListener('focus', () => {
        if (box.innerHTML && input.value.trim().length >= 2) box.style.display = 'block';
    });
})();
</script>
@endpush
