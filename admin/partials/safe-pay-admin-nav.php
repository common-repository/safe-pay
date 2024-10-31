<?php
defined('ABSPATH') || exit;
?>
<nav class="safepay-settings__nav">
    <ul class="safepay-settings__ul">
        <li class="safepay-settings__li">
            <a class="safepay-settings__link<?php if ($_GET['page'] == 'safe-pay/admin/partials/safe-pay-admin-general.php') {
                echo ' safepay-settings__link--active';
            } ?>" href="?page=safe-pay/admin/partials/safe-pay-admin-general.php"><?php printf(__('Общие настройки',
                    'safe-pay')) ?></a>
        </li>
        <li class="safepay-settings__li">
            <a class="safepay-settings__link<?php if ($_GET['page'] == 'safe-pay/admin/partials/safe-pay-admin-transactions.php') {
                echo ' safepay-settings__link--active';
            } ?>" href="?page=safe-pay/admin/partials/safe-pay-admin-transactions.php"><?php printf(__('Транзакции',
                    'safe-pay')) ?></a>
        </li>
        <li class="safepay-settings__li">
            <a class="safepay-settings__link<?php if ($_GET['page'] == 'safe-pay/admin/partials/safe-pay-admin-extended.php') {
                echo ' safepay-settings__link--active';
            } ?>"
               href="?page=safe-pay/admin/partials/safe-pay-admin-extended.php"><?php printf(__('Расширенные настройки',
                    'safe-pay')) ?></a>
        </li>
        <li class="safepay-settings__li">
            <a class="safepay-settings__link<?php if ($_GET['page'] == 'safe-pay/admin/partials/safe-pay-admin-qr.php') {
                echo ' safepay-settings__link--active';
            } ?>"
               href="?page=safe-pay/admin/partials/safe-pay-admin-qr.php"><?php printf(__('Настройки QR-кода',
                    'safe-pay')) ?></a>
        </li>
        <li class="safepay-settings__li">
            <a class="safepay-settings__link<?php if ($_GET['page'] == 'safe-pay/admin/partials/safe-pay-admin-server.php') {
                echo ' safepay-settings__link--active';
            } ?>"
               href="?page=safe-pay/admin/partials/safe-pay-admin-server.php"><?php printf(__('Список серверов',
                    'safe-pay')) ?></a>
        </li>
    </ul>
</nav>