<?php $__env->startSection('title','Machine '.$machine->machine_number); ?>

<?php $__env->startSection('content'); ?>
<nav class="mb-2 small">
    <a href="<?php echo e(route('admin.material.index')); ?>">Materieel</a>
    <?php if($subgroup?->group?->analysis_group): ?>
        &rsaquo; <a href="<?php echo e(route('admin.material.groups', ['analysis' => $subgroup->group->analysis_group])); ?>"><?php echo e($subgroup->group->analysis_group); ?></a>
    <?php endif; ?>
    <?php if($subgroup?->group): ?>
        &rsaquo; <a href="<?php echo e(route('admin.material.subgroups', ['group' => $subgroup->group->id])); ?>"><?php echo e($subgroup->group->group_name); ?></a>
    <?php endif; ?>
    <?php if($subgroup): ?>
        &rsaquo; <a href="<?php echo e(route('admin.material.show', $subgroup)); ?>"><?php echo e($subgroup->subgroup_number); ?></a>
    <?php endif; ?>
    &rsaquo; <strong><?php echo e($machine->machine_number); ?></strong>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>
        <i class="bi bi-gear-wide-connected text-boels"></i>
        <?php echo e($machine->machine_number); ?>

        <small class="text-muted"><?php echo e($machine->description); ?></small>
    </h3>
    <?php if($subgroup): ?>
        <a href="<?php echo e(route('admin.material.show', $subgroup)); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Subgroep <?php echo e($subgroup->subgroup_number); ?>

        </a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <?php echo $__env->make('partials.sap_machine', ['inHuur' => $inHuur ?? []], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <div class="card mb-3">
            <div class="card-header fw-bold">Deze machine</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><th class="ps-3" style="width:40%">Materieelnummer</th><td><strong><?php echo e($machine->machine_number); ?></strong></td></tr>
                        <tr><th class="ps-3">Omschrijving</th><td><?php echo e($machine->description); ?></td></tr>
                        <?php if($machine->brand): ?><tr><th class="ps-3">Merk</th><td><?php echo e($machine->brand); ?></td></tr><?php endif; ?>
                        <?php if($machine->model): ?><tr><th class="ps-3">Model</th><td><?php echo e($machine->model); ?></td></tr><?php endif; ?>
                        <?php if($machine->serial_number): ?><tr><th class="ps-3">Serienummer</th><td><?php echo e($machine->serial_number); ?></td></tr><?php endif; ?>
                        <?php if($machine->year): ?><tr><th class="ps-3">Bouwjaar</th><td><?php echo e($machine->year); ?></td></tr><?php endif; ?>
                        <?php if($machine->location): ?><tr><th class="ps-3">Locatie</th><td><?php echo e($machine->location); ?></td></tr><?php endif; ?>
                        <tr><th class="ps-3">Subgroep</th>
                            <td>
                                <?php if($subgroup): ?>
                                    <a href="<?php echo e(route('admin.material.show', $subgroup)); ?>"><?php echo e($subgroup->subgroup_number); ?> — <?php echo e($subgroup->subgroup_name); ?></a>
                                <?php else: ?> — <?php endif; ?>
                            </td>
                        </tr>
                        <tr><th class="ps-3">Productgroep</th><td><?php echo e($subgroup?->group?->group_name ?? '—'); ?></td></tr>
                        <tr><th class="ps-3">Analysegroep</th><td><?php echo e($subgroup?->group?->analysis_group ?? '—'); ?></td></tr>
                        <?php if($subgroup?->merk || $subgroup?->type): ?>
                            <tr><th class="ps-3">Merk / Type (subgroep)</th><td><?php echo e(trim(($subgroup->merk ?? '').' '.($subgroup->type ?? ''))); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-muted small">
            <i class="bi bi-info-circle"></i>
            De specificaties hiernaast gelden voor alle machines in subgroep
            <?php echo e($subgroup?->subgroup_number); ?> en komen uit de subgroeplijst.
        </p>
    </div>

    <div class="col-lg-7">
        <?php echo $__env->make('admin.material._specs', ['subgroup' => $subgroup], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/admin/material/machine.blade.php ENDPATH**/ ?>