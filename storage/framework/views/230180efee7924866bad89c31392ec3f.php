
<?php
    $size = $size ?? 44;
    $light = $light ?? false;
    $hoogte = $light ? 40 : (int) round($size * 0.9);
?>
<img src="<?php echo e(asset('images/core-logo-woordmerk.png')); ?>" alt="CORE"
     style="height:<?php echo e($hoogte); ?>px; display:inline-block; vertical-align:middle;">
<?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/partials/core-logo.blade.php ENDPATH**/ ?>