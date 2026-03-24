<section class="page-section">
    <header class="page-header">
        <h1><?php echo e(t('section.public_ethics_of_work')); ?></h1>
        <p class="page-lead">
            Open-source pracovní log pro achievements, fuckupy, regen, repair, projekty a veřejnou reflexi.
        </p>
    </header>

    <div class="grid grid-2">
        <article class="card">
            <h2><?php echo e(t('label.achievement')); ?></h2>
            <p>Co se povedlo. Co se pohnulo. Co má smysl zaznamenat jako skutečný kus práce.</p>
        </article>

        <article class="card">
            <h2><?php echo e(t('label.fuckup')); ?></h2>
            <p>Co drhlo. Kde vzniklo dilema. Co se nepovedlo a stojí za veřejnou reflexi.</p>
        </article>

        <article class="card">
            <h2><?php echo e(t('label.regen')); ?></h2>
            <p>Obnova není bonus. Je to součást práce. Spánek se počítá automaticky, aktivní regen se loguje.</p>
        </article>

        <article class="card">
            <h2><?php echo e(t('label.balance')); ?></h2>
            <p>Workload versus recovery. Ne výkon pro výkon, ale udržitelný rytmus práce a obnovy.</p>
        </article>
    </div>

    <div class="grid grid-2">
        <article class="card">
            <h2>quick jump</h2>
            <ul class="mono-list">
                <li><a href="<?php echo e(route_url('archive')); ?>">archive</a></li>
                <li><a href="<?php echo e(route_url('projects.index')); ?>">projects</a></li>
                <li><a href="<?php echo e(route_url('fuckups.index')); ?>">fuckups</a></li>
                <li><a href="<?php echo e(route_url('method')); ?>">method</a></li>
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
    </div>
</section>
