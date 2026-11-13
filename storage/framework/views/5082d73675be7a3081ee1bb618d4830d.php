
<?php ($d = $vandaag['dashboard']); ?>
<?php ($pick = fn ($o) => $o->pick_status === 'onbekend' ? 'nog niet in 8.7' : ($o->pick_status === 'geen_regels' ? 'geen regels' : (($o->picked + 0).'/'.($o->total + 0).' gepickt'))); ?>
<div class="section-title text-center mt-2">Vandaag</div>
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 vd-kaart"><div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-truck text-boels me-1"></i>Te leveren vandaag</span><span><?php if(!empty($vandaag['vandaag']['niet_op_scanner'])): ?> <a href="<?php echo e($d); ?>/planning?niet_op_scanner=1" class="badge bg-danger text-decoration-none me-1" title="PickUpInfo leeg in Smartview: de expeditie ziet deze orders niet op de scanner"><?php echo e($vandaag['vandaag']['niet_op_scanner']); ?> niet op scanner</a><?php endif; ?> <span class="badge bg-boels"><?php echo e($vandaag['vandaag']['aantal']); ?></span></span></div>
            <ul class="list-group list-group-flush small">
                <?php $__empty_1 = true; $__currentLoopData = $vandaag['vandaag']['orders']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-1"><a href="<?php echo e($d); ?>/planning/<?php echo e($o->order_number); ?>" class="text-truncate me-2 text-decoration-none text-dark" title="<?php echo e($o->customer_name); ?>"><?php echo e($o->customer_name); ?></a><span class="badge rounded-pill <?php echo e($o->pick_status === 'volledig' ? 'bg-success' : ($o->pick_status === 'deels' ? 'bg-warning text-dark' : 'bg-light text-muted border')); ?>"><?php echo e($pick($o)); ?></span></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="list-group-item text-muted py-2">Geen orders met leverdatum vandaag.</li>
                <?php endif; ?>
            </ul>
            <div class="card-footer small bg-white d-flex justify-content-between"><span class="text-muted">morgen: <?php echo e($vandaag['vandaag']['morgen']); ?></span><a href="<?php echo e($d); ?>/planning">planning →</a></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 vd-kaart"><div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-arrow-return-left text-boels me-1"></i>Op te halen</span><span class="badge <?php echo e($vandaag['ophalen']['te_laat'] ? 'bg-danger' : 'bg-boels'); ?>"><?php echo e($vandaag['ophalen']['aantal']); ?></span></div>
            <ul class="list-group list-group-flush small">
                <?php $__empty_1 = true; $__currentLoopData = $vandaag['ophalen']['lijst']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-1 <?php echo e($r->te_laat ? 'text-danger' : ''); ?>"><a href="<?php echo e($d); ?>/in-huur/<?php echo e($r->contract_number); ?>" class="text-truncate me-2 text-decoration-none <?php echo e($r->te_laat ? 'text-danger' : 'text-dark'); ?>" title="<?php echo e($r->customer_name); ?>"><?php echo e($r->customer_name); ?></a><span class="text-nowrap"><?php echo e($r->coll_date ? \Carbon\Carbon::parse($r->coll_date)->format('D j M') : '—'); ?> <?php echo e($r->coll_time); ?><?php if($r->coll_doc_printed): ?> <i class="bi bi-printer text-success" title="bon geprint"></i><?php endif; ?></span></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="list-group-item text-muted py-2">Niets afgemeld voor vandaag of morgen.</li>
                <?php endif; ?>
            </ul>
            <div class="card-footer small bg-white d-flex justify-content-between"><span class="text-muted"><?php echo e($vandaag['ophalen']['te_laat']); ?> te laat</span><a href="<?php echo e($d); ?>/in-huur/ophalen">ophaallijst →</a></div>
        </div>
    </div>
    <?php if(($vandaag['factuurrun']['contracten'] ?? null) !== null): ?>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 vd-kaart"><div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-receipt text-boels me-1"></i><?php echo e($vandaag['factuurrun']['eigen'] ? 'Mijn factuurrun' : 'Uit factuurrun'); ?></span><span class="badge <?php echo e($vandaag['factuurrun']['zonder_reden'] ? 'bg-warning text-dark' : 'bg-success'); ?>"><?php echo e($vandaag['factuurrun']['zonder_reden']); ?> zonder reden</span></div>
            <div class="card-body py-2 small">
                <div class="d-flex justify-content-between"><span>Contracten</span><strong><?php echo e($vandaag['factuurrun']['contracten']); ?></strong></div>
                <div class="d-flex justify-content-between"><span>Nooit gefactureerd</span><strong><?php echo e($vandaag['factuurrun']['nooit']); ?></strong></div>
                <div class="d-flex justify-content-between"><span>Weekhuur niet gefactureerd</span><strong>€ <?php echo e(number_format($vandaag['factuurrun']['weekhuur'], 0, ',', '.')); ?></strong></div>
                <?php if($vandaag['factuurrun']['top']->isNotEmpty()): ?><hr class="my-2"><?php $__currentLoopData = $vandaag['factuurrun']['top']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div class="d-flex justify-content-between"><a href="<?php echo e($d); ?>/factuurrun/<?php echo e($t['contract_number']); ?>" class="text-truncate me-2 text-decoration-none text-dark"><?php echo e($t['customer_name']); ?></a><span class="text-muted text-nowrap">€ <?php echo e(number_format($t['weekhuur'], 0, ',', '.')); ?>/wk</span></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> <?php endif; ?>
            </div>
            <div class="card-footer small bg-white text-end"><a href="<?php echo e($d); ?>/factuurrun<?php echo e($vandaag['factuurrun']['eigen'] ? '' : '?mijn=0'); ?>&zonder_reden=1">factuurrun →</a></div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 vd-kaart"><div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-bell text-boels me-1"></i>Signalen voor mij</span><span class="badge bg-boels"><?php echo e(array_sum(array_column($vandaag['signalen'], 'aantal'))); ?></span></div>
            <ul class="list-group list-group-flush small">
                <?php $__empty_1 = true; $__currentLoopData = $vandaag['signalen']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-1"><a href="<?php echo e($s['url'] ?? '#'); ?>" class="text-decoration-none text-dark text-truncate me-2"><?php echo e($s['label']); ?></a><span class="badge bg-secondary rounded-pill"><?php echo e($s['aantal']); ?></span></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="list-group-item text-muted py-2">Niets open op jouw naam. 👍</li>
                <?php endif; ?>
            </ul>
            <div class="card-footer small bg-white text-end"><a href="<?php echo e($d); ?>/">dashboard →</a></div>
        </div>
    </div>
