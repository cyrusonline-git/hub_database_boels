<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name')); ?> <?php if (! empty(trim($__env->yieldContent('title')))): ?> — <?php echo $__env->yieldContent('title'); ?> <?php endif; ?></title>

    
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('images/core-favicon-32.png')); ?>">
    <link rel="icon" type="image/png" sizes="64x64" href="<?php echo e(asset('images/core-favicon-64.png')); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('images/apple-touch-icon.png')); ?>">
    <link rel="manifest" href="<?php echo e(asset('site.webmanifest')); ?>">
    <meta name="apple-mobile-web-app-title" content="CORE">
    <meta name="application-name" content="CORE">
    <meta name="theme-color" content="#17181d">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --boels-orange: <?php echo e($boelsBrand['color'] ?? '#FF6600'); ?>;
            --boels-orange-dark: #cc5200;
            --boels-text-on-orange: <?php echo e($boelsBrand['text_color'] ?? '#FFFFFF'); ?>;
        }
        body { background:#f6f7f9; }
        .navbar-boels { background: var(--boels-orange); }
        .navbar-boels .navbar-brand,
        .navbar-boels .nav-link,
        .navbar-boels .navbar-text { color: var(--boels-text-on-orange) !important; }
        .navbar-boels .nav-link:hover { opacity:.85; }
        .btn-boels { background: var(--boels-orange); color: var(--boels-text-on-orange); border:0; }
        .btn-boels:hover { background: var(--boels-orange-dark); color: var(--boels-text-on-orange); }
        .text-boels { color: var(--boels-orange) !important; }
        .bg-boels { background: var(--boels-orange) !important; color: var(--boels-text-on-orange); }
        .boels-logo {
            display:inline-flex; align-items:center; justify-content:center;
            width:34px; height:34px; border-radius:6px;
            background: var(--boels-orange); color:#fff;
            font-weight:800; font-family:Arial,sans-serif; font-size:20px;
            margin-right:10px;
        }
        .app-tile { transition: transform .15s ease, box-shadow .15s ease; }
        .app-tile:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        .app-tile .icon-circle {
            width:64px; height:64px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:30px; margin:0 auto 12px;
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>

<?php if(auth()->guard()->check()): ?>
<nav class="navbar navbar-expand-lg navbar-boels shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo e(url('/launcher')); ?>">
            <img src="<?php echo e(asset('images/boels-industrial-logo.jpg')); ?>" alt="Boels Industrial"
                 style="height:40px; background:#fff; padding:3px; border-radius:9px; margin-right:12px;">
            <?php echo $__env->make('partials.core-logo', ['size' => 34, 'light' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo e(url('/launcher')); ?>"><i class="bi bi-grid-3x3-gap"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo e(route('articles.index')); ?>"><i class="bi bi-box-seam"></i> Artikelen</a></li>
                <?php if(auth()->user()->is_super_admin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-gear"></i> Beheer</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/users')); ?>">Gebruikers (login)</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/employees')); ?>">Medewerkers</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/roles')); ?>">Rollen</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/permissions')); ?>">Permissies</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/applications')); ?>">Applicaties</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/custom-fields')); ?>">Custom Fields</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/field-aliases')); ?>">Field Aliases</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/infrastructuur')); ?>">Infrastructuur</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/klanten')); ?>">Klanten</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/materieel')); ?>">Materieel</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/material-imports')); ?>">Materieel import</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/sap-uploads')); ?>">Uploads Smartview</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/imports')); ?>">Import Engine</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/handige-links')); ?>">Handige links (dashboard)</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/audit-log')); ?>">Audit Log</a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/table-ownership')); ?>">Tabel-eigendom</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo e(auth()->user()->name); ?>

                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted"><?php echo e(auth()->user()->email); ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo e(route('password.change')); ?>"><i class="bi bi-key"></i> Wachtwoord wijzigen</a></li>
                        <li>
                            <form method="POST" action="<?php echo e(url('/logout')); ?>"><?php echo csrf_field(); ?>
                                <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right"></i> Uitloggen</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="py-4">
    <div class="container-fluid">
        <?php if(session('status')): ?>
            <div class="alert alert-success"><?php echo e(session('status')); ?></div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
            </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </div>
</main>

<footer class="text-center text-muted py-4 small">
    &copy; <?php echo e(date('Y')); ?> Boels — CORE Platform · v<?php echo e(config('app.version', '0.1')); ?>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if(auth()->guard()->check()): ?>
    <?php echo $__env->make('layouts.chat', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /Users/Wim/Desktop/Applicaties/hub_database_Boels/resources/views/layouts/app.blade.php ENDPATH**/ ?>