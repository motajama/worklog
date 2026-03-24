<?php
$settings = $settings ?? [];
?>

<section class="page-section">
    <header class="page-header">
        <h1><?php echo e($page_title ?? t('page.admin_settings_title')); ?></h1>
        <p class="page-lead">
            Nastavení intro stránky a základní správa účtu.
        </p>
    </header>

    <div class="grid grid-2">
        <article class="card settings-card">
            <h2>public intro html / cs</h2>

            <form method="post" action="<?php echo e(route_url('admin.settings.update')); ?>">
                <div class="form-row">
                    <label for="home_intro_html_cs"><?php echo e(t('label.home_intro_html_cs')); ?></label>
                    <textarea
                        id="home_intro_html_cs"
                        name="home_intro_html_cs"
                        rows="14"
                        class="code-textarea"
                    ><?php echo e($settings['home_intro_html_cs'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <label for="home_intro_html_en"><?php echo e(t('label.home_intro_html_en')); ?></label>
                    <textarea
                        id="home_intro_html_en"
                        name="home_intro_html_en"
                        rows="14"
                        class="code-textarea"
                    ><?php echo e($settings['home_intro_html_en'] ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit"><?php echo e(t('action.save')); ?></button>
                </div>
            </form>
        </article>

        <article class="card settings-card">
            <h2>change password</h2>

            <form method="post" action="<?php echo e(route_url('admin.settings.password')); ?>">
                <div class="form-row">
                    <label for="current_password"><?php echo e(t('label.current_password')); ?></label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-row">
                    <label for="new_password"><?php echo e(t('label.new_password')); ?></label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <div class="form-row">
                    <label for="new_password_confirm"><?php echo e(t('label.new_password_confirm')); ?></label>
                    <input type="password" id="new_password_confirm" name="new_password_confirm" required>
                </div>

                <div class="form-actions">
                    <button type="submit"><?php echo e(t('action.change_password')); ?></button>
                </div>
            </form>
        </article>
    </div>

    <div class="grid grid-2">
        <article class="card settings-card">
            <h2>preview / cs</h2>
            <div class="rich-html preview-html">
                <?php echo $settings['home_intro_html_cs'] ?? ''; ?>
            </div>
        </article>

        <article class="card settings-card">
            <h2>preview / en</h2>
            <div class="rich-html preview-html">
                <?php echo $settings['home_intro_html_en'] ?? ''; ?>
            </div>
        </article>
    </div>
</section>