</div>
<div class="card mb-4 vd-kaart">
    <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-bar-chart-line text-boels me-1"></i>Planning komende 7 dagen</span><a href="<?php echo e($d); ?>/planbord" class="small">planbord →</a></div>
    <div class="card-body py-2"><canvas id="planningWeek" height="60"></canvas></div>
</div>
<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('js/chart.umd.min.js')); ?>?v=4.4.1"></script>
<script>
(function () {
  try {
    const w = <?php echo json_encode($vandaag['planning_week'], 15, 512) ?>;
    const lbl = w.map(d => new Date(d.datum).toLocaleDateString('nl-NL', { weekday: 'short', day: 'numeric', month: 'short' }));
    new Chart(document.getElementById('planningWeek'), { type: 'bar', data: { labels: lbl, datasets: [
        { label: 'Volledig gepickt', data: w.map(d => d.volledig), backgroundColor: '#2e7d32', borderRadius: 4 },
        { label: 'Deels', data: w.map(d => d.deels), backgroundColor: '#ef6c00', borderRadius: 4 },
        { label: 'Nog niet', data: w.map(d => d.niets), backgroundColor: '#cfd4d9', borderRadius: 4 } ] },
      options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } } });
  } catch (e) { console.error('Grafiek', e); const c = document.getElementById('planningWeek'); if (c) c.insertAdjacentHTML('afterend', '<div class="text-danger small">Grafiek kon niet laden: ' + e.message + '</div>'); }
})();
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/launcher/_vandaag.blade.php ENDPATH**/ ?>