
<?php if(!empty($sap['beschikbaar'])): ?>
<?php ($d = $sap['dashboard']); ?>
<div class="card mb-3">
    <div class="card-header fw-bold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-box-seam text-boels me-1"></i>Bij deze klant volgens Smartview</span>
        <?php if($sap['stand']): ?><small class="text-muted fw-normal">stand <?php echo e(\Carbon\Carbon::parse($sap['stand'])->format('d-m H:i')); ?></small><?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row g-2 text-center mb-3">
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="fs-5 fw-bold"><?php echo e($sap['huur']['contracten'] ?? 0); ?></div><div class="small text-muted">contracten in huur</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2"><div class="fs-5 fw-bold">€ <?php echo e(number_format($sap['huur']['weekhuur'] ?? 0, 0, ',', '.')); ?></div><div class="small text-muted">weekhuur</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2 <?php echo e(($sap['huur']['to_collect_contracten'] ?? 0) ? 'border-danger' : ''); ?>"><div class="fs-5 fw-bold"><?php echo e($sap['huur']['to_collect_contracten'] ?? 0); ?></div><div class="small text-muted">afgemeld, op te halen</div></div></div>
            <div class="col-6 col-md-3"><div class="border rounded p-2 <?php echo e(($sap['factuurrun']['zonder_reden'] ?? 0) ? 'border-warning' : ''); ?>"><div class="fs-5 fw-bold"><?php echo e($sap['factuurrun']['contracten'] ?? 0); ?></div><div class="small text-muted">uit factuurrun<?php echo e(($sap['factuurrun']['zonder_reden'] ?? 0) ? ' · '.$sap['factuurrun']['zonder_reden'].' zonder reden' : ''); ?></div></div></div>
        </div>
        <?php if(!empty($sap['huur']['top'])): ?>
        <div class="small text-uppercase text-muted fw-semibold mb-1">Grootste contracten</div>
        <table class="table table-sm mb-2"><tbody>
            <?php $__currentLoopData = $sap['huur']['top']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td><a href="<?php echo e($d); ?>/in-huur/<?php echo e($c['contract_number']); ?>"><code><?php echo e($c['contract_number']); ?></code></a></td><td class="small"><?php echo e($c['project'] ?? $c['delivery_name']); ?></td><td class="small text-muted"><?php echo e($c['regels']); ?> regels · sinds <?php echo e($c['sinds'] ? \Carbon\Carbon::parse($c['sinds'])->format('d-m-Y') : '—'); ?></td><td class="text-end">€ <?php echo e(number_format($c['weekhuur'], 0, ',', '.')); ?>/wk</td><td><?php if($c['to_collect'] > 0): ?><span class="badge bg-danger"><?php echo e($c['to_collect']); ?> afgemeld</span><?php endif; ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody></table>
        <?php endif; ?>
        <?php if(!empty($sap['planning'])): ?>
        <div class="small text-uppercase text-muted fw-semibold mb-1">Aankomende orders</div>
        <ul class="list-unstyled small mb-2">
            <?php $__currentLoopData = $sap['planning']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><a href="<?php echo e($d); ?>/planning/<?php echo e($o['order_number']); ?>"><code><?php echo e($o['order_number']); ?></code></a> <?php echo e(ucfirst(str_replace('_', ' ', $o['soort']))); ?> · <?php echo e(\Carbon\Carbon::parse($o['delivery_date'])->format('d-m-Y')); ?> · <?php echo e($o['picked'] + 0); ?>/<?php echo e($o['total'] + 0); ?> gepickt</li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php endif; ?>
        <?php if(!empty($sap['offertes'])): ?>
        <div class="small text-uppercase text-muted fw-semibold mb-1">Offertes en toekomstige contracten (2.6)</div>
        <ul class="list-unstyled small mb-2">
            <?php $__currentLoopData = $sap['offertes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><a href="<?php echo e($d); ?>/planning/<?php echo e($o['contract_number']); ?>"><code><?php echo e($o['contract_number']); ?></code></a> <?php echo e($o['is_contract'] ? 'contract' : 'offerte'); ?> (<?php echo e($o['offerte_status']); ?>) · lever <?php echo e($o['delivery_date'] ? \Carbon\Carbon::parse($o['delivery_date'])->format('d-m-Y') : '—'); ?> · € <?php echo e(number_format($o['goods_value'], 0, ',', '.')); ?> · <?php echo e($o['regels']); ?> regels</li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php endif; ?>
        <div class="small">
            <a href="<?php echo e($d); ?>/in-huur?customer=<?php echo e(urlencode($customer->customer_number)); ?>">Alles in huur</a>
            <?php if(($sap['factuurrun']['contracten'] ?? 0) > 0): ?> · <a href="<?php echo e($d); ?>/factuurrun?mijn=0&customer=<?php echo e(urlencode($customer->customer_number)); ?>">Uit factuurrun</a><?php endif; ?>
            · <a href="<?php echo e($d); ?>/planning?q=<?php echo e(urlencode($customer->customer_name)); ?>&alles=1">Planning</a>
        </div>
    </div>
</div>
<?php endif; ?>
<?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/partials/sap_klant.blade.php ENDPATH**/ ?>