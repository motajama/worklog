<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? 'placeholder'); ?></h1>
        <p class="page-lead">Tahle stránka už má route, layout, locale a skin. Další krok je napojení na DB a reálný obsah.</p>
    </header>

    <article class="card">
        <h2>route</h2>
        <pre><?php echo e(json_encode($route, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
    </article>

    <article class="card">
        <h2>params</h2>
        <pre><?php echo e(json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
    </article>
</section>
