<?php
defined('ABSPATH') || exit;

use SafePay\Blockchain\DBServer;

$server = DBServer::getAllServers();
?>
<div class="wrap">
    <h2><?php echo get_admin_page_title() ?></h2>

    <?php include 'safe-pay-admin-nav.php'; ?>

    <?php if ($server !== false): ?>
        <form method="post" class="safepay-table__form">
            <table class="safepay-settings__table safepay-table">
                <tr class="safepay-table__row safepay-table__head">
                    <th class="safepay-table__col"><?php printf(__('ID', 'safe-pay')); ?></th>
                    <th class="safepay-table__col"><?php printf(__('URL сервера', 'safe-pay')); ?></th>
                    <th class="safepay-table__col"><?php printf(__('Тип сервера', 'safe-pay')); ?></th>
                    <th class="safepay-table__col"><?php printf(__('Последнее обновление', 'safe-pay')); ?></th>
                    <th class="safepay-table__col"><?php printf(__('Действие', 'safe-pay')); ?></th>
                </tr>
                <?php foreach ($server as $server_item): ?>
                    <tr class="safepay-table__row" id="safepay-table-server-<?php echo esc_html($server_item->ID); ?>">
                        <td class="safepay-table__col"><?php echo esc_html($server_item->ID); ?></td>
                        <td class="safepay-table__col"><?php echo esc_html($server_item->URL_SERVER); ?></td>
                        <td class="safepay-table__col"><?php echo esc_html($server_item->TYPE_SERVER); ?></td>
                        <td class="safepay-table__col"><?php echo esc_html(get_date_from_gmt(date("Y-m-d H:i:s",
                                round((int)$server_item->TIME_UPDATE / 1000)))); ?></td>
                        <td class="safepay-table__col">
                            <a class="safepay-table__del-server" href="#"
                               data-server_id="<?php echo esc_html($server_item->ID); ?>">
                                <?php echo __('Удалить', 'safe-pay'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="safepay-table__row safepay-table__row--form">
                    <td class="safepay-table__col"></td>
                    <td class="safepay-table__col">
                        <input type="text" name="URL_SERVER"
                               placeholder="<?php echo __('Введите ссылку сервера', 'safe-pay') ?>">
                    </td>
                    <td class="safepay-table__col">
                        <input type="text" name="TYPE_SERVER"
                               placeholder="<?php echo __('Введите тип сервера (test/live)', 'safe-pay') ?>">
                    </td>
                    <td class="safepay-table__col">
                        <input type="text" disabled
                               placeholder="<?php echo __('А здесь ничего вводить не нужно', 'safe-pay') ?>">
                    </td>
                    <td class="safepay-table__col">
                        <input type="submit" class="button-primary safepay-table__add-server"
                               value="<?php echo __('Добавить', 'safe-pay'); ?>">
                    </td>
                </tr>
            </table>
            <div class="safepay-table__result"></div>
        </form>
    <?php else: ?>
        <p><?php echo __('Не добавлено ни одного сервера.', 'safe-pay'); ?></p>
    <?php endif; ?>
</div>