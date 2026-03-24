<section class="page-section">
    <header class="page-header">
        <h1><?php echo e(t('section.public_log')); ?></h1>
        <p class="page-lead">
            Veřejná etika práce. Achievementy, fuckupy, regen, repair a průběžná reflexe práce v jednom logu.
        </p>
    </header>

    <article class="card">
        <h2>status</h2>
        <p>
            Tohle bude veřejná výpisová stránka. Později se sem načtou měsíce, entry a inline reflexe.
        </p>
    </article>

    <article class="card">
        <h2>how it will work</h2>
        <ul class="mono-list">
            <li>nahoře krátký souhrn období</li>
            <li>pod tím měsíční bloky</li>
            <li>každý achievement / fuckup / regen / repair jako jedna položka</li>
            <li>u veřejných fuckupů možnost rozbalit nebo poslat reflexi</li>
            <li>bez veřejného dashboardu a bez veřejné app navigace navíc</li>
        </ul>
    </article>

    <article class="card">
        <h2>runtime</h2>
        <ul class="mono-list">
            <li>locale: <?php echo e(current_locale()); ?></li>
            <li>skin: <?php echo e(current_skin()); ?></li>
            <li>sleep baseline: <?php echo e((string) config('app.sleep_minutes_per_day')); ?> min</li>
            <li>recovery multiplier: <?php echo e((string) config('app.recovery.workload_multiplier')); ?></li>
        </ul>
    </article>
</section>
