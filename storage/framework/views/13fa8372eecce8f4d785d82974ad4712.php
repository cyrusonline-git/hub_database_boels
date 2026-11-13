<?php $__env->startSection('title', $customer->customer_name); ?>

<?php $__env->startSection('content'); ?>
<?php ($adminView = request()->routeIs('admin.*')); ?>

<?php if($adminView): ?>
    <nav class="mb-2 small">
        <a href="<?php echo e(route('admin.customers.index')); ?>">Klanten</a>
        <?php if($customer->concern_name): ?>
            &rsaquo; <a href="<?php echo e(route('admin.customers.index', ['concern' => $customer->concern_number])); ?>"><?php echo e($customer->concern_name); ?></a>
        <?php endif; ?>
        &rsaquo; <strong><?php echo e($customer->customer_number); ?></strong>
    </nav>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-building text-boels"></i>
        <?php echo e($customer->customer_name); ?>

        <small class="text-muted">#<?php echo e($customer->customer_number); ?></small>
    </h3>
    <?php if($adminView): ?>
        <a href="<?php echo e(route('admin.customers.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Terug naar klanten
        </a>
    <?php else: ?>
        <a href="<?php echo e(route('launcher')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Terug naar dashboard
        </a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-bold">Debiteur</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Debiteurnummer</th><td><?php echo e($customer->customer_number); ?></td></tr>
                        <tr><th class="ps-3">Naam</th><td><?php echo e($customer->customer_name); ?></td></tr>
                        <tr><th class="ps-3">Tweede naam</th><td><?php echo e($customer->second_name ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Verantwoordelijke</th><td><?php echo e($customer->responsible ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Rol</th><td><?php echo e($customer->responsible_role ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Branche (NACE)</th>
                            <td><?php if($customer->nace_code): ?><?php echo e($customer->nace_code); ?> — <?php echo e($customer->nace_description); ?><?php else: ?> — <?php endif; ?></td></tr>
                        <tr><th class="ps-3">Bron</th><td><?php echo e($customer->source_system ?? '—'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-bold">Inkooporganisatie</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Nummer</th><td><?php echo e($customer->purchasing_org_number ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Naam</th><td><?php echo e($customer->purchasing_org_name ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Verantwoordelijke</th><td><?php echo e($customer->purchasing_org_responsible ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Rol</th><td><?php echo e($customer->purchasing_org_responsible_role ?? '—'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header fw-bold">Concern</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Concernnummer</th><td><?php echo e($customer->concern_number ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Naam</th><td><?php echo e($customer->concern_name ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Verantwoordelijke</th><td><?php echo e($customer->concern_responsible ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Rol</th><td><?php echo e($customer->concern_responsible_role ?? '—'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php echo $__env->make('partials.sap_klant', ['sap' => $sap ?? []], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="card">
            <div class="card-header fw-bold">
                Andere debiteuren binnen dit concern
                <span class="badge bg-secondary"><?php echo e($concernCustomers->count()); ?></span>
            </div>
            <?php if($concernCustomers->isEmpty()): ?>
                <div class="card-body text-muted">Geen andere debiteuren in dit concern.</div>
            <?php else: ?>
                <ul class="list-group list-group-flush" style="max-height:340px; overflow-y:auto;">
                    <?php $__currentLoopData = $concernCustomers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <a href="<?php echo e($adminView ? route('admin.customers.show', $cc) : route('customers.view', $cc)); ?>"><?php echo e($cc->customer_name); ?></a>
                            <span class="text-muted">#<?php echo e($cc->customer_number); ?></span>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/admin/customers/show.blade.php ENDPATH**/ ?>