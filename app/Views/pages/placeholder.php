<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? config('app.app_name')); ?></h1>
        <p class="page-lead">
            <?php echo e(current_locale() === 'en' ? 'This page is not published yet.' : 'Tahle stránka ještě není publikovaná.'); ?>
        </p>
    </header>
</section>
