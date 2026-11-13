
<div class="text-center <?php echo e(!empty($compact) ? 'mt-0 mb-3' : 'mt-3 mb-4'); ?>">
    <h3 class="mb-1">Welkom, <?php echo e(explode(' ', $u->name)[0]); ?></h3>
    <p class="text-muted mb-0"><?php echo e(\Illuminate\Support\Str::ucfirst(now()->locale('nl')->translatedFormat('l j F Y'))); ?>

        <?php if($u->last_login_at): ?> · vorige login <?php echo e($u->last_login_at->locale('nl')->diffForHumans()); ?> <?php endif; ?>
    </p>
</div>


<div class="dash-search mb-2">
    <i class="bi bi-search"></i>
    <input type="search" id="dashSearch" placeholder="Zoek een klant, collega of artikel…" autocomplete="off">
    <div id="dashResults"></div>
</div>
<div class="text-center <?php echo e(!empty($compact) ? 'mb-4' : 'mb-5'); ?>">
    <a href="<?php echo e(route('articles.index')); ?>" class="btn btn-boels btn-sm px-3">
        <i class="bi bi-box-seam"></i> Artikelen zoeken op naam of nummer
    </a>
</div>
<?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/launcher/_kop.blade.php ENDPATH**/ ?>