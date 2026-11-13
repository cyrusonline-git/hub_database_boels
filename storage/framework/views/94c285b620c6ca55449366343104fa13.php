<?php $__env->startSection('title','Smartview-codes → medewerkers'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3><i class="bi bi-person-badge text-boels"></i> Smartview-codes → medewerkers</h3>
    <div class="d-flex gap-2">
        <?php if($gebruikers->isNotEmpty()): ?>
        <form method="POST" action="<?php echo e(route('admin.sap-uploads.maak-kaarten')); ?>" onsubmit="return confirm('Voor <?php echo e($gebruikers->count()); ?> gebruiker(s) zonder medewerkerkaart een kaart aanmaken en het account koppelen?')">
            <?php echo csrf_field(); ?>
            <button class="btn btn-boels btn-sm"><i class="bi bi-person-plus"></i> Kaarten aanmaken voor <?php echo e($gebruikers->count()); ?> gebruiker(s) zonder medewerkerkaart</button>
        </form>
        <?php endif; ?>
        <a href="<?php echo e(route('admin.sap-uploads.index')); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Uploads Smartview</a>
    </div>
</div>
<?php if(session('ok')): ?><div class="alert alert-success"><?php echo e(session('ok')); ?></div><?php endif; ?>
<p class="text-muted">
    Smartview schrijft de aanmaker als code: de eerste vijf letters van de achternaam (zonder tussenvoegsel) plus de eerste letter
    van de voornaam — <em>Wouter Zwackhalen</em> = <code>ZWACKW</code>. CORE koppelt automatisch als precies één medewerker
    past; de rest koppel je hier eenmalig. Staat iemand alleen als <strong>gebruiker</strong> in CORE (zonder medewerkerkaart), kies dan die gebruiker:
    CORE maakt dan een medewerkerkaart aan en koppelt het account eraan. Namen die SAP voluit schrijft (planning) staan als <code>NAAM:…</code>.
</p>
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Code / naam in Smartview</th><th>Gezien</th><th>Medewerker in CORE</th><th>Bron</th><th style="width:360px">Koppelen</th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $rijen; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="<?php echo e($c->employee_id ? '' : 'table-warning'); ?>">
                    <td><code><?php echo e($c->code); ?></code><?php if($c->weergave && $c->weergave !== $c->code): ?><div class="small text-muted"><?php echo e($c->weergave); ?></div><?php endif; ?></td>
                    <td class="small text-muted"><?php echo e($c->gezien); ?>× · <?php echo e($c->laatst_gezien_at?->format('d-m-Y')); ?></td>
                    <td><?php echo e($c->employee?->name ?? '—'); ?></td>
                    <td><span class="badge <?php echo e($c->bron === 'handmatig' ? 'bg-primary' : ($c->bron === 'auto' ? 'bg-success' : 'bg-warning text-dark')); ?>"><?php echo e($c->bron); ?></span></td>
                    <td>
                        <form method="POST" action="<?php echo e(route('admin.sap-uploads.koppel', $c)); ?>" class="d-flex gap-1">
                            <?php echo csrf_field(); ?>
                            <select name="employee_id" class="form-select form-select-sm">
                                <option value="">— niet gekoppeld —</option>
                                <?php if($c->kandidaten->isNotEmpty() || $c->gebruikerKandidaten->isNotEmpty()): ?>
                                    <optgroup label="Suggesties">
                                        <?php $__currentLoopData = $c->kandidaten; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($k['id']); ?>"><?php echo e($k['naam']); ?> (<?php echo e($k['code']); ?>)</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php $__currentLoopData = $c->gebruikerKandidaten; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="user:<?php echo e($u['id']); ?>"><?php echo e($u['naam']); ?> — gebruiker, kaart wordt aangemaakt</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </optgroup>
                                <?php endif; ?>
                                <optgroup label="Medewerkers">
                                    <?php $__currentLoopData = $medewerkers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($m->id); ?>" <?php if($c->employee_id === $m->id): echo 'selected'; endif; ?>><?php echo e($m->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </optgroup>
                                <?php if($gebruikers->isNotEmpty()): ?>
                                <optgroup label="Gebruikers zonder medewerkerkaart (kaart wordt aangemaakt)">
                                    <?php $__currentLoopData = $gebruikers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="user:<?php echo e($u['id']); ?>"><?php echo e($u['naam']); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </optgroup>
                                <?php endif; ?>
                            </select>
                            <button class="btn btn-boels btn-sm">Opslaan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/admin/sap_uploads/codes.blade.php ENDPATH**/ ?>