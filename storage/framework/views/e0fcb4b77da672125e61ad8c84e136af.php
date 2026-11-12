<?php $__env->startSection('title','Inloggen'); ?>

<?php $__env->startSection('content'); ?>
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="text-center mb-4 mt-5">
            <div class="mb-3">
                <?php echo $__env->make('partials.core-logo', ['size' => 52], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <p class="text-muted mb-1" style="letter-spacing:2.5px; text-transform:uppercase; font-size:11px;">Powered by</p>
            <img src="<?php echo e(asset('images/boels-industrial-logo.jpg')); ?>" alt="Boels Industrial"
                 style="width:110px; border-radius:8px;">
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h5 class="card-title mb-3">Inloggen</h5>

                <form method="POST" action="<?php echo e(url('/login')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">E-mailadres</label>
                        <input type="email" name="email" value="<?php echo e(old('email')); ?>" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wachtwoord</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label class="form-check-label" for="remember">Aangemeld blijven</label>
                    </div>
                    <button type="submit" class="btn btn-boels w-100">Inloggen</button>
                </form>

                <div class="text-center mt-3">
                    <a href="<?php echo e(route('password.request')); ?>" class="small">Wachtwoord vergeten?</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/auth/login.blade.php ENDPATH**/ ?>