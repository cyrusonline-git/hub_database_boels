<?php $__env->startSection('title','Uploads Smartview'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-cloud-upload text-boels"></i> Uploads Smartview</h3>
    <a href="<?php echo e(route('admin.sap-uploads.codes')); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-person-badge"></i> Smartview-codes → medewerkers
        <?php if($ongekoppeld): ?><span class="badge bg-warning text-dark ms-1"><?php echo e($ongekoppeld); ?> open</span><?php endif; ?>
    </a>
</div>

<?php if(session('import_error')): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?php echo e(session('import_error')); ?></div><?php endif; ?>
<?php if(session('import_result')): ?><div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?php echo e(session('import_result')); ?></div><?php endif; ?>
<?php if($errors->any()): ?><div class="alert alert-danger"><?php echo e($errors->first()); ?></div><?php endif; ?>

<p class="text-muted">
    Vier Smartview-rapporten voeden het Industrial-dashboard en de andere apps. Exporteer ze in Smartview met de
    standaardnaam hieronder en sleep ze op de juiste kaart. CORE herkent het bestand aan de kopregel;
    een verkeerd bestand wordt geweigerd. Alleen contracten van depot <strong><?php echo e(config('sap.depot') ?: 'alle'); ?></strong> worden meegenomen.
</p>

<div class="row g-3 mb-4">
<?php $__currentLoopData = $soorten; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sleutel => $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-lg-6">
        <div class="card h-100 <?php echo e($s['verouderd'] ? 'border-warning' : ''); ?>">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title mb-1"><?php echo e($s['label']); ?></h5>
                    <span class="badge <?php echo e($s['frequentie_dagen'] <= 1 ? 'bg-boels' : 'bg-secondary'); ?>"><?php echo e($s['frequentie_dagen'] <= 1 ? 'dagelijks' : 'wekelijks'); ?></span>
                </div>
                <div class="small mb-2"><i class="bi bi-file-earmark-excel text-success"></i> <code><?php echo e($s['bestand']); ?></code></div>
                <p class="text-muted small"><?php echo e($s['omschrijving']); ?></p>
                <?php if($s['laatste']): ?>
                    <div class="small mb-3 <?php echo e($s['verouderd'] ? 'text-warning' : 'text-muted'); ?>">
                        <i class="bi <?php echo e($s['verouderd'] ? 'bi-exclamation-triangle' : 'bi-clock-history'); ?>"></i>
                        Laatste stand: <strong><?php echo e($s['laatste']->created_at->format('d-m-Y H:i')); ?></strong>
                        door <?php echo e($s['laatste']->user?->name ?? 'script'); ?> ·
                        <?php echo e(number_format($s['laatste']->regels, 0, ',', '.')); ?> regels, <?php echo e($s['laatste']->contracten); ?> contracten/orders
                        <?php if($s['verouderd']): ?> · <strong>verouderd</strong><?php endif; ?>
                        <?php if(is_array($s['laatste']->samenvatting)): ?>
                            <br>
                            <?php $__currentLoopData = array_filter($s['laatste']->samenvatting, fn($v) => !is_array($v)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="me-2"><?php echo e(str_replace('_', ' ', $k)); ?>: <strong><?php echo e(is_numeric($v) && str_contains((string) $v, '.') ? '€ '.number_format($v, 2, ',', '.') : $v); ?></strong></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="small text-warning mb-3"><i class="bi bi-exclamation-triangle"></i> Nog nooit geüpload.</div>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('admin.sap-uploads.upload')); ?>" enctype="multipart/form-data" class="d-flex gap-2 align-items-center sap-drop" data-soort="<?php echo e($sleutel); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="soort" value="<?php echo e($sleutel); ?>">
                    <input type="file" name="file" class="form-control form-control-sm" accept=".xlsx,.xls,.csv" required>
                    <button type="submit" class="btn btn-boels btn-sm text-nowrap"><i class="bi bi-upload"></i> Verwerken</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-1"></i> Laatste uploads</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>#</th><th>Wanneer</th><th>Soort</th><th>Bestand</th><th>Door</th><th class="text-end">Regels</th><th class="text-end">Contracten</th><th class="text-end">Overgeslagen</th><th>Rapportdatum</th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $historie; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="text-muted"><?php echo e($u->id); ?></td>
                    <td><?php echo e($u->created_at->format('d-m-Y H:i')); ?></td>
                    <td><?php echo e(config('sap.soorten.'.$u->soort.'.label', $u->soort)); ?></td>
                    <td class="small"><?php echo e($u->bestandsnaam); ?></td>
                    <td><?php echo e($u->user?->name ?? 'script'); ?></td>
                    <td class="text-end"><?php echo e(number_format($u->regels, 0, ',', '.')); ?></td>
                    <td class="text-end"><?php echo e($u->contracten); ?></td>
                    <td class="text-end"><?php echo e($u->overgeslagen); ?></td>
                    <td><?php echo e($u->rapportdatum?->format('d-m-Y')); ?><?php echo e($u->regels_opgeruimd ? ' · regels opgeruimd' : ''); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="9" class="text-muted text-center py-3">Nog geen uploads.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3">
    Ook via script: <code>curl -F file=@"&lt;bestand&gt;" "<?php echo e(url('/__sap_upload.php')); ?>?k=&lt;DEPLOY_SECRET&gt;"</code>
    (soort wordt herkend) — of op de server <code>php artisan sap:import &lt;pad&gt;</code>.
</p>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// Slepen op een kaart = bestand kiezen + direct verwerken
document.querySelectorAll('.sap-drop').forEach(form => {
    const card = form.closest('.card');
    ['dragenter','dragover'].forEach(ev => card.addEventListener(ev, e => { e.preventDefault(); card.classList.add('border-boels','shadow'); }));
    ['dragleave','drop'].forEach(ev => card.addEventListener(ev, e => { e.preventDefault(); card.classList.remove('border-boels','shadow'); }));
    card.addEventListener('drop', e => {
        const input = form.querySelector('input[type=file]');
        if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; form.requestSubmit(); }
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/admin/sap_uploads/index.blade.php ENDPATH**/ ?>