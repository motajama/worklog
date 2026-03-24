<section class="page-section auth-page">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.login_title')); ?></h1>
        <p class="page-lead"><?php echo e(t('help.login')); ?></p>
    </header>

    <article class="card auth-card">
        <form method="post" action="<?php echo e(route_url('auth.login.submit')); ?>">
            <div class="form-row">
                <label for="username"><?php echo e(t('label.username')); ?></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo e((string) old('username')); ?>"
                    required
                >
            </div>

            <div class="form-row">
                <label for="password"><?php echo e(t('label.password')); ?></label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-row">
                <button type="submit"><?php echo e(t('action.login')); ?></button>
            </div>
        </form>
    </article>
</section>
