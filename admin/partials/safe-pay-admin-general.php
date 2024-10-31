<?php

defined('ABSPATH') || exit;

$all_options = get_option('safepay_options');
?>
<div class="wrap safepay-settings">
    <h2><?php echo get_admin_page_title() ?></h2>

    <?php include 'safe-pay-admin-nav.php'; ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('safepay_options');
        do_settings_sections('safe-pay/admin/partials/safe-pay-admin-general.php');
        ?>
        <p class="submit">
            <input type="submit" class="button-primary"
                   value="<?php printf(__('Сохранить настройки', 'safe-pay')); ?>"/> <span
                    class="safepay-settings__save">&larr; <?php printf(__('Нажмите, чтобы сохранить ключи!',
                    'safe-pay')) ?></span>
        </p>
    </form>
</div>