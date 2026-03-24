<!DOCTYPE html>
<html lang="<?php echo e(current_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title ?? config('app.app_name')); ?> — <?php echo e(config('app.app_name')); ?></title>
    <meta name="description" content="<?php echo e(config('app.site_meta.description_' . current_locale(), config('app.app_name'))); ?>">

    <link rel="stylesheet" href="<?php echo e(asset('assets/css/base.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/skins/' . current_skin() . '.css')); ?>">
</head>
<body class="<?php echo is_admin_route() ? 'is-admin' : 'is-public'; ?>" data-skin="<?php echo e(current_skin()); ?>">
    <div class="app-shell">
        <header class="site-header">
            <div class="site-branding">
                <div class="site-title-wrap">
                    <a class="site-title" href="<?php echo e(route_url('home')); ?>">
                        <?php echo e(config('app.app_name')); ?>
                    </a>
                    <div class="site-tagline">
                        <?php echo e(config('app.site_meta.tagline_' . current_locale(), 'public ethics of work')); ?>
                    </div>
                </div>
            </div>

            <div class="site-controls">
                <div class="control-group">
                    <span class="control-label">lang</span>
                    <a href="<?php echo e(url('?lang=cs')); ?>" class="control-link <?php echo current_locale() === 'cs' ? 'is-active' : ''; ?>">CZ</a>
                    <a href="<?php echo e(url('?lang=en')); ?>" class="control-link <?php echo current_locale() === 'en' ? 'is-active' : ''; ?>">EN</a>
                </div>

                <div class="control-group">
                    <span class="control-label">skin</span>
                    <?php foreach (config('app.available_skins', []) as $skin): ?>
                        <a href="<?php echo e(url('?skin=' . rawurlencode($skin))); ?>" class="control-link <?php echo current_skin() === $skin ? 'is-active' : ''; ?>">
                            <?php echo e($skin); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </header>

        <nav class="site-nav" aria-label="Main navigation">
            <?php foreach (navigation_items() as $item): ?>
                <a
                    href="<?php echo e(route_url($item['route'])); ?>"
                    class="nav-link <?php echo route_is($item['route']) ? 'is-active' : ''; ?>"
                >
                    <?php echo e(t($item['label_key'])); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <main class="site-main">
            <?php echo $content; ?>
        </main>

        <footer class="site-footer">
            <div class="footer-line">
                <span><?php echo e(config('app.app_name')); ?></span>
                <span>v<?php echo e(config('app.app_version')); ?></span>
                <span><?php echo e(current_skin()); ?></span>
                <span><?php echo e(current_locale()); ?></span>
            </div>
        </footer>
    </div>
</body>
</html>
