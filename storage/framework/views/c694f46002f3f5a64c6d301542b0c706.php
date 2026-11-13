<?php $__env->startSection('title','Dashboard'); ?>

<?php $__env->startPush('styles'); ?>
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
    .dash-tegel { border: 1px solid #e6e8eb; border-radius: 14px; transition: transform .12s, box-shadow .12s, border-color .12s; background: #fff; }
    .dash-tegel:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08); border-color: #FF6600; }
    .dash-tegel .dash-icoon { width: 46px; height: 46px; border-radius: 12px; background: #FF6600; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
    .dash-tegel .lbl { font-weight: 700; color: #222; font-size: 1rem; line-height: 1.2; }
    .dash-tegel .sub { font-size: .78rem; color: #6b7280; margin-top: 2px; }
    .dash-tegel-breed { background: linear-gradient(135deg, #FF6600 0%, #ff8533 100%); border-color: #FF6600; }
    .dash-tegel-breed .dash-icoon { background: rgba(255,255,255,.22); } .dash-tegel-breed .lbl, .dash-tegel-breed .sub, .dash-tegel-breed .bi-chevron-right { color: #fff !important; }
    .app-tile-klein .icon-circle-klein { width: 34px; height: 34px; font-size: 1rem; margin: 0; flex-shrink: 0; }
    .app-tile-klein .app-badge { top: -6px; right: -6px; }
    .vd-kaart { border-radius: 14px; border: 1px solid #e6e8eb; }
    .vd-kaart .card-header { background: #fff; font-weight: 600; border-radius: 14px 14px 0 0; }
    .bg-boels { background: #FF6600 !important; color: #fff; }
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
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php ($u = auth()->user()); ?>

<?php if(strtolower($u->email) === 'lisanne.rodrigues@boels.nl'): ?>
    
    <img src="<?php echo e(asset('images/welkom-lisanne.jpg')); ?>" alt=""
         style="position:fixed; top:102px; right:31px; height:150px; width:auto;
                border-radius:16px; box-shadow:0 4px 14px rgba(0,0,0,.18);
                z-index:1; pointer-events:none;">
<?php endif; ?>

<?php ($metDash = ! empty($dashboardTegels)); ?>

<?php if (! ($metDash)): ?>
    <?php echo $__env->make('launcher._kop', ['u' => $u, 'compact' => false], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>


<div class="row g-4 mb-4">
    <div class="col-lg-3 order-2 order-lg-1">
        <div class="section-title">Handige links</div>
        <div class="card stat-card shadow-sm p-3 mb-3">
            <?php $__empty_1 = true; $__currentLoopData = $quickLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $links): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="link-cat"><?php echo e($category); ?></div>
                <?php $__currentLoopData = $links; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e($link->url); ?>" target="_blank" class="side-link" <?php if($link->description): ?> title="<?php echo e($link->description); ?>" <?php endif; ?>>
                        <i class="<?php echo e($link->icon ?: 'bi-link-45deg'); ?>"></i>
                        <span class="flex-grow-1">
                            <?php echo e($link->title); ?>

                            <?php if($link->description): ?><span class="d-block small text-muted"><?php echo e($link->description); ?></span><?php endif; ?>
                        </span>
                        <i class="bi bi-box-arrow-up-right small text-muted"></i>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-muted small">
                    Nog geen links toegevoegd.
                    <?php if($isAdmin): ?>
                        <a href="<?php echo e(url('/admin/handige-links')); ?>">Voeg rekentools, documenten of sites toe</a> —
                        bijvoorbeeld de generator-rekentool.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if($isAdmin && $quickLinks->isNotEmpty()): ?>
                <a href="<?php echo e(url('/admin/handige-links')); ?>" class="small mt-2 d-inline-block"><i class="bi bi-pencil"></i> links beheren</a>
            <?php endif; ?>
            <hr class="my-2">
            <a href="<?php echo e(route('password.change')); ?>" class="side-link"><i class="bi bi-key"></i><span class="flex-grow-1">Wachtwoord wijzigen</span></a>
        </div>
    </div>

    <div class="col-lg-6 order-1 order-lg-2">
        <?php if($metDash): ?>
        <?php echo $__env->make('launcher._kop', ['u' => $u, 'compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        
        <div class="section-title text-center">Dashboard</div>
        <div class="row g-3">
            <?php $__currentLoopData = $dashboardTegels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="<?php echo e($t['breed'] ? 'col-12' : 'col-12 col-md-6'); ?>">
                <a href="<?php echo e($t['url']); ?>" class="text-decoration-none">
                    <div class="card dash-tegel h-100 <?php echo e($t['breed'] ? 'dash-tegel-breed' : ''); ?>">
                        <div class="d-flex align-items-center gap-3 p-3">
                            <div class="dash-icoon"><i class="<?php echo e($t['icoon']); ?>"></i></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="lbl"><?php echo e($t['label']); ?></div>
                                <div class="sub"><?php echo e($t['sub']); ?></div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        
        <?php if(!empty($vandaag)): ?>
            <?php echo $__env->make('launcher._vandaag', ['vandaag' => $vandaag, 'compact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
        <?php else: ?>
        <div class="section-title text-center">Jouw applicaties</div>
        <?php if($apps->isEmpty()): ?>
            <div class="alert alert-warning">
                Je hebt nog geen toegang tot een applicatie. Neem contact op met een beheerder.
            </div>
        <?php else: ?>
        <div class="row g-4">
            <?php $__currentLoopData = $apps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $app): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-6 col-md-4">
                    <a href="<?php echo e($app->url ?: '#'); ?>" <?php if(! str_starts_with((string) $app->url, '/')): ?> target="_blank" <?php endif; ?> class="text-decoration-none">
                        <div class="card text-center p-4 app-tile h-100 position-relative">
                            <?php ($b = $badges[$app->id] ?? 0); ?>
                            <span class="app-badge" data-badge-app="<?php echo e($app->id); ?>"
                                  <?php if($b > 0): ?> style="display:flex;" <?php endif; ?>><?php echo e($b > 99 ? '99+' : $b); ?></span>
                            <div class="icon-circle" style="background: <?php echo e($app->color); ?>; color: #fff;">
                                <i class="<?php echo e($app->icon ?: 'bi-app'); ?>"></i>
                            </div>
                            <h6 class="mb-1 text-dark"><?php echo e($app->name); ?></h6>
                            <small class="text-muted"><?php echo e($app->description); ?></small>
                        </div>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="col-lg-3 order-3">
        <?php if($metDash): ?>
        <div class="section-title">Jouw applicaties</div>
        <?php if($apps->isEmpty()): ?>
            <div class="text-muted small">Nog geen applicaties.</div>
        <?php else: ?>
        <div class="row g-2">
            <?php $__currentLoopData = $apps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $app): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-6 col-lg-12">
                    <a href="<?php echo e($app->url ?: '#'); ?>" <?php if(! str_starts_with((string) $app->url, '/')): ?> target="_blank" <?php endif; ?> class="text-decoration-none">
                        <div class="card app-tile app-tile-klein h-100 position-relative">
                            <?php ($b = $badges[$app->id] ?? 0); ?>
                            <span class="app-badge" data-badge-app="<?php echo e($app->id); ?>" <?php if($b > 0): ?> style="display:flex;" <?php endif; ?>><?php echo e($b > 99 ? '99+' : $b); ?></span>
                            <div class="d-flex align-items-center gap-2 p-2">
                                <div class="icon-circle icon-circle-klein" style="background: <?php echo e($app->color); ?>; color: #fff;"><i class="<?php echo e($app->icon ?: 'bi-app'); ?>"></i></div>
                                <div class="min-w-0"><div class="text-dark fw-semibold small text-truncate"><?php echo e($app->name); ?></div></div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>


<?php if($isAdmin && $admin): ?>

<div class="section-title text-center">Platform-overzicht</div>
<div class="row g-3 justify-content-center mb-4">
    <?php $__currentLoopData = [
        ['bi-people', number_format($admin['stats']['employees'], 0, ',', '.'), 'Actieve medewerkers', url('/admin/employees')],
        ['bi-building', number_format($admin['stats']['customers'], 0, ',', '.'), 'Klanten', url('/admin/klanten')],
        ['bi-tools', number_format($admin['stats']['machines'], 0, ',', '.'), 'Machines', url('/admin/materieel')],
        ['bi-person-check', number_format($admin['stats']['users_active'], 0, ',', '.'), 'Actieve accounts', url('/admin/users')],
        ['bi-hourglass-split', number_format($admin['stats']['users_pending'], 0, ',', '.'), 'Wacht op activatie', url('/admin/users?status=pending_activation')],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$icon, $num, $lbl, $href]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="<?php echo e($href); ?>" class="text-decoration-none">
                <div class="card stat-card shadow-sm p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon"><i class="<?php echo e($icon); ?>"></i></div>
                        <div>
                            <div class="num text-dark"><?php echo e($num); ?></div>
                            <div class="lbl"><?php echo e($lbl); ?></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="row g-3 justify-content-center mb-4">
    <div class="col-lg-6">
        <div class="card stat-card shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong><i class="bi bi-clock-history text-boels"></i> Laatste activiteit</strong>
                <a href="<?php echo e(url('/admin/audit-log')); ?>" class="small">alles →</a>
            </div>
            <?php $__empty_1 = true; $__currentLoopData = $admin['auditLogs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="activity-row">
                    <span class="when"><?php echo e($log->created_at->locale('nl')->diffForHumans(short: true)); ?></span>
                    <span class="flex-grow-1">
                        <strong><?php echo e($log->user->name ?? 'Systeem'); ?></strong>
                        <?php echo e(['created' => 'maakte', 'updated' => 'wijzigde', 'deleted' => 'verwijderde'][$log->event] ?? $log->event); ?>

                        <?php echo e(class_basename($log->auditable_type)); ?> #<?php echo e($log->auditable_id); ?>

                    </span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-muted small">Nog geen activiteit gelogd.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card stat-card shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong><i class="bi bi-hourglass-split text-boels"></i> Accounts in afwachting</strong>
                <a href="<?php echo e(url('/admin/users')); ?>" class="small">beheer →</a>
            </div>
            <?php $__empty_1 = true; $__currentLoopData = $admin['pendingUsers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="activity-row">
                    <span class="when"><?php echo e($p->created_at?->locale('nl')->diffForHumans(short: true)); ?></span>
                    <span class="flex-grow-1"><strong><?php echo e($p->name); ?></strong> <span class="text-muted"><?php echo e($p->email); ?></span></span>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-muted small">Geen accounts wachten op activatie. 👍</div>
            <?php endif; ?>

            <?php if($admin['lastImport']): ?>
                <hr class="my-2">
                <div class="small text-muted">
                    <i class="bi bi-upload"></i> Laatste import:
                    <a href="<?php echo e(url('/admin/imports/'.$admin['lastImport']->id)); ?>"><?php echo e($admin['lastImport']->original_filename); ?></a>
                    (<?php echo e($admin['lastImport']->status); ?>, <?php echo e($admin['lastImport']->created_at->locale('nl')->diffForHumans()); ?>)
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
            ['Producttypes', data.subgroups], ['Contracten en orders (Smartview)', data.contracten],
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
                const r = await fetch('<?php echo e(route('launcher.search')); ?>?q=' + encodeURIComponent(q), {
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
            const r = await fetch('<?php echo e(route('launcher.badges')); ?>', { headers: { 'Accept': 'application/json' } });
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/launcher/index.blade.php ENDPATH**/ ?>