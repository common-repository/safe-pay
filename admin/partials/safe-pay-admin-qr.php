<?php
defined('ABSPATH') || exit;

$all_options = get_option('safepay_options_qr');
?>
<div class="wrap safepay-settings">
    <h2><?php echo get_admin_page_title() ?></h2>

    <?php include 'safe-pay-admin-nav.php'; ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('safepay_options_qr');
        do_settings_sections('safe-pay/admin/partials/safe-pay-admin-qr.php');
        ?>
        <p class="submit">
            <input type="submit" class="button-primary"
                   value="<?php printf(__('Сохранить настройки', 'safe-pay')); ?>"/>
        </p>
    </form>
</div>