
<div class="card mb-3">
    <div class="card-header fw-bold">
        Specificaties
        <?php if($subgroup?->specifications): ?><span class="badge bg-success"><?php echo e(count($subgroup->specifications)); ?></span><?php endif; ?>
    </div>
    <?php if($subgroup?->specifications): ?>
        <div class="card-body p-0" style="max-height:420px; overflow-y:auto;">
            <table class="table table-sm table-striped mb-0">
                <tbody>
                    <?php $__currentLoopData = $subgroup->specifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr><th class="ps-3" style="width:45%"><?php echo e($key); ?></th><td><?php echo e($value); ?></td></tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card-body text-muted">
            Geen specificaties — deze subgroep staat (nog) niet in de subgroeplijst.
        </div>
    <?php endif; ?>
</div>

<?php if($subgroup?->highlights): ?>
    <div class="card mb-3">
        <div class="card-header fw-bold">Highlights</div>
        <ul class="list-group list-group-flush">
            <?php $__currentLoopData = $subgroup->highlights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li class="list-group-item"><?php echo e($h); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<?php $__currentLoopData = ['accessoires' => 'Accessoires', 'verkoopartikelen' => 'Verkoopartikelen', 'alternatieven' => 'Alternatieven']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if($subgroup?->$field): ?>
        <div class="card mb-3">
            <div class="card-header fw-bold"><?php echo e($label); ?></div>
            <ul class="list-group list-group-flush">
                <?php $__currentLoopData = $subgroup->$field; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li class="list-group-item"><?php echo e($item); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/admin/material/_specs.blade.php ENDPATH**/ ?>