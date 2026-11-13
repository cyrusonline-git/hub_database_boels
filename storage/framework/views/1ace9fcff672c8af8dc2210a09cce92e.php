
<?php if(!empty($inHuur['beschikbaar'])): ?>
<div class="card mb-3 <?php echo e(!empty($inHuur['regels']) ? 'border-warning' : ''); ?>">
    <div class="card-header fw-bold"><i class="bi bi-geo-alt text-boels me-1"></i>Waar staat dit nu? <small class="text-muted fw-normal">volgens SAP, stand <?php echo e(\Carbon\Carbon::parse($inHuur['stand'])->format('d-m H:i')); ?></small></div>
    <div class="card-body">
        <?php $__empty_1 = true; $__currentLoopData = $inHuur['regels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="mb-2">
                <strong><?php echo e($r['customer_name']); ?></strong> · <a href="<?php echo e($inHuur['dashboard']); ?>/in-huur/<?php echo e($r['contract_number']); ?>">contract <?php echo e($r['contract_number']); ?></a><br>
                <span class="small text-muted"><?php echo e($r['project_description'] ?? $r['delivery_name']); ?><?php echo e($r['delivery_address'] ? ' · '.$r['delivery_address'] : ''); ?> · sinds <?php echo e($r['hire_date'] ? \Carbon\Carbon::parse($r['hire_date'])->format('d-m-Y') : '—'); ?> · <?php echo e(str_contains($r['status'], 'To Collect') ? 'afgemeld, ophalen '.($r['coll_date'] ? \Carbon\Carbon::parse($r['coll_date'])->format('d-m-Y') : '') : 'in huur'); ?> · € <?php echo e(number_format($r['weekly_hire_rate'], 2, ',', '.')); ?>/wk</span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <span class="text-muted">Niet in huur bij een klant van depot <?php echo e(config('sap.depot') ?: '—'); ?> (staat vermoedelijk op het depot, in onderhoud of bij een ander depot).</span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/partials/sap_machine.blade.php ENDPATH**/ ?>